<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Services\CartService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorefrontController extends Controller
{
    private function store(): Store
    {
        return Store::where('slug', 'tecnologia-demo')->firstOrFail();
    }

    private function product(Store $store, string $sku): Product
    {
        return $store->products()->with('tiers')->publiclyVisible()->where('sku', $sku)->firstOrFail();
    }

    public function index(Request $request)
    {
        $store = $this->store();
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:60']]);
        $query = $store->products()->with('tiers')->publiclyVisible();
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%');
            });
        }
        $category = $filters['category'] ?? '';
        if ($category !== '') {
            $query->where('category', $category);
        }
        $products = $query->orderBy('id')->get();
        $categories = $store->products()->publiclyVisible()->distinct()->pluck('category');
        return view('catalog', compact('store', 'products', 'categories', 'filters'));
    }

    public function show(Request $request, string $sku, PricingService $pricing)
    {
        $store = $this->store();
        $product = $this->product($store, $sku);
        if ($request->query('foto') === '1') {
            abort_unless($product->photo_path, 404);
            $stream = Storage::disk('catalog')->readStream($product->photo_path);
            abort_unless(is_resource($stream), 404);
            return response()->stream(function () use ($stream) {
                try { fpassthru($stream); } finally { fclose($stream); }
            }, 200, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store']);
        }
        $quote = $product->stock ? $pricing->quote($product, 1) : null;
        return view('product', compact('store', 'product', 'quote'));
    }

    public function cart(Request $request, CartService $cart)
    {
        $store = $this->store();
        return view('cart', ['store' => $store, ...$cart->snapshot($request, $store)]);
    }

    public function add(Request $request, string $sku, CartService $cart)
    {
        $store = $this->store();
        $cart->change($request, $store, $this->product($store, $sku), true);
        return redirect()->route('cart')->with('status', 'Artículo agregado. El carro no reserva existencias.');
    }

    public function update(Request $request, string $sku, CartService $cart)
    {
        $store = $this->store();
        $cart->change($request, $store, $this->product($store, $sku), false);
        return redirect()->route('cart')->with('status', 'Cantidad y precio actualizados.');
    }

    public function remove(Request $request, string $sku, CartService $cart)
    {
        $store = $this->store();
        $product = $store->products()->where('sku', $sku)->firstOrFail();
        $cart->remove($request, $store, $product);
        return redirect()->route('cart')->with('status', 'Artículo quitado.');
    }
}
