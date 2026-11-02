<?php

namespace App\Http\Requests\Owner;

use App\Http\Requests\OwnerRequest;
use App\Models\Order;

class CancelOrderRequest extends OwnerRequest
{
    public function authorize(): bool
    {
        Order::forStore($this->user()->store)->findOrFail($this->route('order'));
        return $this->user()->ownsStore();
    }
    public function rules(): array { return ['motivo' => ['required', 'string', 'max:500']]; }
}
