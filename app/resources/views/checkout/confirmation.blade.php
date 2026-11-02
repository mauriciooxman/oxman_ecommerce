@extends('layouts.store')
@section('title', 'Pedido registrado')
@section('content')
<a class="back" href="{{ route('catalog') }}">Volver al catálogo</a>
<h1>Pedido registrado</h1>
<p>Referencia: <strong>{{ $order->reference }}</strong></p>
<p>Las unidades están reservadas. Esta demo no realiza cobros.</p>
<p><a class="button" href="{{ route('orders.tracking', $order->access_token) }}">Consultar seguimiento privado</a></p>
<p class="muted">Guarda el enlace de seguimiento. Quien tenga ese enlace podrá consultar el pedido; la referencia por sí sola no permite acceder.</p>
@include('checkout.summary')
@endsection
