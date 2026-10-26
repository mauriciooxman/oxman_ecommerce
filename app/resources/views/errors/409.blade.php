@extends('owner.layout')
@section('title', 'Conflicto al guardar')
@section('content')
@php
    $stockConflict = request()->routeIs('owner.adjust');
    $conflictProduct = auth()->user()?->ownsStore() && request()->routeIs('owner.update', 'owner.adjust', 'owner.state')
        ? auth()->user()->store->products()->find(request()->route('product')) : null;
    if ($conflictProduct && !Illuminate\Support\Facades\Gate::allows('view', $conflictProduct)) { $conflictProduct = null; }
    $draftFields = $stockConflict
        ? ['mode' => 'Modo', 'quantity' => 'Cantidad ingresada', 'reason' => 'Motivo']
        : ['sku' => 'SKU', 'name' => 'Nombre', 'description' => 'Descripción', 'compatibility' => 'Compatibilidad', 'category_id' => 'Categoría (ID)', 'unit_price' => 'Precio al detalle CLP'];
@endphp
<section class="panel"><h1>El cambio no se aplicó</h1><p>El producto cambió, está archivado o esta operación ya se usó con otros datos. Revisa la versión actual antes de volver a guardar.</p>
@if($conflictProduct)
<a class="button" href="{{ $stockConflict ? route('owner.stock', $conflictProduct) : route('owner.edit', $conflictProduct) }}">{{ $stockConflict ? 'Abrir stock e historial actual' : 'Abrir producto actual' }}</a>
@if(request()->routeIs('owner.update', 'owner.adjust'))<h2>Copia de tu borrador</h2><p>Copia los campos que quieras conservar y compáralos con la versión actual. Este borrador no se vuelve a enviar automáticamente.</p>
@foreach($draftFields as $field => $label)
@if(is_scalar(request()->input($field)))<label for="draft-{{ $field }}">{{ $label }}</label><textarea id="draft-{{ $field }}" readonly rows="3">{{ request()->input($field) }}</textarea>@endif
@endforeach
@if(!$stockConflict && is_array(request()->input('tiers')))
@foreach(array_slice(request()->input('tiers'), 0, 20) as $index => $tier)
@if(is_array($tier))<p>Tramo {{ $index + 1 }}: desde {{ is_scalar($tier['minimum_quantity'] ?? null) ? $tier['minimum_quantity'] : '' }} unidades, precio unitario {{ is_scalar($tier['unit_price'] ?? null) ? $tier['unit_price'] : '' }} CLP.</p>@endif
@endforeach
@endif
@endif
@endif
<p><a href="{{ route('owner.products') }}">Volver al catálogo privado</a></p></section>
@endsection
