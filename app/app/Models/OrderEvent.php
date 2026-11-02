<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    protected $guarded = [];
    public const UPDATED_AT = null;
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('El historial solo admite nuevos eventos.'));
        static::deleting(fn () => throw new \LogicException('El historial solo admite nuevos eventos.'));
    }
    public function order() { return $this->belongsTo(Order::class); }
}
