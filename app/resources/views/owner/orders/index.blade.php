@extends('owner.layout')
@section('title', 'Pedidos')
@section('content')
<h1>Pedidos de {{ $store->name }}</h1>
<p class="muted">Pedido, pago y despacho se gestionan por separado. Datos ficticios.</p>
<form class="filters" method="get" action="{{ route('owner.orders.index') }}">
    <div><label for="estado">Estado del pedido</label><select id="estado" name="estado"><option value="">Todos</option>
        @foreach(\App\Models\Order::STATES as $state)<option value="{{ $state }}" @selected($estado === $state)>{{ ucfirst($state) }}</option>@endforeach
    </select></div><button>Filtrar pedidos</button><a href="{{ route('owner.orders.index') }}">Limpiar filtro</a>
</form>
@forelse($orders as $order)
<article class="panel">
    <h2><a href="{{ route('owner.orders.show', $order) }}">{{ $order->reference }}</a> @if(!$order->reviewed_at)<span class="order-new">Nuevo</span>@endif</h2>
    <p>{{ $order->nombre }} · {{ $order->modalidad === 'retiro' ? 'Retiro en tienda' : 'Despacho' }}</p>
    <p class="muted"><time datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->timezone('America/Santiago')->format('d/m/Y H:i') }}</time> · {{ $order->created_at->locale('es')->diffForHumans() }}</p>
    <dl class="owner-order-states"><div><dt>Pedido</dt><dd>{{ ucfirst($order->estado_pedido) }}</dd></div><div><dt>Pago</dt><dd>{{ ucfirst($order->estado_pago) }}</dd></div><div><dt>Despacho</dt><dd>{{ ucfirst(str_replace('_', ' ', $order->estado_despacho)) }}</dd></div></dl>
</article>
@empty
<p class="panel">No hay pedidos para este filtro.</p>
@endforelse
{{ $orders->links() }}
@endsection
