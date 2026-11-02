<?php

namespace Tests\Feature;

use App\Models\{Order, OrderLine, Product, Store, User};
use App\Services\{OrderService, StockService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\GuardsDemoDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase, GuardsDemoDatabase {
        GuardsDemoDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->get('/');
        $this->withCookie(config('session.cookie'), session()->getId());
    }
    private function store(): Store { return Store::where('slug', 'tecnologia-demo')->firstOrFail(); }
    private function product(): Product { return $this->store()->products()->where('sku', 'DEMO-C1')->firstOrFail(); }
    private function data(): array { return ['nombre' => 'Comprador Ficticio', 'telefono' => '+56900000000', 'correo' => 'demo@example.test', 'modalidad' => 'retiro']; }
    private function review(int $quantity = 6): string
    {
        $this->post('/carro/DEMO-C1', ['quantity' => (string)$quantity])->assertRedirect('/carro');
        $this->get('/checkout/revisar')->assertOk()->assertSee('Confirmar pedido de demostración')->assertSee('name="_token"', false);
        return session('checkout.'.$this->store()->id.'.pending');
    }
    private function confirm(string $key, array $overrides = [])
    {
        return $this->post('/checkout/confirmar', array_replace($this->data(), ['idempotency_key' => $key], $overrides));
    }

    public function test_retiro_snapshot_precio_servidor_referencia_privada_y_reintentos(): void
    {
        $key = $this->review();
        $this->confirm($key, ['unit_price' => 1, 'store_id' => 999, 'quantity' => 999, 'product_id' => 999, 'stock_reservado' => 0])->assertRedirect();
        $order = Order::sole();
        $this->assertMatchesRegularExpression('/^OXM-\d{8}-[A-F0-9]{6}$/', $order->reference);
        $this->assertSame(6, $this->product()->stock_reservado);
        $this->assertSame(40, $this->product()->stock);
        $this->assertSame(15000, $order->lines->sole()->subtotal);
        $this->assertSame(2500, $order->lines->sole()->precio_unitario);
        $this->assertSame($this->store()->id, $order->store_id);
        $this->assertNull(session('carts.'.$this->store()->id));
        $this->assertNull(session('checkout.'.$this->store()->id.'.pending'));
        $this->get('/checkout/confirmacion/'.$order->reference)->assertOk()->assertSee($order->reference)->assertSee('Retiro en tienda')->assertSee('No hay un total definitivo')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post('/carro/DEMO-C1', ['quantity' => 1]);
        $this->confirm($key)->assertRedirect('/checkout/confirmacion/'.$order->reference);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_events', 1);
        $this->assertSame(6, $this->product()->stock_reservado);
        $this->assertSame(1, session('carts.'.$this->store()->id.'.'.$this->product()->id.'.quantity'));
        session()->forget('checkout');
        session()->save();
        $this->get('/checkout/confirmacion/'.$order->reference)->assertNotFound();
        $this->get('/pedido/seguimiento/'.$order->access_token)->assertOk()->assertSee('Comprador Ficticio')->assertDontSee('+56900000000')->assertDontSee('demo@example.test')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/pedido/seguimiento/'.$order->reference)->assertNotFound();
        $this->get('/pedido/seguimiento/no-existe')->assertNotFound();
        $this->product()->update(['unit_price' => 1234, 'name' => 'Nombre cambiado']);
        $this->assertSame('Cable USB-C · 1 metro', $order->lines()->sole()->nombre_producto);
        $this->assertSame(2500, $order->lines()->sole()->precio_unitario);
    }

    public function test_despacho_y_aceptacion_idempotente_sin_exponer_direccion(): void
    {
        $key = $this->review();
        $this->confirm($key, ['modalidad' => 'despacho', 'region' => 'Región ficticia', 'comuna' => 'Comuna ficticia', 'direccion' => 'Calle privada ficticia 123'])->assertRedirect();
        $order = Order::sole();
        $url = '/pedido/seguimiento/'.$order->access_token;
        $this->get($url)->assertOk()->assertSee('Despacho pendiente de cotización')->assertDontSee('Calle privada ficticia 123')->assertDontSee('Región ficticia');
        $this->post($url.'/aceptar-cotizacion')->assertStatus(422);
        $this->assertDatabaseCount('order_events', 1);
        $order->update(['estado_despacho' => 'cotizado', 'monto_despacho' => 4500]);
        $this->get($url)->assertSee('4.500')->assertSee('Aceptar cotización')->assertSee('No hay un total definitivo');
        $this->post($url.'/aceptar-cotizacion')->assertRedirect($url);
        $this->post($url.'/aceptar-cotizacion')->assertRedirect($url);
        $this->assertSame('aceptado', $order->fresh()->estado_despacho);
        $this->assertDatabaseCount('order_events', 2);
        $this->get($url)->assertSee('19.500')->assertSee('Total con despacho aceptado');
    }

    public static function invalidData(): array
    {
        return [
            [['nombre' => ''], 'nombre'], [['nombre' => ['array']], 'nombre'], [['nombre' => str_repeat('x', 161)], 'nombre'],
            [['telefono' => ''], 'telefono'], [['telefono' => str_repeat('1', 21)], 'telefono'],
            [['correo' => 'sin-arroba'], 'correo'], [['modalidad' => 'otra'], 'modalidad'],
            [['modalidad' => 'despacho'], 'direccion'], [['modalidad' => 'despacho', 'direccion' => 'Calle', 'comuna' => 'Comuna'], 'region'],
            [['modalidad' => 'despacho', 'direccion' => 'Calle', 'region' => 'Región'], 'comuna'],
            [['region' => str_repeat('x', 101)], 'region'], [['direccion' => str_repeat('x', 256)], 'direccion'],
            [['idempotency_key' => 'invalida'], 'idempotency_key'],
        ];
    }
    #[DataProvider('invalidData')]
    public function test_valida_contacto_y_direccion_sin_reservar(array $input, string $field): void
    {
        $this->confirm($this->review(), $input)->assertRedirect('/checkout/revisar')->assertSessionHasErrors($field);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $this->product()->stock_reservado);
        $this->get('/checkout/revisar')->assertOk();
    }

    public function test_clave_no_emitida_en_sesion_no_autoriza_pedido(): void
    {
        $this->review();
        $this->confirm((string)Str::uuid())->assertStatus(419);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_precio_cambiado_requiere_nueva_revision(): void
    {
        $key = $this->review();
        $this->product()->tiers()->where('minimum_quantity', 6)->update(['unit_price' => 2400]);
        $this->confirm($key)->assertRedirect('/checkout/revisar')->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $this->product()->stock_reservado);
        $this->get('/checkout/revisar')->assertSee('Cambió el precio')->assertSee('14.400');
        $this->confirm($key)->assertRedirect();
        $this->assertSame(14400, OrderLine::sole()->subtotal);
    }

    public static function unavailable(): array { return [[['published' => false]], [['archived_at' => '2026-10-26 10:00:00']], [['stock' => 5]], [['stock_reservado' => 35]]]; }
    #[DataProvider('unavailable')]
    public function test_revalida_publicacion_stock_y_reservas_sin_estado_parcial(array $change): void
    {
        $key = $this->review();
        $this->product()->update($change);
        $this->confirm($key)->assertRedirect('/checkout/revisar')->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
        $this->assertDatabaseCount('order_events', 0);
        $this->get('/checkout/revisar')->assertRedirect('/');
    }

    public function test_carro_vacio_y_disponible_publico_consideran_reservas(): void
    {
        $this->get('/checkout/revisar')->assertRedirect('/');
        $this->product()->update(['stock_reservado' => 40]);
        $this->get('/productos/DEMO-C1')->assertSee('Sin stock')->assertSee('disabled');
        $this->post('/carro/DEMO-C1', ['quantity' => 1])->assertSessionHasErrors();
        $this->get('/carro')->assertSee('Tu carro está vacío');
    }

    public function test_linea_invalida_no_reserva_otras_lineas_y_fallo_posterior_revierte_todo(): void
    {
        $key = $this->review(1);
        $this->post('/carro/DEMO-A1', ['quantity' => 1]);
        $this->get('/checkout/revisar');
        $other = Product::where('sku', 'DEMO-A1')->firstOrFail();
        $other->update(['published' => false]);
        $this->confirm($key)->assertSessionHasErrors('checkout');
        $this->assertSame(0, $this->product()->stock_reservado);
        $this->get('/checkout/revisar')->assertOk()->assertSee('dejó de estar disponible');
        // Fallo después de escribir líneas y reservas: la transacción debe revertirlas.
        $original = Order::getEventDispatcher();
        $dispatcher = clone $original;
        Order::setEventDispatcher($dispatcher);
        $dispatcher->listen('eloquent.creating: App\\Models\\OrderEvent', fn () => throw new \RuntimeException('Fallo inducido'));
        try {
            app(OrderService::class)->confirmar($this->store(), $this->data(), (string)Str::uuid(), [$this->product()->id => ['quantity' => 1, 'unit_price' => 3000]]);
            $this->fail('Debió fallar');
        } catch (\RuntimeException $error) { $this->assertSame('Fallo inducido', $error->getMessage()); }
        finally { Order::setEventDispatcher($original); }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
        $this->assertDatabaseCount('order_events', 0);
        $this->assertSame(0, $this->product()->stock_reservado);
    }

    public function test_aislamiento_tienda_en_lineas_idempotencia_seguimiento_y_cotizacion(): void
    {
        $this->confirm($this->review())->assertRedirect();
        $order = Order::sole();
        $other = Store::create(['slug' => 'otra', 'name' => 'Otra ficticia', 'initials' => 'OT']);
        try {
            app(OrderService::class)->confirmar($other, $this->data(), (string)Str::uuid(), [$this->product()->id => ['quantity' => 1, 'unit_price' => 3000]]);
            $this->fail('Producto ajeno admitido');
        } catch (\Illuminate\Validation\ValidationException $error) { $this->assertArrayHasKey('checkout', $error->errors()); }
        try {
            app(OrderService::class)->confirmar($other, $this->data(), $order->idempotency_key, []);
            $this->fail('Clave ajena admitida');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) { $this->assertSame(409, $error->getStatusCode()); }
        $order->update(['store_id' => $other->id, 'modalidad' => 'despacho', 'estado_despacho' => 'cotizado', 'monto_despacho' => 1000]);
        $this->get('/pedido/seguimiento/'.$order->access_token)->assertNotFound();
        $this->get('/checkout/confirmacion/'.$order->reference)->assertNotFound();
        $this->post('/pedido/seguimiento/'.$order->access_token.'/aceptar-cotizacion')->assertNotFound();
        $this->assertSame('cotizado', $order->fresh()->estado_despacho);
    }

    public function test_ajuste_y_conteo_no_bajan_del_reservado_y_ajuste_valido_es_idempotente(): void
    {
        $this->confirm($this->review());
        $user = User::factory()->create(['store_id' => $this->store()->id, 'role' => 'owner']);
        foreach ([['mode' => 'variation', 'quantity' => -35], ['mode' => 'count', 'quantity' => 5]] as $input) {
            try {
                app(StockService::class)->adjust($user, $this->product()->id, [...$input, 'version' => $this->product()->version, 'reason' => 'Prueba ficticia', 'operation_id' => (string)Str::uuid()]);
                $this->fail('Bajó del reservado');
            } catch (\Illuminate\Validation\ValidationException $error) { $this->assertArrayHasKey('quantity', $error->errors()); }
        }
        $data = ['mode' => 'count', 'quantity' => 6, 'version' => $this->product()->version, 'reason' => 'Conteo ficticio', 'operation_id' => (string)Str::uuid()];
        $a = app(StockService::class)->adjust($user, $this->product()->id, $data);
        $b = app(StockService::class)->adjust($user, $this->product()->id, $data);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(6, $this->product()->stock);
        $this->assertSame(6, $this->product()->stock_reservado);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_csrf_real_en_confirmacion_y_aceptacion(): void
    {
        $key = $this->review();
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, function ($app) {
            return new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\PreventRequestForgery {
                protected function runningUnitTests() { return false; }
            };
        });
        $csrf = session()->token();
        $this->confirm($key)->assertStatus(419);
        $this->confirm($key, ['_token' => 'invalido'])->assertStatus(419);
        $this->assertDatabaseCount('orders', 0);
        $this->confirm($key, ['_token' => $csrf])->assertRedirect();
        $order = Order::sole();
        $order->update(['modalidad' => 'despacho', 'estado_despacho' => 'cotizado', 'monto_despacho' => 1000]);
        $url = '/pedido/seguimiento/'.$order->access_token.'/aceptar-cotizacion';
        $this->post($url)->assertStatus(419);
        $this->post($url, ['_token' => 'invalido'])->assertStatus(419);
        $this->assertSame('cotizado', $order->fresh()->estado_despacho);
        $this->post($url, ['_token' => $csrf])->assertRedirect();
    }
}
