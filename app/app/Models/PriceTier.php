<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceTier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['minimum_quantity' => 'integer', 'unit_price' => 'integer'];
    }
}
