<?php

namespace App\Services;

use App\Mail\NewOwnerOrder;
use App\Models\Order;
use Illuminate\Support\Facades\{DB, Log, Mail};

class OwnerOrderNotice
{
    // Un intento persistente: evita duplicados incluso con procesos concurrentes.
    public function attempt(int $orderId): void
    {
        try {
            // Nunca capturar antes del commit exterior, tampoco desde consola.
            if (DB::transactionLevel() > 0) { return; }
            if (!DB::table('order_owner_notices')->where('order_id', $orderId)->where('status', 'pending')
                ->update(['status' => 'attempting', 'attempted_at' => now()])) { return; }
            $order = Order::with('store')->findOrFail($orderId);
            $recipient = config('owner_orders.recipients', [])[$order->store->slug] ?? null;
            if (!is_string($recipient) || strlen($recipient) > 254 || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $this->failed($orderId, 'recipient_not_configured');
                return;
            }
            // El host viene de configuración, no del Host/Referer enviado por el comprador.
            $url = rtrim(config('app.url'), '/').route('owner.orders.show', $orderId, false);
            Mail::mailer('owner_order_capture')->to($recipient)->send(new NewOwnerOrder($order->reference, $order->estado_pago, $url));
            DB::table('order_owner_notices')->where('order_id', $orderId)->update(['status' => 'captured', 'captured_at' => now()]);
        } catch (\Throwable) {
            $this->failed($orderId, 'capture_failed');
        }
    }

    private function failed(int $orderId, string $code): void
    {
        try {
            DB::table('order_owner_notices')->where('order_id', $orderId)->update(['status' => 'failed', 'failure_code' => $code]);
        } catch (\Throwable) { /* El pedido ya está confirmado; no propagar errores de infraestructura. */ }
        try {
            Log::warning('owner_order_notice_failed', ['order_id' => $orderId, 'code' => $code]);
        } catch (\Throwable) { /* Conservar la compra también si falla el registro de diagnóstico. */ }
    }
}
