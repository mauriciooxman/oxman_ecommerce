<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'quantity' => 'integer', 'delta' => 'integer', 'before' => 'integer', 'after' => 'integer'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
