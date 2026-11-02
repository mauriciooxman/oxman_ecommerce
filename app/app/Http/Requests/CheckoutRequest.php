<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    protected $redirectRoute = 'checkout.review';
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'nombre' => ['required', 'string', 'max:160'],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:254'],
            'modalidad' => ['required', 'in:retiro,despacho'],
            'region' => ['required_if:modalidad,despacho', 'nullable', 'string', 'max:100'],
            'comuna' => ['required_if:modalidad,despacho', 'nullable', 'string', 'max:100'],
            'direccion' => ['required_if:modalidad,despacho', 'nullable', 'string', 'max:255'],
        ];
    }
}
