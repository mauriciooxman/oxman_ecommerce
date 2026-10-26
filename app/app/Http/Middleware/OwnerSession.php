<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OwnerSession
{
    public function handle(Request $request, Closure $next, string $access = 'private')
    {
        app()->setLocale('es');
        abort_unless(in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true)
            && in_array($request->getHost(), ['127.0.0.1', 'localhost', '[::1]', '::1'], true), 404);
        if ($request->exists('store_id')) {
            throw ValidationException::withMessages(['store_id' => 'La tienda se determina desde la sesión del dueño.']);
        }
        if ($access === 'private') {
            if (! Auth::check()) {
                return redirect()->route('owner.login');
            }
            $request->user()->refresh()->load('store');
            if ((int) $request->session()->get('owner_credential_version') !== (int) $request->user()->credential_version) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('owner.login');
            }
            abort_unless($request->user()->ownsStore(), 403);
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        return $response;
    }
}
