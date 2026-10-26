<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Support\GuardsDemoDatabase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase, GuardsDemoDatabase {
        GuardsDemoDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function key(): string
    {
        return 'carts.'.Store::where('slug', 'tecnologia-demo')->firstOrFail()->id;
    }

    public function test_catalogo_ficha_busqueda_y_vacios(): void
    {
        $this->get('/')->assertOk()->assertSee('DEMO-C1')->assertSee('Sin stock')->assertDontSee('DEMO-H1');
        $this->get('/?q=Cable&category=Cables')->assertOk()->assertSee('DEMO-C1')->assertDontSee('DEMO-A1');
        $this->get('/?q=imposible')->assertOk()->assertSee('No encontramos productos')->assertSee('Volver al catálogo');
        $this->get('/productos/DEMO-C1')->assertOk()->assertSee('Compatibilidad de ejemplo')->assertSee('2.000');
        $this->get('/carro')->assertOk()->assertSee('Tu carro está vacío')->assertSee('Volver al catálogo');
    }

    public function test_consolida_sku_recalcula_y_reduce_cantidad(): void
    {
        $id = Product::where('sku', 'DEMO-C1')->firstOrFail()->id;
        $this->post('/carro/DEMO-C1', ['quantity' => '5', 'unit_price' => 1])->assertRedirect('/carro');
        $this->assertSame(['quantity' => 5, 'unit_price' => 3000], session($this->key().'.'.$id));
        $this->post('/carro/DEMO-C1', ['quantity' => '1', 'store_id' => 999])->assertRedirect('/carro');
        $this->get('/carro')->assertSee('15.000')->assertSee('18 unidades')->assertSee('2.000 c/u');
        $this->assertCount(1, session($this->key()));
        $this->patch('/carro/DEMO-C1', ['quantity' => '23'])->assertRedirect('/carro');
        $this->get('/carro')->assertSee('57.500');
        $this->patch('/carro/DEMO-C1', ['quantity' => '24'])->assertRedirect('/carro');
        $this->get('/carro')->assertSee('48.000');
        $this->patch('/carro/DEMO-C1', ['quantity' => '5'])->assertRedirect('/carro');
        $this->assertSame(3000, session($this->key().'.'.$id.'.unit_price'));
        $this->assertSame(40, Product::findOrFail($id)->stock);
        $this->get('/carro')->assertSee('Despacho pendiente de cotización')->assertSee('No hay un total definitivo');
        $this->delete('/carro/DEMO-C1')->assertRedirect('/carro');
        $this->get('/carro')->assertSee('Tu carro está vacío');
    }

    public static function invalidQuantities(): array
    {
        return [['0'], ['-1'], ['1.5'], ['texto'], ['41'], ['9999999999999999999'], [['6']]];
    }

    #[DataProvider('invalidQuantities')]
    public function test_rechaza_cantidad_y_conserva_carro(mixed $invalid): void
    {
        $this->post('/carro/DEMO-C1', ['quantity' => '5']);
        $saved = session($this->key());
        $this->from('/carro')->patch('/carro/DEMO-C1', ['quantity' => $invalid])->assertSessionHasErrors('quantity.'.Product::where('sku', 'DEMO-C1')->firstOrFail()->id)->assertRedirect('/carro');
        $this->assertSame($saved, session($this->key()));
        $this->from('/productos/DEMO-C1')->post('/carro/DEMO-C1', ['quantity' => $invalid])->assertSessionHasErrors('quantity.'.Product::where('sku', 'DEMO-C1')->firstOrFail()->id);
        $this->assertSame($saved, session($this->key()));
    }

    public function test_oculto_agotado_y_sku_ajeno_no_se_agregan(): void
    {
        $this->get('/productos/DEMO-H1')->assertNotFound();
        $this->post('/carro/DEMO-H1', ['quantity' => '1'])->assertNotFound();
        $this->post('/carro/DEMO-C2', ['quantity' => '1'])->assertSessionHasErrors('quantity.'.Product::where('sku', 'DEMO-C2')->firstOrFail()->id);
        $other = Store::create(['slug' => 'otra', 'name' => 'Otra', 'initials' => 'OT']);
        $copy = Product::where('sku', 'DEMO-C1')->firstOrFail()->replicate();
        $copy->store_id = $other->id;
        $copy->sku = 'AJENO-1';
        $copy->save();
        $this->post('/carro/AJENO-1', ['quantity' => '1'])->assertNotFound();
        $this->patch('/carro/AJENO-1', ['quantity' => '1'])->assertNotFound();
        $this->delete('/carro/AJENO-1')->assertNotFound();
        $this->get('/productos/AJENO-1')->assertNotFound();
    }

    public function test_no_mezcla_skus_y_conserva_carro_en_recarga(): void
    {
        $this->post('/carro/DEMO-C1', ['quantity' => '5']);
        $this->post('/carro/DEMO-A1', ['quantity' => '1']);
        $this->get('/carro')->assertSee('23.000');
        $this->get('/carro')->assertSee('23.000');
        $id = Product::where('sku', 'DEMO-C1')->firstOrFail()->id;
        $this->assertSame(3000, session($this->key().'.'.$id.'.unit_price'));
    }

    public function test_cambio_catalogo_revalida_precio_y_disponibilidad(): void
    {
        $this->post('/carro/DEMO-C1', ['quantity' => '6']);
        $product = Product::where('sku', 'DEMO-C1')->firstOrFail();
        $product->tiers()->where('minimum_quantity', 6)->update(['unit_price' => 2400]);
        $this->get('/carro')->assertSee('Cambió el precio')->assertSee('14.400');
        $product->update(['stock' => 5]);
        $this->get('/carro')->assertSee('dejó de estar disponible')->assertSee('Tu carro está vacío');
        $product->update(['stock' => 40]);
        $this->post('/carro/DEMO-C1', ['quantity' => '6']);
        $product->update(['published' => false]);
        $this->get('/carro')->assertSee('dejó de estar disponible')->assertSee('Tu carro está vacío');
    }

    public function test_sesiones_y_tiendas_independientes(): void
    {
        $this->post('/carro/DEMO-C1', ['quantity' => '6']);
        $saved = session($this->key());
        $this->withSession(['carts' => []])->get('/carro')->assertSee('Tu carro está vacío');
        $this->withSession(['carts' => [Store::firstOrFail()->id => $saved, 999 => ['otra' => 'intacta']]])->get('/carro')->assertSee('15.000');
        $this->assertSame(['otra' => 'intacta'], session('carts.999'));
    }

    public function test_formularios_csrf_y_controles_etiquetados(): void
    {
        $this->get('/productos/DEMO-C1')->assertSee('name="_token"', false)->assertSee('for="qty-'.Product::where('sku', 'DEMO-C1')->firstOrFail()->id.'"', false)->assertSee('step="1"', false);
        $this->post('/carro/DEMO-C1', ['quantity' => '1']);
        $this->get('/carro')->assertSee('name="_token"', false)->assertSee('name="_method"', false);
    }

    public function test_array_rechazado_retorna_a_ficha_sin_error_de_render(): void
    {
        $this->followingRedirects()->from('/productos/DEMO-C1')->post('/carro/DEMO-C1', ['quantity' => ['6']])
            ->assertOk()->assertSee('ingresa una cantidad entera mayor que cero.');
    }

    public function test_filtros_cero_categoria_sola_y_combinacion_incompatible(): void
    {
        Product::where('sku', 'DEMO-A1')->update(['name' => 'Cargador 0', 'category' => '0']);
        $this->get('/?q=0')->assertOk()->assertSee('DEMO-A1')->assertDontSee('DEMO-C1');
        $this->get('/?category=0')->assertOk()->assertSee('DEMO-A1')->assertDontSee('DEMO-C1');
        $this->get('/?category=Cables')->assertOk()->assertSee('DEMO-C1')->assertDontSee('DEMO-A1');
        $this->get('/?q=0&category=Cables')->assertOk()->assertSee('No encontramos productos');
    }

    public function test_error_de_cantidad_solo_marca_la_linea_enviada(): void
    {
        // MakesHttpRequests no conserva cookies de respuesta automáticamente.
        // Reutilizar la sesión del GET inicial reproduce el navegador al redirigir.
        $this->get('/')->assertOk();
        $sessionId = session()->getId();
        $this->withCookie(config('session.cookie'), $sessionId);
        $this->post('/carro/DEMO-C1', ['quantity' => '5']);
        $this->post('/carro/DEMO-A1', ['quantity' => '1']);
        $saved = session($this->key());
        $id = Product::where('sku', 'DEMO-C1')->firstOrFail()->id;
        $otherId = Product::where('sku', 'DEMO-A1')->firstOrFail()->id;
        $this->from('/carro')->patch('/carro/DEMO-C1', ['quantity' => '0'])
            ->assertRedirect('/carro')->assertSessionHasErrors('quantity.'.$id);
        $response = $this->get('/carro')->assertOk()->assertSee('href="#qty-'.$id.'"', false);
        $this->assertSame($sessionId, session()->getId());
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'aria-invalid="true"'));
        $this->assertStringContainsString('id="qty-'.$otherId.'" type="number" name="quantity" min="1" max="20" step="1" value="1" aria-invalid="false"', $html);
        $this->assertStringContainsString('id="qty-error-'.$otherId.'" class="field-error"></p>', $html);
        $this->assertSame($saved, session($this->key()));
    }

    public function test_stock_acumulado_no_permite_cuarenta_mas_uno(): void
    {
        $this->post('/carro/DEMO-C1', ['quantity' => '40'])->assertRedirect('/carro');
        $saved = session($this->key());
        $id = Product::where('sku', 'DEMO-C1')->firstOrFail()->id;
        $this->from('/productos/DEMO-C1')->post('/carro/DEMO-C1', ['quantity' => '1'])
            ->assertRedirect('/productos/DEMO-C1')->assertSessionHasErrors('quantity.'.$id);
        $this->assertSame($saved, session($this->key()));
    }

    public function test_tramo_alcanzable_referencia_neutral_y_unidad_faltante(): void
    {
        $this->get('/?q=DEMO-C2')->assertOk()->assertDontSee('Desde 6 unidades:');
        $this->get('/productos/DEMO-C1')->assertOk()->assertSee('Precio de referencia')->assertDontSee('Aplicado');
        $this->post('/carro/DEMO-C1', ['quantity' => '5']);
        $this->get('/carro')->assertOk()->assertSee('Te falta')->assertSee('1 unidad')->assertDontSee('Te faltan');
    }

    public function test_bloqueo_de_sesion_usa_archivos_locales(): void
    {
        $manager = app('session');
        $this->assertTrue($manager->shouldBlock());
        $this->assertSame('file', $manager->blockDriver());
        $lock = app('cache')->store($manager->blockDriver())->lock('test-session-'.bin2hex(random_bytes(8)), 5);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_csrf_real_rechaza_tokens_ausentes_e_incorrectos_en_cada_mutacion(): void
    {
        // Solo elimina el atajo de tests: conserva handle() y tokensMatch() reales.
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, function ($app) {
            return new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\PreventRequestForgery {
                protected function runningUnitTests() { return false; }
            };
        });
        $token = str_repeat('a', 40);
        $this->withSession(['_token' => $token]);
        $this->post('/carro/DEMO-C1', ['quantity' => '5', '_token' => $token])->assertRedirect('/carro');
        foreach (['POST', 'PATCH', 'DELETE'] as $method) {
            $saved = session($this->key());
            foreach ([[], ['_token' => 'incorrecto']] as $input) {
                $this->call($method, '/carro/DEMO-C1', ['quantity' => '1', ...$input])->assertStatus(419);
                $this->assertSame($saved, session($this->key()));
            }
            $this->call($method, '/carro/DEMO-C1', ['quantity' => '1', '_token' => $token])->assertRedirect('/carro');
            $this->assertNotSame($saved, session($this->key()));
        }
    }
}
