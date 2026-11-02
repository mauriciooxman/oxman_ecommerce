<?php

namespace Tests\Feature\Owner;

use App\Models\{Order, Product, Store, User};
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\GuardsDemoDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase, GuardsDemoDatabase {
        GuardsDemoDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }
    private User $owner;
    private Store $store;
    private Product $product;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->store = Store::where('slug', 'tecnologia-demo')->firstOrFail();
        $this->product = $this->store->products()->where('sku', 'DEMO-C1')->firstOrFail();
        $this->owner = User::factory()->create(['store_id' => $this->store->id, 'role' => 'owner', 'credential_version' => 1]);
        $this->actingAs($this->owner)->withSession(['owner_credential_version' => 1]);
    }
    private function order(string $mode = 'retiro'): Order
    {
        return app(OrderService::class)->confirmar($this->store,
            ['nombre' => 'Comprador de ejemplo', 'telefono' => '000000000', 'correo' => 'ejemplo@example.test', 'modalidad' => $mode,
                'region' => 'Región ficticia', 'comuna' => 'Comuna ficticia', 'direccion' => 'Dirección ficticia'],
            (string)Str::uuid(), [$this->product->id => ['quantity' => 2, 'unit_price' => 3000]]);
    }
    private function postAction(Order $order, string $action, array $data = [])
    {
        return $this->from('/dueno/pedidos/'.$order->id)->post('/dueno/pedidos/'.$order->id.'/'.$action, $data);
    }
    private function prepared(string $mode = 'retiro'): Order
    {
        $order = $this->order($mode);
        $this->postAction($order, 'cotizar-despacho', ['monto' => $mode === 'retiro' ? 0 : 1500])->assertRedirect();
        if ($mode === 'despacho') { $this->post('/pedido/seguimiento/'.$order->access_token.'/aceptar-cotizacion')->assertRedirect(); }
        $this->postAction($order, 'confirmar-pago')->assertRedirect();
        $this->postAction($order, 'preparacion')->assertRedirect();
        return $order->fresh();
    }

    public function test_listado_filtro_detalle_snapshot_historial_y_fechas(): void
    {
        $first = $this->order();
        $second = $this->order();
        $second->update(['estado_pedido' => 'preparacion']);
        $first->update(['created_at' => '2026-10-26 15:00:00']);
        $this->product->update(['name' => 'Nombre nuevo', 'unit_price' => 9000]);
        $this->get('/dueno/pedidos')->assertOk()->assertSee($first->reference)->assertSee($second->reference)->assertSee('hace')->assertSee('26/10/2026 12:00');
        $this->get('/dueno/pedidos?estado=preparacion')->assertOk()->assertSee($second->reference)->assertDontSee($first->reference);
        $this->get('/dueno/pedidos?estado=cancelado')->assertSee('No hay pedidos');
        $this->get('/dueno/pedidos?estado=invalido')->assertSessionHasErrors('estado');
        $this->get('/dueno/pedidos/'.$first->id)->assertOk()->assertSee('Cable USB-C · 1 metro')->assertSee('6.000')->assertDontSee('Nombre nuevo')
            ->assertSee('000000000')->assertSee('Historial del pedido')->assertSee('name="_token"', false)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_permisos_y_aislamiento_en_todas_las_rutas_y_servicio(): void
    {
        $order = $this->order();
        $other = Store::create(['slug' => 'otra-3b', 'name' => 'Otra tienda', 'initials' => 'OT']);
        $outsider = User::factory()->create(['store_id' => $other->id, 'role' => 'owner', 'credential_version' => 1]);
        $this->actingAs($outsider)->withSession(['owner_credential_version' => 1]);
        $this->get('/dueno/pedidos')->assertOk()->assertDontSee($order->reference);
        $this->get('/dueno/pedidos/'.$order->id)->assertNotFound();
        foreach (['cancelar', 'cotizar-despacho', 'confirmar-pago', 'preparacion', 'entregar'] as $action) {
            $this->postAction($order, $action, ['motivo' => 'Ejemplo', 'monto' => 0])->assertNotFound();
        }
        try { app(OrderService::class)->cancelar($order, $outsider, 'Ejemplo'); $this->fail('Acceso ajeno'); }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $error) { $this->addToAssertionCount(1); }
        $outsider->forceFill(['role' => 'viewer'])->save();
        $this->get('/dueno/pedidos')->assertForbidden();
        $this->postAction($order, 'entregar')->assertForbidden();
        $this->actingAs($this->owner)->withSession(['owner_credential_version' => 1]);
        $this->postAction($order, 'confirmar-pago', ['store_id' => $other->id])->assertSessionHasErrors('store_id');
        $this->app['auth']->forgetGuards();
        $this->get('/dueno/pedidos')->assertRedirect('/dueno/ingresar');
        $this->postAction($order, 'entregar')->assertRedirect('/dueno/ingresar');
        $this->assertSame('pendiente', $order->fresh()->estado_pedido);
        $this->assertSame(2, $this->product->fresh()->stock_reservado);
    }

    public function test_despacho_requiere_aceptacion_del_comprador_y_entrega_una_vez(): void
    {
        $order = $this->order('despacho');
        $this->postAction($order, 'cotizar-despacho', ['monto' => 1500])->assertRedirect();
        $this->assertSame('cotizado', $order->fresh()->estado_despacho);
        $this->assertSame('pendiente', $order->fresh()->estado_pago);
        $this->get('/pedido/seguimiento/'.$order->access_token)->assertSee('1.500')->assertSee('Aceptar cotización');
        $this->postAction($order, 'preparacion')->assertRedirect();
        $this->postAction($order, 'confirmar-pago')->assertRedirect();
        $this->postAction($order, 'entregar')->assertStatus(422);
        $this->assertSame(40, $this->product->fresh()->stock);
        $this->post('/pedido/seguimiento/'.$order->access_token.'/aceptar-cotizacion')->assertRedirect();
        $this->postAction($order, 'entregar')->assertRedirect();
        $this->assertSame('despachado', $order->fresh()->estado_pedido);
        $this->assertSame('despachado_registrado', $order->fresh()->estado_despacho);
        $this->assertSame('recibido', $order->fresh()->estado_pago);
        $this->assertSame(38, $this->product->fresh()->stock);
        $this->assertSame(0, $this->product->fresh()->stock_reservado);
        $this->assertSame(2, $this->product->fresh()->version);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->postAction($order, 'entregar')->assertStatus(409);
        $this->postAction($order, 'cancelar', ['motivo' => 'Muy tarde'])->assertStatus(422);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(7, $order->events()->count());
        $this->assertSame(38, $this->product->fresh()->stock);
        $this->assertSame(3000, $order->lines()->sole()->precio_unitario);
    }

    public function test_retiro_sin_costo_aceptado_y_entregado(): void
    {
        $order = $this->prepared();
        $this->assertSame(0, $order->monto_despacho);
        $this->assertSame('aceptado', $order->estado_despacho);
        $this->postAction($order, 'entregar')->assertRedirect();
        $this->assertSame('retirado', $order->fresh()->estado_pedido);
        $this->assertSame('retirado_registrado', $order->fresh()->estado_despacho);
        $this->assertSame(38, $this->product->fresh()->stock);
        $this->assertSame(0, $this->product->fresh()->stock_reservado);
        $this->assertSame(6, $order->events()->count());
        $this->get('/dueno/pedidos/'.$order->id)->assertSee('Pedido cerrado')->assertDontSee('Cancelar y liberar reserva');
    }

    public function test_cancelar_pagado_conserva_pago_y_fisico_y_libera_solo_su_reserva(): void
    {
        $order = $this->prepared();
        $other = $this->order();
        $this->postAction($order, 'cancelar', ['motivo' => 'Cancelación ficticia'])->assertRedirect();
        $this->assertSame('cancelado', $order->fresh()->estado_pedido);
        $this->assertSame('recibido', $order->fresh()->estado_pago);
        $this->assertSame('aceptado', $order->fresh()->estado_despacho);
        $this->assertSame('Cancelación ficticia', $order->fresh()->motivo_cancelacion);
        $this->assertSame(40, $this->product->fresh()->stock);
        $this->assertSame(2, $this->product->fresh()->stock_reservado);
        $this->assertDatabaseCount('stock_movements', 0);
        $events = $order->events()->count();
        $this->postAction($order, 'cancelar', ['motivo' => 'Reintento'])->assertStatus(409);
        $this->postAction($order, 'entregar')->assertStatus(422);
        $this->post('/pedido/seguimiento/'.$order->access_token.'/aceptar-cotizacion')->assertStatus(422);
        $this->assertSame($events, $order->events()->count());
        $this->assertSame('pendiente', $other->fresh()->estado_pedido);
        $this->assertSame(2, $this->product->fresh()->stock_reservado);
    }

    public static function invalidQuotes(): array
    {
        return [[[], 'monto'], [['monto' => -1], 'monto'], [['monto' => '1.5'], 'monto'], [['monto' => ['x']], 'monto'], [['monto' => 4294967296], 'monto'], [['monto' => 1000, 'tipo_retiro' => 'x'], 'tipo_retiro'], [['monto' => 1000, 'estado_pago' => 'recibido'], 'estado_pago']];
    }
    #[DataProvider('invalidQuotes')]
    public function test_valida_cotizacion_sin_mutar(array $input, string $field): void
    {
        $order = $this->order('despacho');
        $this->postAction($order, 'cotizar-despacho', $input)->assertSessionHasErrors($field);
        $this->assertSame('pendiente_cotizacion', $order->fresh()->estado_despacho);
        $this->assertNull($order->fresh()->monto_despacho);
        $this->assertSame(1, $order->events()->count());
    }

    public function test_no_convierte_despacho_en_retiro_y_motivo_obligatorio(): void
    {
        $shipping = $this->order('despacho');
        $this->postAction($shipping, 'cotizar-despacho', ['monto' => 0])->assertStatus(422);
        $this->postAction($shipping, 'cotizar-despacho', ['tipo_retiro' => 1])->assertStatus(422);
        $pickup = $this->order();
        $this->postAction($pickup, 'cotizar-despacho', ['monto' => 1000])->assertStatus(422);
        $this->postAction($pickup, 'cotizar-despacho', ['tipo_retiro' => 1])->assertRedirect();
        foreach (['', '   ', str_repeat('x', 501), ['x']] as $reason) {
            $this->postAction($shipping, 'cancelar', ['motivo' => $reason])->assertSessionHasErrors('motivo');
        }
        $this->assertSame('pendiente', $shipping->fresh()->estado_pedido);
    }

    public static function invalidTransitions(): array
    {
        return [
            ['entregar', 'pendiente', 'recibido', 'aceptado', 422], ['entregar', 'preparacion', 'pendiente', 'aceptado', 422],
            ['entregar', 'preparacion', 'recibido', 'cotizado', 422], ['entregar', 'cancelado', 'recibido', 'aceptado', 422],
            ['entregar', 'despachado', 'recibido', 'despachado_registrado', 409], ['entregar', 'retirado', 'recibido', 'retirado_registrado', 409],
            ['cancelar', 'cancelado', 'pendiente', 'pendiente_cotizacion', 409], ['cancelar', 'despachado', 'recibido', 'despachado_registrado', 422],
            ['cancelar', 'retirado', 'recibido', 'retirado_registrado', 422], ['confirmar-pago', 'preparacion', 'recibido', 'aceptado', 409],
            ['confirmar-pago', 'cancelado', 'pendiente', 'cotizado', 422], ['confirmar-pago', 'despachado', 'pendiente', 'aceptado', 422],
            ['preparacion', 'preparacion', 'pendiente', 'aceptado', 422], ['preparacion', 'cancelado', 'pendiente', 'cotizado', 422],
            ['cotizar-despacho', 'pendiente', 'pendiente', 'cotizado', 422], ['cotizar-despacho', 'cancelado', 'pendiente', 'pendiente_cotizacion', 422],
        ];
    }
    #[DataProvider('invalidTransitions')]
    public function test_transicion_invalida_no_cambia_estados_stock_ni_historial(string $action, string $state, string $payment, string $shipping, int $status): void
    {
        $order = $this->order();
        $order->update(['estado_pedido' => $state, 'estado_pago' => $payment, 'estado_despacho' => $shipping]);
        $before = $order->fresh()->getAttributes();
        $this->postAction($order, $action, in_array($action, ['cancelar', 'cotizar-despacho'], true) ? ($action === 'cancelar' ? ['motivo' => 'Ejemplo'] : ['monto' => 0]) : [])->assertStatus($status);
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame(40, $this->product->fresh()->stock);
        $this->assertSame(2, $this->product->fresh()->stock_reservado);
        $this->assertSame(1, $order->events()->count());
    }

    public function test_inconsistencia_en_segunda_linea_revierte_entrega_y_auditoria(): void
    {
        $second = $this->store->products()->where('sku', 'DEMO-A1')->firstOrFail();
        $order = app(OrderService::class)->confirmar($this->store, ['nombre' => 'Ficticio', 'telefono' => '0000', 'modalidad' => 'retiro'], (string)Str::uuid(), [
            $this->product->id => ['quantity' => 2, 'unit_price' => 3000], $second->id => ['quantity' => 1, 'unit_price' => 8000],
        ]);
        $order->update(['estado_pedido' => 'preparacion', 'estado_pago' => 'recibido', 'estado_despacho' => 'aceptado']);
        $second->refresh()->update(['stock_reservado' => 0]);
        $this->postAction($order, 'entregar')->assertStatus(422);
        $this->assertSame(40, $this->product->fresh()->stock);
        $this->assertSame(2, $this->product->fresh()->stock_reservado);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->postAction($order, 'cancelar', ['motivo' => 'Ejemplo'])->assertStatus(422);
        $this->assertSame(2, $this->product->fresh()->stock_reservado);
        $this->assertSame('preparacion', $order->fresh()->estado_pedido);
        $this->assertSame(1, $order->events()->count());
    }

    public function test_csrf_real_de_todas_las_acciones(): void
    {
        $order = $this->order();
        $this->get('/dueno/pedidos/'.$order->id)->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, function ($app) {
            return new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\PreventRequestForgery {
                protected function runningUnitTests() { return false; }
            };
        });
        foreach (['cancelar', 'cotizar-despacho', 'confirmar-pago', 'preparacion', 'entregar'] as $action) {
            $this->postAction($order, $action)->assertStatus(419);
            $this->postAction($order, $action, ['_token' => 'invalido'])->assertStatus(419);
        }
        $this->postAction($order, 'cancelar', ['_token' => session()->token(), 'motivo' => 'Ejemplo'])->assertRedirect();
        $this->assertSame(0, $this->product->fresh()->stock_reservado);
    }
}
