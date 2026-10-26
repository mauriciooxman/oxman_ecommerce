@extends('owner.layout')
@section('title', 'Catálogo privado')
@section('content')
<div class="owner-heading"><div><p class="eyebrow">{{ $store->name }} · Datos ficticios</p><h1>{{ $archived ? 'Productos archivados' : 'Tu catálogo' }}</h1></div><a class="button" href="{{ route('owner.create') }}">Crear producto</a></div>
<nav class="owner-actions" aria-label="Filtrar productos"><a href="{{ route('owner.products') }}" @if(!$archived) aria-current="page" @endif>Activos y ocultos</a><a href="{{ route('owner.products', ['archived' => 1]) }}" @if($archived) aria-current="page" @endif>Archivados</a></nav>
<div class="grid">@forelse($products as $product)<article class="panel"><p class="eyebrow">{{ $product->category }} · {{ $product->sku }}</p><h2><a href="{{ route('owner.edit', $product) }}">{{ $product->name }}</a></h2><p>{{ $product->archived_at ? 'Archivado' : ($product->published ? 'Publicado' : 'Oculto') }} · Stock {{ $product->stock }}</p><p>${{ number_format($product->unit_price, 0, ',', '.') }} CLP · {{ $product->tiers->count() }} {{ $product->tiers->count() === 1 ? 'tramo' : 'tramos' }}</p><a href="{{ route('owner.stock', $product) }}">Stock e historial</a></article>@empty<p class="panel">No hay productos en este filtro.</p>@endforelse</div>{{ $products->links() }}
@endsection
