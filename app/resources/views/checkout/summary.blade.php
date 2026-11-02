<section class="panel checkout-summary">
    <h2>Artículos del pedido</h2>
    @foreach($order->lines as $line)
    <div class="checkout-line"><h3>{{ $line->nombre_producto }}</h3><p>{{ $line->sku }} · {{ $line->cantidad }} × ${{ number_format($line->precio_unitario, 0, ',', '.') }} c/u</p><p><strong>${{ number_format($line->subtotal, 0, ',', '.') }}</strong></p></div>
    @endforeach
    <p><strong>Subtotal productos: ${{ number_format($order->lines->sum('subtotal'), 0, ',', '.') }}</strong></p>
    @if($order->modalidad === 'retiro')
    <p>Retiro en tienda</p><p class="muted">Pedido de demostración, sin cobros. No hay un total definitivo.</p>
    @elseif(in_array($order->estado_despacho, ['cotizado', 'aceptado', 'despachado_registrado'], true))
    <p>Cotización de despacho: ${{ number_format($order->monto_despacho, 0, ',', '.') }}</p>
        @if(in_array($order->estado_despacho, ['aceptado', 'despachado_registrado'], true))
        <p><strong>Total con despacho aceptado: ${{ number_format($order->lines->sum('subtotal') + $order->monto_despacho, 0, ',', '.') }}</strong></p><p>No se realizó ningún cobro.</p>
        @else
        <p>La cotización requiere tu aceptación. No hay un total definitivo.</p>
        @endif
    @else
    <p>Despacho pendiente de cotización</p><p class="muted">El envío aún no está incluido. No hay un total definitivo.</p>
    @endif
</section>
