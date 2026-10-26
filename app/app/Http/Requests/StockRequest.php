<?php

namespace App\Http\Requests;

class StockRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:variation,count'],
            'quantity' => ['required', 'integer', 'regex:/^-?[0-9]+$/D', 'between:-1000000,1000000'],
            'reason' => ['required', 'string', 'max:500'],
            'operation_id' => ['required', 'uuid'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
