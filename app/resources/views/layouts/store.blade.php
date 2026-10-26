<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title', 'Catálogo') · {{ $store->name }}</title><link rel="stylesheet" href="{{ asset('css/store.css') }}"></head>
<body>
<a class="skip" href="#contenido">Ir al contenido</a>
<div class="demo">Demo local · Comercio, productos, compatibilidad, precios y existencias ficticios</div>
<header class="header wrap"><a class="brand" href="{{ route('catalog') }}"><span class="logo">{{ $store->initials }}</span><span><strong>{{ $store->name }}</strong><small>Accesorios de celular</small></span></a><nav aria-label="Navegación principal"><a href="{{ route('catalog') }}">Catálogo</a><a class="cart-link" href="{{ route('cart') }}">Tu carro →</a></nav></header>
<main class="wrap" id="contenido">
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())
<div class="notice error" role="alert"><strong>Revisa los datos</strong><ul>
@foreach($errors->getMessages() as $field => $messages)
@foreach($messages as $error)
@php($quantityProductId = str_starts_with($field, 'quantity.') ? substr($field, 9) : null)
<li>
@if($quantityProductId !== null)
<a href="#qty-{{ $quantityProductId }}">{{ $error }}</a>
@else
{{ $error }}
@endif
</li>
@endforeach
@endforeach
</ul><p>El carro válido se conserva.</p></div>
@endif
@yield('content')
</main>
<footer class="wrap footer"><span>{{ $store->name }} · Tienda ficticia</span><span>Tecnología de OxmanIT</span></footer>
</body></html>
