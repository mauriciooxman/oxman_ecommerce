@extends('owner.layout')
@section('title', 'Ingresar')
@section('content')
<section class="panel login"><h1>Ingresar al panel</h1><p>Acceso del dueño para administrar su tienda ficticia.</p><form method="post" action="{{ route('owner.authenticate') }}">@csrf
<label for="email">Correo</label><input id="email" name="email" type="email" autocomplete="username" maxlength="254" value="{{ is_string(old('email')) ? old('email') : '' }}" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error"><p class="field-error" id="email-error">@error('email'){{ $message }}@enderror</p>
<label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error"><p class="field-error" id="password-error">@error('password'){{ $message }}@enderror</p><button>Ingresar</button></form><p class="muted">Mauro crea y restablece las cuentas mediante la consola local.</p></section>
@endsection
