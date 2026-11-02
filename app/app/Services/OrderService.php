<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private PricingService $pricing) {}

    // $lines procede exclusivamente de la revisión guardada en sesión, nunca del POST.
    public function confirmar(Store $store, array $datos, string $idempotencyKey, array $lines): Order
    {
        return DB::transaction(function () use ($store, $datos, $idempotencyKey, $lines) {
            // Serializa reintentos incluso antes de que exista el pedido. Orden fijo:
            // tienda -> productos por id. No depende de un índice vacío/gap lock.
            Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $existing = Order::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                abort_unless((int)$existing->store_id === (int)$store->id, 409);
                return $existing;
            }
            if (!$lines) { $this->reject('El carro está vacío. Vuelve al catálogo.'); }
            $products = $store->products()->whereIn('id', array_keys($lines))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $snapshots = [];
            $subtotal = 0;
            foreach ($lines as $id => $line) {
                $product = $products->get($id);
                if (!$product || !$product->published || $product->archived_at) {
                    $this->reject('Un artículo dejó de estar disponible. Revisa nuevamente el carro.');
                }
                $quantity = $line['quantity'] ?? null;
                if (!is_int($quantity) || $quantity < 1 || $quantity > 1000000) { $this->reject('Cantidad inválida. Revisa el carro.'); }
                if ($product->available_stock < $quantity) {
                    $this->reject($product->name.': stock insuficiente. Disponibles: '.$product->available_stock.'.');
                }
                $quote = $this->pricing->quote($product, $quantity);
                if ($quote['unit_price'] !== ($line['unit_price'] ?? null)) {
                    $this->reject('Cambió el precio de '.$product->name.'. Revisa el importe actualizado antes de confirmar.');
                }
                $subtotal += $quote['subtotal'];
                if ($quote['unit_price'] < 0 || $subtotal > 4294967295) { $this->reject('El importe supera el límite del pedido de demostración.'); }
                $snapshots[] = ['product_id' => $product->id, 'sku' => $product->sku, 'nombre_producto' => $product->name,
                    'cantidad' => $quantity, 'precio_unitario' => $quote['unit_price'], 'subtotal' => $quote['subtotal']];
            }
            $contact = array_intersect_key($datos, array_flip(['nombre', 'telefono', 'correo', 'modalidad', 'region', 'comuna', 'direccion']));
            if ($contact['modalidad'] === 'retiro') { $contact['region'] = $contact['comuna'] = $contact['direccion'] = null; }
            $order = Order::create([...$contact, 'store_id' => $store->id,
                'reference' => 'OXM-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3))),
                'idempotency_key' => $idempotencyKey, 'access_token' => (string)Str::uuid(),
                'estado_pedido' => 'pendiente', 'estado_pago' => 'pendiente', 'estado_despacho' => 'pendiente_cotizacion']);
            foreach ($snapshots as $snapshot) {
                $order->lines()->create($snapshot);
                $product = $products[$snapshot['product_id']];
                $product->stock_reservado += $snapshot['cantidad'];
                $product->save();
            }
            $order->events()->create(['tipo' => 'estado_pedido', 'valor_nuevo' => 'pendiente']);
            DB::table('order_owner_notices')->insert(['order_id' => $order->id, 'status' => 'pending']);
            DB::afterCommit(fn () => app(OwnerOrderNotice::class)->attempt($order->id));
            return $order;
        }, 3);
    }

    public function aceptarCotizacion(Store $store, string $accessToken): Order
    {
        return DB::transaction(function () use ($store, $accessToken) {
            $order = Order::where('store_id', $store->id)->where('access_token', $accessToken)->lockForUpdate()->firstOrFail();
            abort_if($order->estado_pedido === 'cancelado', 422, 'El pedido está cancelado.');
            if ($order->estado_despacho === 'aceptado') { return $order; }
            abort_unless($order->modalidad === 'despacho' && $order->estado_pedido !== 'cancelado'
                && $order->estado_despacho === 'cotizado' && $order->monto_despacho !== null, 422, 'No hay una cotización disponible para aceptar.');
            $order->update(['estado_despacho' => 'aceptado']);
            $order->events()->create(['tipo' => 'estado_despacho', 'valor_anterior' => 'cotizado', 'valor_nuevo' => 'aceptado']);
            return $order;
        }, 3);
    }

    public function cotizarDespacho(Order $order, User $user, ?int $montoClp): void
    {
        $this->manage($order, $user, function (Order $current) use ($user, $montoClp) {
            $this->assertOpen($current);
            abort_unless($current->estado_despacho === 'pendiente_cotizacion', 422, 'La cotización ya fue registrada.');
            $amount = $montoClp ?? ($current->modalidad === 'retiro' ? 0 : null);
            abort_unless(is_int($amount) && $amount >= 0 && $amount <= 4294967295, 422, 'Indica un monto válido en CLP.');
            abort_unless($current->modalidad === 'retiro' ? $amount === 0 : $amount > 0, 422, 'Retiro requiere monto cero; despacho requiere un monto mayor que cero.');
            abort_if($current->lines()->sum('subtotal') + $amount > 4294967295, 422, 'El importe supera el límite de la demo.');
            $current->monto_despacho = $amount;
            $this->transition($current, $user, 'estado_despacho', $current->modalidad === 'retiro' ? 'aceptado' : 'cotizado');
        });
    }

    public function confirmarPago(Order $order, User $user): void
    {
        $this->manage($order, $user, function (Order $current) use ($user) {
            $this->assertOpen($current);
            abort_if($current->estado_pago === 'recibido', 409, 'El pago ya está registrado.');
            $this->transition($current, $user, 'estado_pago', 'recibido', 'Confirmación manual ficticia; sin pasarela.');
        });
    }

    public function pasarAPreparacion(Order $order, User $user): void
    {
        $this->manage($order, $user, function (Order $current) use ($user) {
            $this->assertOpen($current);
            abort_unless($current->estado_pedido === 'pendiente', 422, 'El pedido ya no está pendiente.');
            $this->transition($current, $user, 'estado_pedido', 'preparacion');
        });
    }

    public function cancelar(Order $order, User $user, string $motivo): void
    {
        abort_if(trim($motivo) === '' || mb_strlen($motivo) > 500, 422, 'Indica un motivo de hasta 500 caracteres.');
        $this->manage($order, $user, function (Order $current) use ($user, $motivo) {
            abort_if($current->estado_pedido === 'cancelado', 409, 'El pedido ya está cancelado.');
            $this->assertOpen($current);
            $this->settleStock($current, $user, false);
            $current->motivo_cancelacion = trim($motivo);
            $this->transition($current, $user, 'estado_pedido', 'cancelado', trim($motivo));
        });
    }

    public function registrarEntrega(Order $order, User $user): void
    {
        $this->manage($order, $user, function (Order $current) use ($user) {
            abort_if(in_array($current->estado_pedido, ['despachado', 'retirado'], true), 409, 'La salida ya está registrada.');
            abort_unless($current->estado_pedido === 'preparacion' && $current->estado_pago === 'recibido'
                && $current->estado_despacho === 'aceptado', 422, 'Requiere preparación, pago confirmado y entrega aceptada.');
            $this->settleStock($current, $user, true);
            $this->transition($current, $user, 'estado_pedido', $current->modalidad === 'retiro' ? 'retirado' : 'despachado');
            $this->transition($current, $user, 'estado_despacho', $current->modalidad === 'retiro' ? 'retirado_registrado' : 'despachado_registrado');
        });
    }

    private function manage(Order $order, User $user, \Closure $action): void
    {
        $user->refresh()->load('store');
        abort_unless($user->ownsStore(), 403);
        DB::transaction(function () use ($order, $user, $action) {
            $current = Order::forStore($user->store)->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $action($current);
        }, 3);
    }

    private function assertOpen(Order $order): void
    {
        abort_unless(in_array($order->estado_pedido, ['pendiente', 'preparacion'], true), 422, 'El pedido está cerrado.');
    }

    private function transition(Order $order, User $user, string $field, string $value, ?string $notes = null): void
    {
        $previous = $order->$field;
        $order->$field = $value;
        $order->save();
        $order->events()->create(['tipo' => $field, 'valor_anterior' => $previous, 'valor_nuevo' => $value,
            'notas' => $notes, 'usuario_id' => $user->id]);
    }

    private function settleStock(Order $order, User $user, bool $deliver): void
    {
        $quantities = $order->lines()->get()->groupBy('product_id')->map(fn ($lines) => (int)$lines->sum('cantidad'));
        abort_if($quantities->isEmpty(), 422, 'Pedido sin líneas reservadas.');
        $products = $user->store->products()->whereIn('id', $quantities->keys())->orderBy('id')->lockForUpdate()->get();
        abort_unless($products->count() === $quantities->count(), 422, 'No se encontraron todos los productos de la tienda.');
        foreach ($products as $product) {
            $quantity = $quantities[$product->id];
            abort_if($quantity < 1 || $product->stock_reservado < $quantity || $product->stock < $product->stock_reservado, 422, 'Inventario inconsistente; no se modificó el pedido.');
            if ($deliver) {
                $product->movements()->create(['store_id' => $order->store_id, 'user_id' => $user->id,
                    'operation_id' => (string)Str::uuid(), 'mode' => 'variation', 'quantity' => -$quantity,
                    'before' => $product->stock, 'after' => $product->stock - $quantity, 'delta' => -$quantity,
                    'reason' => 'Salida por pedido '.$order->reference, 'created_at' => now()]);
                $product->stock -= $quantity;
                $product->version++;
            }
            $product->stock_reservado -= $quantity;
            $product->save();
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['checkout' => $message]);
    }
}
