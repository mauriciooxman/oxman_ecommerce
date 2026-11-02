@extends('layouts.store')
@section('title', 'Revisar pedido')
@section('content')
<a class="back" href="{{ route('cart') }}">Volver al carro</a>
<h1>Revisa tu pedido</h1>
<p>Usa datos ficticios. Al confirmar se reservarán las unidades; no se realizará ningún cobro.</p>
@foreach($notices as $notice)<div class="notice" role="status">{{ $notice }}</div>@endforeach
<div class="cart-layout checkout-layout">
<form class="panel checkout-form" method="post" action="{{ route('checkout.confirm') }}">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
    <h2>Contacto de demostración</h2>
    @foreach(['nombre' => ['Nombre', 'text', 160, 'name'], 'telefono' => ['Teléfono', 'tel', 20, 'tel'], 'correo' => ['Correo (opcional)', 'email', 254, 'email']] as $field => [$label, $type, $max, $autocomplete])
    <div class="checkout-field">
        <label for="{{ $field }}">{{ $label }}</label>
        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" maxlength="{{ $max }}" autocomplete="{{ $autocomplete }}" value="{{ is_scalar(old($field)) ? old($field) : '' }}" @required($field !== 'correo') aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="error-{{ $field }}">
        <p class="field-error" id="error-{{ $field }}">@error($field){{ $message }}@enderror</p>
    </div>
    @endforeach
    <h2>Entrega</h2>
    <label for="modalidad">Modalidad</label>
    <select name="modalidad" id="modalidad" aria-describedby="error-modalidad" required>
        <option value="retiro" @selected(old('modalidad', 'retiro') === 'retiro')>Retiro en tienda</option>
        <option value="despacho" @selected(old('modalidad') === 'despacho')>Despacho pendiente de cotización</option>
    </select>
    <p class="field-error" id="error-modalidad">@error('modalidad'){{ $message }}@enderror</p>
    <fieldset id="direccion-despacho">
        <legend>Dirección para despacho</legend>
        <p class="muted">Completa estos datos solo si eliges despacho.</p>
        @foreach(['region' => ['Región', 100], 'comuna' => ['Comuna', 100], 'direccion' => ['Dirección', 255]] as $field => [$label, $max])
        <div class="checkout-field">
            <label for="{{ $field }}">{{ $label }}</label>
            <input id="{{ $field }}" name="{{ $field }}" maxlength="{{ $max }}" value="{{ is_scalar(old($field)) ? old($field) : '' }}" aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="error-{{ $field }}">
            <p class="field-error" id="error-{{ $field }}">@error($field){{ $message }}@enderror</p>
        </div>
        @endforeach
    </fieldset>
    <p class="muted">El servidor comprobará nuevamente los precios y las unidades disponibles.</p>
    <button>Confirmar pedido de demostración</button>
</form>
<aside class="panel summary">
    <h2>Resumen del pedido</h2>
    @foreach($lines as $line)
    <div class="checkout-line"><h3>{{ $line['product']->name }}</h3><p>{{ $line['quantity'] }} × ${{ number_format($line['unit_price'], 0, ',', '.') }} c/u</p><p><strong>${{ number_format($line['subtotal'], 0, ',', '.') }}</strong></p></div>
    @endforeach
    <div class="row"><span>Subtotal productos</span><strong>${{ number_format($subtotal, 0, ',', '.') }}</strong></div>
    <p class="muted">Despacho pendiente de cotización si eliges envío. No hay un total definitivo.</p>
</aside>
</div>
<script>
(() => {
    const mode = document.getElementById('modalidad');
    const address = document.getElementById('direccion-despacho');
    const update = () => {
        address.hidden = mode.value !== 'despacho';
        address.querySelectorAll('input').forEach(input => { input.required = !address.hidden; input.disabled = address.hidden; });
    };
    mode.addEventListener('change', update);
    update();
})();
</script>
@endsection
