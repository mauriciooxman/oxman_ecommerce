<?php

namespace App\Http\Controllers\Owner;

use App\Http\Requests\Owner\{CancelOrderRequest, QuoteShippingRequest};
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController
{
    private function order(Request $request, int $id): Order
    {
        return Order::forStore($request->user()->store)->findOrFail($id);
    }
    public function index(Request $request)
    {
        $filters = $request->validate(['estado' => ['nullable', \Illuminate\Validation\Rule::in(Order::STATES)]]);
        $query = Order::forStore($request->user()->store);
        if (!empty($filters['estado'])) { $query->where('estado_pedido', $filters['estado']); }
        return view('owner.orders.index', ['store' => $request->user()->store,
            'orders' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString(), 'estado' => $filters['estado'] ?? '']);
    }
    public function show(Request $request, int $order)
    {
        $item = $this->order($request, $order);
        \Illuminate\Support\Facades\DB::table('orders')->where('id', $item->id)->where('store_id', $request->user()->store_id)
            ->whereNull('reviewed_at')->update(['reviewed_at' => now()]);
        $item->refresh()->load(['lines', 'events' => fn ($query) => $query->orderBy('created_at')->orderBy('id')]);
        return view('owner.orders.show', ['store' => $request->user()->store, 'order' => $item]);
    }
    public function cancel(CancelOrderRequest $request, int $order, OrderService $service)
    {
        $service->cancelar($this->order($request, $order), $request->user(), $request->validated('motivo'));
        return $this->back($order, 'Pedido cancelado. Reserva liberada; stock físico conservado.');
    }
    public function quoteShipping(QuoteShippingRequest $request, int $order, OrderService $service)
    {
        $item = $this->order($request, $order);
        abort_if($request->boolean('tipo_retiro') && $item->modalidad !== 'retiro', 422, 'El pedido es para despacho.');
        $amount = $request->validated('monto');
        $service->cotizarDespacho($item, $request->user(), $amount === null ? null : (int)$amount);
        return $this->back($order, $item->modalidad === 'retiro' ? 'Retiro sin costo aceptado.' : 'Cotización registrada; falta aceptación del comprador.');
    }
    public function confirmPayment(Request $request, int $order, OrderService $service)
    {
        $service->confirmarPago($this->order($request, $order), $request->user());
        return $this->back($order, 'Confirmación manual de pago registrada. No se realizó ningún cobro.');
    }
    public function toPreparation(Request $request, int $order, OrderService $service)
    {
        $service->pasarAPreparacion($this->order($request, $order), $request->user());
        return $this->back($order, 'Pedido en preparación.');
    }
    public function deliver(Request $request, int $order, OrderService $service)
    {
        $service->registrarEntrega($this->order($request, $order), $request->user());
        return $this->back($order, 'Salida registrada. Stock descontado y reserva liberada una vez.');
    }
    private function back(int $id, string $message)
    {
        return redirect()->route('owner.orders.show', $id)->with('status', $message);
    }
}
