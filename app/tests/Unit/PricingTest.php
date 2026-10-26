<?php

namespace Tests\Unit;

use App\Models\PriceTier;
use App\Models\Product;
use App\Services\PricingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PricingTest extends TestCase
{
    public static function limits(): array
    {
        return [[5, 3000, 15000], [6, 2500, 15000], [23, 2500, 57500], [24, 2000, 48000]];
    }

    #[DataProvider('limits')]
    public function test_limites_de_tramo(int $quantity, int $unit, int $subtotal): void
    {
        $product = new Product(['stock' => 40, 'unit_price' => 3000]);
        $product->setRelation('tiers', collect([
            new PriceTier(['minimum_quantity' => 6, 'unit_price' => 2500]),
            new PriceTier(['minimum_quantity' => 24, 'unit_price' => 2000]),
        ]));
        $quote = (new PricingService)->quote($product, $quantity);
        $this->assertSame($unit, $quote['unit_price']);
        $this->assertSame($subtotal, $quote['subtotal']);
        if ($quantity === 6) {
            $this->assertSame(18, $quote['next']['remaining']);
        }
    }

    public function test_no_sugiere_un_tramo_inalcanzable(): void
    {
        $product = new Product(['stock' => 10, 'unit_price' => 3000]);
        $product->setRelation('tiers', collect([new PriceTier(['minimum_quantity' => 24, 'unit_price' => 2000])]));
        $this->assertNull((new PricingService)->quote($product, 6)['next']);
    }
}
