<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductRequest extends OwnerRequest
{
    protected function prepareForValidation(): void
    {
        // Los renglones vacíos permiten agregar/quitar tramos sin JavaScript.
        if (is_array($this->input('tiers'))) {
            $this->merge(['tiers' => array_values(array_filter($this->input('tiers'), fn ($tier) => ! is_array($tier) || ($tier['minimum_quantity'] ?? '') !== '' && ($tier['minimum_quantity'] ?? null) !== null || ($tier['unit_price'] ?? '') !== '' && ($tier['unit_price'] ?? null) !== null))]);
        }
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/D', Rule::unique('products')->where('store_id', $this->user()->store_id)->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'compatibility' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('store_id', $this->user()->store_id)],
            'unit_price' => ['required', 'integer', 'regex:/^[1-9][0-9]*$/D', 'between:1,1000000000'],
            'version' => [$this->route('product') ? 'required' : 'nullable', 'integer', 'min:1'],
            'tiers' => ['present', 'array', 'max:20'],
            'tiers.*' => ['array:minimum_quantity,unit_price'],
            'tiers.*.minimum_quantity' => ['required', 'integer', 'regex:/^[0-9]+$/D', 'between:2,1000000', 'distinct'],
            'tiers.*.unit_price' => ['required', 'integer', 'regex:/^[1-9][0-9]*$/D', 'between:1,1000000000'],
            'photo' => ['nullable', 'file', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        parent::withValidator($validator);
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) { return; }
            $previousPrice = (int) $this->input('unit_price');
            $previousMinimum = 1;
            foreach ($this->input('tiers', []) as $index => $tier) {
                if ((int) $tier['minimum_quantity'] <= $previousMinimum || (int) $tier['unit_price'] >= $previousPrice) {
                    $validator->errors()->add('tiers.'.$index.'.unit_price', 'Ordena umbrales de menor a mayor y reduce el precio en cada tramo.');
                }
                $previousPrice = (int) $tier['unit_price'];
                $previousMinimum = (int) $tier['minimum_quantity'];
            }
        });
    }
}
