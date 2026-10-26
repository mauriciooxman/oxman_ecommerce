<?php

namespace App\Services;

use App\Models\Product;
use InvalidArgumentException;

class PricingService
{
    public function quote(Product $product, int $quantity): array
    {
        if ($quantity < 1 || $quantity > $product->stock) {
            throw new InvalidArgumentException('La cantidad debe estar entre 1 y las existencias disponibles.');
        }
        $price = $product->unit_price;
        $minimum = 1;
        $next = null;
        foreach ($product->tiers as $tier) {
            if ($quantity >= $tier->minimum_quantity) {
                $price = $tier->unit_price;
                $minimum = $tier->minimum_quantity;
            } elseif ($tier->minimum_quantity <= $product->stock && $next === null) {
                $next = ['remaining' => $tier->minimum_quantity - $quantity,
                    'quantity' => $tier->minimum_quantity, 'unit_price' => $tier->unit_price];
            }
        }
        return ['unit_price' => $price, 'subtotal' => $price * $quantity,
            'minimum' => $minimum, 'next' => $next];
    }
}
