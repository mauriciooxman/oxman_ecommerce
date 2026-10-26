<?php

namespace App\Http\Controllers\Owner;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function create()
    {
        return view('owner.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:254'], 'password' => ['required', 'string', function ($attribute, $value, $fail) {
            if (is_string($value) && strlen($value) > 72) { $fail('La contraseña no puede superar 72 bytes.'); }
        }]]);
        $email = Str::lower(trim($data['email']));
        $key = hash('sha256', $email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Vuelve a intentar en un minuto.']);
        }
        if (! Auth::attempt(['email' => $email, 'password' => $data['password']], false)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'No fue posible ingresar con esas credenciales.']);
        }
        if (! $request->user()->ownsStore()) {
            Auth::logout();
            abort(403);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('owner_credential_version', $request->user()->credential_version);
        return redirect()->route('owner.products');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('owner.login');
    }
}
