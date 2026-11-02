<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    protected $guarded = [];
    public const UPDATED_AT = null;
    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'precio_unitario' => 'integer', 'subtotal' => 'integer'];
    }
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('El snapshot del pedido es inmutable.'));
        static::deleting(fn () => throw new \LogicException('El snapshot del pedido es inmutable.'));
    }
    public function order() { return $this->belongsTo(Order::class); }
}
