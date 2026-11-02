<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATES = ['pendiente', 'preparacion', 'despachado', 'retirado', 'cancelado'];
    protected $guarded = [];
    protected $hidden = ['access_token', 'idempotency_key', 'nombre', 'telefono', 'correo', 'region', 'comuna', 'direccion'];

    protected function casts(): array
    {
        return ['monto_despacho' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function store() { return $this->belongsTo(Store::class); }
    public function scopeForStore($query, Store $store) { return $query->where('store_id', $store->id); }
    public function lines() { return $this->hasMany(OrderLine::class); }
    public function events() { return $this->hasMany(OrderEvent::class); }
}
