@extends('layouts.store')
@section('title', 'Catálogo')
@section('content')
<section class="intro"><p class="eyebrow">POR UNIDAD O POR CANTIDAD</p><h1>Encuentra tu próximo accesorio</h1><p>Elige lo que necesitas y descubre el precio para tu cantidad.</p></section>
<form class="filters panel" method="get" action="{{ route('catalog') }}" role="search"><div><label for="q">Buscar producto o modelo</label><input id="q" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Ej. cable USB-C"></div><div><label for="category">Categoría</label><select id="category" name="category"><option value="">Todas las categorías</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>@endforeach</select></div><button>Buscar</button><a href="{{ route('catalog') }}">Limpiar filtros</a></form>
<p class="muted">{{ $products->count() }} {{ $products->count() === 1 ? 'producto encontrado' : 'productos encontrados' }} · Precios en CLP</p>
<div class="grid">
@forelse($products as $product)
<article class="panel card" id="catalog-item-{{ $product->id }}"><figure><img src="{{ $product->photo_url }}" alt="{{ $product->photo_path ? 'Fotografía de ' : 'Ilustración de ' }}{{ $product->name }}" width="480" height="320"><figcaption>{{ $product->photo_path ? 'Fotografía' : 'Ilustración' }} · Producto ficticio</figcaption></figure><div class="card-body"><p class="eyebrow">{{ $product->category }}</p><h2><a href="{{ route('product', $product->sku) }}">{{ $product->name }}</a></h2><p class="muted">Modelo {{ $product->sku }}</p><p class="price">${{ number_format($product->unit_price, 0, ',', '.') }} <small>c/u</small></p><p class="muted">Precio al detalle · CLP</p>@if($tier = $product->tiers->first(fn ($tier) => $tier->minimum_quantity <= $product->available_stock))<p class="tier">Desde {{ $tier->minimum_quantity }} unidades: <strong>${{ number_format($tier->unit_price, 0, ',', '.') }} c/u</strong></p>@endif<p class="stock">{{ $product->available_stock ? $product->available_stock.' unidades disponibles' : 'Sin stock' }}</p>@php($quantityError = 'quantity.'.$product->id)
<form class="catalog-add" method="post" action="{{ route('cart.add', $product->sku) }}" data-catalog-add data-product-id="{{ $product->id }}">
@csrf<input type="hidden" name="from_catalog" value="1">
<label for="qty-{{ $product->id }}">Cantidad de {{ $product->name }}</label>
<input class="quantity" id="qty-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ max(1, $product->available_stock) }}" step="1" value="{{ $errors->has($quantityError) && is_scalar(old('quantity')) ? old('quantity') : 1 }}" required @disabled(!$product->available_stock) aria-invalid="{{ $errors->has($quantityError) ? 'true' : 'false' }}" aria-describedby="qty-error-{{ $product->id }}">
<p class="field-error" id="qty-error-{{ $product->id }}">@error($quantityError){{ $message }}@enderror</p>
<button @disabled(!$product->available_stock) aria-label="Agregar {{ $product->name }} al carro">Agregar al carro</button>
</form>
<p class="catalog-added" role="status" aria-atomic="true" id="catalog-added-{{ $product->id }}">@if((int)session('catalog_added') === $product->id)Artículo agregado. El carro no reserva existencias. <a href="{{ route('cart') }}">Ver carro</a>@endif</p>
<a class="catalog-details" href="{{ route('product', $product->sku) }}">Ver producto →</a></div></article>
@empty
<div class="panel empty"><h2>No encontramos productos</h2><p>Prueba otra búsqueda o revisa todas las categorías.</p><a class="button" href="{{ route('catalog') }}">Volver al catálogo</a></div>
@endforelse
</div>
<script src="{{ asset('js/catalog.js') }}" defer></script>
@endsection
