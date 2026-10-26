<?php

namespace App\Http\Controllers\Owner;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\StateRequest;
use App\Http\Requests\StockRequest;
use App\Services\OwnerCatalogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CatalogController
{
    private function product(Request $request, int $id)
    {
        $product = $request->user()->store->products()->with('tiers')->findOrFail($id);
        Gate::authorize('view', $product);
        return $product;
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['archived' => ['nullable', 'in:1']]);
        $query = $request->user()->store->products()->with('tiers');
        isset($filters['archived']) ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
        return view('owner.index', ['store' => $request->user()->store, 'products' => $query->orderBy('id')->paginate(20)->withQueryString(), 'archived' => isset($filters['archived'])]);
    }

    public function create(Request $request)
    {
        return view('owner.product', ['store' => $request->user()->store, 'product' => null, 'categories' => $request->user()->store->categories()->orderBy('name')->get()]);
    }

    public function edit(Request $request, int $product)
    {
        return view('owner.product', ['store' => $request->user()->store, 'product' => $this->product($request, $product), 'categories' => $request->user()->store->categories()->orderBy('name')->get()]);
    }

    public function store(ProductRequest $request, OwnerCatalogService $catalog)
    {
        $product = $catalog->save($request->user(), null, $request->validated(), $request->file('photo'));
        return redirect()->route('owner.edit', $product)->with('status', 'Producto creado oculto y sin stock. Registra un ajuste para cargar unidades.');
    }

    public function update(ProductRequest $request, int $product, OwnerCatalogService $catalog)
    {
        $catalog->save($request->user(), $product, $request->validated(), $request->file('photo'));
        return redirect()->route('owner.edit', $product)->with('status', 'Producto, precios y foto guardados.');
    }

    public function state(StateRequest $request, int $product, OwnerCatalogService $catalog)
    {
        $catalog->changeState($request->user(), $product, $request->validated());
        return redirect()->route('owner.edit', $product)->with('status', match ($request->validated('action')) {
            'publish' => 'Producto publicado.', 'hide' => 'Producto oculto.', 'archive' => 'Producto archivado. Su historial se conserva.', 'restore' => 'Producto restaurado a oculto. Debes publicarlo explícitamente.',
        });
    }

    public function stock(Request $request, int $product)
    {
        $item = $this->product($request, $product);
        $movements = $item->movements()->where('store_id', $request->user()->store_id)->with('user')->orderByDesc('id')->paginate(20);
        foreach ($movements as $movement) { Gate::authorize('view', $movement); }
        return view('owner.stock', ['store' => $request->user()->store, 'product' => $item, 'movements' => $movements]);
    }

    public function adjust(StockRequest $request, int $product, StockService $stock)
    {
        $stock->adjust($request->user(), $product, $request->validated());
        return redirect()->route('owner.stock', $product)->with('status', 'Ajuste registrado. Reenviar la misma operación conserva un único movimiento.');
    }

    public function photo(Request $request, int $product)
    {
        $item = $this->product($request, $product);
        abort_unless($item->photo_path, 404);
        $stream = Storage::disk('catalog')->readStream($item->photo_path);
        abort_unless(is_resource($stream), 404);
        return response()->stream(function () use ($stream) {
            try { fpassthru($stream); } finally { fclose($stream); }
        }, 200, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
