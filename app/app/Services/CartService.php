<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(private PricingService $pricing) {}

    private function key(Store $store): string
    {
        return 'carts.'.$store->id;
    }

    public function snapshot(Request $request, Store $store): array
    {
        $saved = $request->session()->get($this->key($store), []);
        $lines = [];
        $notices = [];
        $subtotal = 0;
        foreach ($saved as $id => $entry) {
            $product = $store->products()->with('tiers')->publiclyVisible()->find($id);
            if (! $product || $product->stock < $entry['quantity']) {
                $notices[] = 'Un artículo dejó de estar disponible en la cantidad elegida y se quitó del carro. Revisa el catálogo.';
                unset($saved[$id]);
                continue;
            }
            $quote = $this->pricing->quote($product, $entry['quantity']);
            if ($quote['unit_price'] !== $entry['unit_price']) {
                $notices[] = 'Cambió el precio de '.$product->name.'. Revisa el importe actualizado.';
                $saved[$id]['unit_price'] = $quote['unit_price'];
            }
            $lines[] = ['product' => $product, 'quantity' => $entry['quantity'], ...$quote];
            $subtotal += $quote['subtotal'];
        }
        $request->session()->put($this->key($store), $saved);
        return compact('lines', 'notices', 'subtotal');
    }

    public function change(Request $request, Store $store, Product $product, bool $add): void
    {
        $raw = $request->input('quantity');
        if ((! is_string($raw) && ! is_int($raw)) || ! preg_match('/^[1-9][0-9]*$/D', (string) $raw)
            || strlen((string) $raw) > 7) {
            throw ValidationException::withMessages(['quantity.'.$product->id => $product->name.': ingresa una cantidad entera mayor que cero.']);
        }
        $saved = $request->session()->get($this->key($store), []);
        $quantity = (int) $raw + ($add ? ($saved[$product->id]['quantity'] ?? 0) : 0);
        if (! $product->published || $product->archived_at !== null || $quantity > $product->stock) {
            throw ValidationException::withMessages(['quantity.'.$product->id => $product->name.': no hay existencias para esa cantidad. Disponibles: '.$product->stock.'.']);
        }
        $quote = $this->pricing->quote($product, $quantity);
        $saved[$product->id] = ['quantity' => $quantity, 'unit_price' => $quote['unit_price']];
        $request->session()->put($this->key($store), $saved);
    }

    public function remove(Request $request, Store $store, Product $product): void
    {
        $request->session()->forget($this->key($store).'.'.$product->id);
    }
}
