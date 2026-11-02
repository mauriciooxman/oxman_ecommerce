<?php

namespace App\Http\Requests\Owner;

class QuoteShippingRequest extends CancelOrderRequest
{
    public function rules(): array
    {
        return ['monto' => ['nullable', 'required_unless:tipo_retiro,1', 'integer', 'regex:/^[0-9]+$/D', 'min:0', 'max:4294967295'], 'tipo_retiro' => ['sometimes', 'boolean']];
    }
}
