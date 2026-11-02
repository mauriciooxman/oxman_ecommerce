@extends('layouts.store')
@section('title', 'Seguimiento de pedido')
@section('content')
<a class="back" href="{{ route('catalog') }}">Volver al catálogo</a>
<h1>Seguimiento de pedido</h1>
<p>Referencia: <strong>{{ $order->reference }}</strong></p>
<p>{{ $order->nombre }} · {{ $order->modalidad === 'retiro' ? 'Retiro en tienda' : 'Despacho' }}</p>
<p class="muted">Registrado {{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->created_at->locale('es')->diffForHumans() }}</p>
<dl class="order-status">
    <dt>Pedido</dt><dd>{{ ucfirst($order->estado_pedido) }}</dd>
    <dt>Pago</dt><dd>{{ ucfirst($order->estado_pago) }}</dd>
    <dt>Despacho</dt><dd>{{ ucfirst(str_replace('_', ' ', $order->estado_despacho)) }}</dd>
</dl>
@include('checkout.summary')
@if($order->modalidad === 'despacho' && $order->estado_pedido !== 'cancelado' && $order->estado_despacho === 'cotizado')
<form class="quote-accept" method="post" action="{{ route('orders.accept', $order->access_token) }}">@csrf<button>Aceptar cotización de despacho</button></form>
@endif
@endsection
