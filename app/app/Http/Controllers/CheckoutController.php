<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Store;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private function store(): Store { return Store::where('slug', 'tecnologia-demo')->firstOrFail(); }
    private function key(Store $store): string { return 'checkout.'.$store->id; }

    public function review(Request $request, CartService $cart)
    {
        $store = $this->store();
        $snapshot = $cart->snapshot($request, $store);
        if (!$snapshot['lines']) {
            return redirect()->route('catalog')->with('status', implode(' ', $snapshot['notices']).' El carro está vacío. Agrega artículos para revisar tu pedido.');
        }
        $key = $request->session()->get($this->key($store).'.pending', (string)Str::uuid());
        $saved = [];
        foreach ($snapshot['lines'] as $line) { $saved[$line['product']->id] = ['quantity' => $line['quantity'], 'unit_price' => $line['unit_price']]; }
        $request->session()->put($this->key($store).'.pending', $key);
        $request->session()->put($this->key($store).'.forms.'.$key, ['lines' => $saved]);
        return response()->view('checkout.review', ['store' => $store, 'idempotencyKey' => $key, ...$snapshot]);
    }

    public function confirm(CheckoutRequest $request, OrderService $orders)
    {
        $store = $this->store();
        $key = $request->validated('idempotency_key');
        $form = $request->session()->get($this->key($store).'.forms.'.$key);
        abort_unless($form, 419, 'Vuelve a revisar el pedido antes de confirmar.');
        try {
            $order = $orders->confirmar($store, $request->validated(), $key, $form['lines']);
        } catch (ValidationException $error) {
            return redirect()->route('checkout.review')->withErrors($error->errors())->withInput($request->except('_token'));
        }
        if (!isset($form['order_id'])) {
            $request->session()->put($this->key($store).'.forms.'.$key.'.order_id', $order->id);
            $request->session()->forget('carts.'.$store->id);
            $request->session()->forget($this->key($store).'.pending');
        }
        return redirect()->route('checkout.confirmation', $order->reference);
    }

    public function confirmation(Request $request, string $referencia)
    {
        $store = $this->store();
        $ids = array_column($request->session()->get($this->key($store).'.forms', []), 'order_id');
        $order = Order::with('lines')->where('store_id', $store->id)->whereIn('id', $ids)->where('reference', $referencia)->firstOrFail();
        return response()->view('checkout.confirmation', compact('store', 'order'));
    }

    public function tracking(string $token)
    {
        $store = $this->store();
        $order = Order::with('lines')->where('store_id', $store->id)->where('access_token', $token)->firstOrFail();
        return response()->view('checkout.tracking', compact('store', 'order'));
    }

    public function acceptQuote(string $token, OrderService $orders)
    {
        $orders->aceptarCotizacion($this->store(), $token);
        return redirect()->route('orders.tracking', $token)->with('status', 'Cotización de despacho aceptada. No se realizó ningún cobro.');
    }
}
