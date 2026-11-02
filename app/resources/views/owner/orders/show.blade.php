@extends('owner.layout')
@section('title', 'Pedido '.$order->reference)
@section('content')
<p><a href="{{ route('owner.orders.index') }}">Volver a pedidos</a></p>
<h1>Pedido {{ $order->reference }}</h1>
<p class="muted">{{ $order->created_at->timezone('America/Santiago')->format('d/m/Y H:i') }} · {{ $order->created_at->locale('es')->diffForHumans() }} · Datos ficticios</p>
<dl class="owner-order-states"><div><dt>Pedido</dt><dd>{{ ucfirst($order->estado_pedido) }}</dd></div><div><dt>Pago</dt><dd>{{ ucfirst($order->estado_pago) }}</dd></div><div><dt>Despacho</dt><dd>{{ ucfirst(str_replace('_', ' ', $order->estado_despacho)) }}</dd></div></dl>
<div class="owner-two">
<section class="panel"><h2>Comprador y entrega</h2>
    <dl><dt>Nombre</dt><dd>{{ $order->nombre }}</dd><dt>Teléfono</dt><dd>{{ $order->telefono }}</dd><dt>Correo</dt><dd>{{ $order->correo ?: 'No indicado' }}</dd><dt>Modalidad</dt><dd>{{ $order->modalidad === 'retiro' ? 'Retiro en tienda' : 'Despacho' }}</dd>
    @if($order->modalidad === 'despacho')<dt>Dirección</dt><dd>{{ $order->direccion }} · {{ $order->comuna }} · {{ $order->region }}</dd>@endif
    @if($order->motivo_cancelacion)<dt>Motivo de cancelación</dt><dd>{{ $order->motivo_cancelacion }}</dd>@endif</dl>
    <a href="{{ route('orders.tracking', $order->access_token) }}">Abrir seguimiento privado del comprador</a><p class="muted">Este enlace permite consultar el pedido. Compártelo solo con el comprador ficticio.</p>
</section>
@include('checkout.summary')
</div>
@if(in_array($order->estado_pedido, ['pendiente', 'preparacion'], true))
<section class="panel owner-form"><h2>Gestionar pedido</h2>
    @if($order->estado_despacho === 'pendiente_cotizacion')
    <form method="post" action="{{ route('owner.orders.quote', $order) }}">@csrf
        <h3>{{ $order->modalidad === 'retiro' ? 'Confirmar retiro sin costo' : 'Cotizar despacho' }}</h3>
        @if($order->modalidad === 'retiro')
        <input type="hidden" name="monto" value="0"><p>Retiro en tienda: $0 de despacho. Se acepta directamente.</p>
        @else
        <label for="monto">Monto de despacho en CLP</label><input id="monto" name="monto" type="number" min="1" max="4294967295" step="1" value="{{ is_scalar(old('monto')) ? old('monto') : '' }}" required aria-describedby="error-monto" aria-invalid="{{ $errors->has('monto') ? 'true' : 'false' }}"><p id="error-monto" class="field-error">@error('monto'){{ $message }}@enderror</p><p class="muted">El comprador debe aceptar el monto desde su seguimiento privado.</p>
        @endif
        <button>{{ $order->modalidad === 'retiro' ? 'Confirmar retiro sin costo' : 'Registrar cotización' }}</button>
    </form>
    @elseif($order->estado_despacho === 'cotizado')
    <p>Falta la aceptación del comprador. El dueño no puede aceptarla en su nombre.</p>
    @endif
    @if($order->estado_pago === 'pendiente')
    <form method="post" action="{{ route('owner.orders.payment', $order) }}">@csrf<h3>Pago</h3><p>Registra manualmente el pago ficticio. Esta acción no realiza cobros.</p><button>Registrar pago recibido</button></form>
    @endif
    @if($order->estado_pedido === 'pendiente')
    <form method="post" action="{{ route('owner.orders.prepare', $order) }}">@csrf<h3>Preparación</h3><button>Pasar a preparación</button></form>
    @endif
    <h3>{{ $order->modalidad === 'retiro' ? 'Entrega al comprador' : 'Salida a despacho' }}</h3>
    @if($order->estado_pedido === 'preparacion' && $order->estado_pago === 'recibido' && $order->estado_despacho === 'aceptado')
    <form method="post" action="{{ route('owner.orders.deliver', $order) }}">@csrf<p>Descuenta el stock físico y libera la reserva una sola vez.</p><button>{{ $order->modalidad === 'retiro' ? 'Registrar retiro entregado' : 'Registrar despacho' }}</button></form>
    @else
    <p class="muted">Para registrar la salida se requiere preparación, pago recibido y despacho o retiro aceptado.</p>
    @endif
    <details><summary>Cancelar pedido</summary><form method="post" action="{{ route('owner.orders.cancel', $order) }}">@csrf
        <p>Libera la reserva sin aumentar stock físico. Un pago recibido conserva su estado; no se generan devoluciones.</p>
        <label for="motivo">Motivo obligatorio</label><textarea name="motivo" id="motivo" rows="3" maxlength="500" required aria-describedby="error-motivo" aria-invalid="{{ $errors->has('motivo') ? 'true' : 'false' }}">{{ is_scalar(old('motivo')) ? old('motivo') : '' }}</textarea><p id="error-motivo" class="field-error">@error('motivo'){{ $message }}@enderror</p><button class="outline">Cancelar y liberar reserva</button>
    </form></details>
</section>
@else
<p class="notice">Pedido cerrado. No admite nuevas operaciones de gestión.</p>
@endif
<section class="panel"><h2>Historial del pedido</h2><ol class="owner-order-events">
@foreach($order->events as $event)
<li><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->timezone('America/Santiago')->format('d/m/Y H:i:s') }}</time> · {{ ucfirst(str_replace('_', ' ', $event->tipo)) }}: {{ $event->valor_anterior ?: 'Inicio' }} → {{ $event->valor_nuevo }}
@if($event->notas)<p>{{ $event->notas }}</p>@endif<p class="muted">{{ $event->usuario_id ? 'Dueño #'.$event->usuario_id : 'Comprador / creación del pedido' }}</p></li>
@endforeach
</ol></section>
@endsection
