<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::updateOrCreate(['slug' => 'tecnologia-demo'], ['name' => 'Tecnología Demo', 'initials' => 'TD']);
        $items = [
            ['DEMO-C1', 'Cable USB-C · 1 metro', 'Cables', 40, 3000, true, 'cable', 'USB-C a USB-C. Ejemplo ficticio; verificar dispositivo antes de usar.', [[6, 2500], [24, 2000]]],
            ['DEMO-C2', 'Cable USB-C · 2 metros', 'Cables', 0, 4000, true, 'cable', 'USB-C a USB-C. Compatibilidad ficticia.', [[6, 3500]]],
            ['DEMO-A1', 'Cargador de pared USB-C', 'Cargadores', 20, 8000, true, 'charger', 'Puerto USB-C. Potencia y compatibilidad solo ilustrativas.', [[6, 7000]]],
            ['DEMO-S1', 'Soporte plegable de escritorio', 'Soportes', 30, 5000, true, 'stand', 'Celulares de ejemplo de 4 a 7 pulgadas; medidas ficticias.', [[6, 4000], [24, 3500]]],
            ['DEMO-F1', 'Funda transparente · modelo demo', 'Fundas', 15, 3500, true, 'case', 'Solo modelo ficticio DEMO-PHONE. No corresponde a un teléfono comercial.', [[6, 2800]]],
            ['DEMO-H1', 'Adaptador de prueba oculto', 'Adaptadores', 10, 2000, false, 'charger', 'Artículo ficticio no publicado.', []],
        ];
        foreach ($items as [$sku, $name, $category, $stock, $price, $published, $image, $compatibility, $tiers]) {
            $categoryRecord = $store->categories()->firstOrCreate(['name' => $category]);
            $product = $store->products()->updateOrCreate(['sku' => $sku], [
                'name' => $name, 'category' => $category, 'category_id' => $categoryRecord->id, 'stock' => $stock, 'unit_price' => $price,
                'published' => $published, 'image' => $image.'.svg', 'compatibility' => $compatibility,
                'description' => 'Accesorio de demostración para explorar compras por unidad y por cantidad. Datos, precios y existencias ficticios.',
            ]);
            foreach ($tiers as [$minimum, $unit]) {
                $product->tiers()->updateOrCreate(['minimum_quantity' => $minimum], ['unit_price' => $unit]);
            }
        }
    }
}
