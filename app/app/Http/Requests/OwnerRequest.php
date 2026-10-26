<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class OwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($id = $this->route('product')) {
            $product = $this->user()->store->products()->findOrFail($id);
            Gate::authorize('update', $product);
        }
        return $this->user()->ownsStore();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach (array_diff(array_keys($this->all()), array_merge(array_keys($this->rules()), ['_token', '_method'])) as $field) {
                $validator->errors()->add($field, 'Campo no permitido.');
            }
        });
    }
}
