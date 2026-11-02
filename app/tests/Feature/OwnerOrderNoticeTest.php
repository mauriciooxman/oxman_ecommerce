<?php

namespace Tests\Feature;

use App\Mail\NewOwnerOrder;
use App\Models\{Order, Product, Store, User};
use App\Services\{OrderService, OwnerOrderNotice};
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\{Artisan, DB, Event, Log, Mail};
use Illuminate\Support\Str;
use Tests\Support\GuardsDemoDatabase;
use Tests\TestCase;

class OwnerOrderNoticeTest extends TestCase
{
    use GuardsDemoDatabase;
    private Store $store;
    private Product $product;
    private string $capture;

    protected function setUp(): void
    {
        parent::setUp();
        // Commits reales: sin transacción envolvente de RefreshDatabase.
        $this->beforeRefreshingDatabase();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seed();
        $this->store = Store::where('slug', 'tecnologia-demo')->firstOrFail();
        $this->product = $this->store->products()->where('sku', 'DEMO-C1')->firstOrFail();
        $this->capture = storage_path('framework/testing/notice-'.Str::uuid().'.log');
        config(['logging.channels.order_notices.path' => $this->capture]);
        config(['owner_orders.recipients.tecnologia-demo' => 'dueno-configurado@example.test']);
    }
    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) { DB::rollBack(); }
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }
    private function create(?string $key = null, ?Store $store = null, ?Product $product = null): Order
    {
        return app(OrderService::class)->confirmar($store ?? $this->store,
            ['nombre' => 'COMPRADOR-PRIVADO', 'telefono' => 'TELEFONO-PRIVADO', 'correo' => 'comprador-privado@example.test',
                'modalidad' => 'despacho', 'region' => 'REGION-PRIVADA', 'comuna' => 'COMUNA-PRIVADA', 'direccion' => 'DIRECCION-PRIVADA',
                'notification_email' => 'inyectado@example.test'], $key ?? (string)Str::uuid(),
            [($product ?? $this->product)->id => ['quantity' => 2, 'unit_price' => 3000]]);
    }
    public function test_aviso_solo_despues_del_commit_exterior_y_reintento_no_duplica(): void
    {
        $observed = 0;
        config(['database.connections.notice_probe' => config('database.connections.mysql'), 'mail.default' => 'smtp']);
        Event::listen(MessageSending::class, function () use (&$observed) {
            $this->assertSame(0, DB::transactionLevel());
            $this->assertSame(1, DB::connection('notice_probe')->table('orders')->count());
            $this->assertSame(1, DB::connection('notice_probe')->table('order_lines')->count());
            $this->assertSame(2, (int)DB::connection('notice_probe')->table('products')->where('id', $this->product->id)->value('stock_reservado'));
            $observed++;
        });
        DB::beginTransaction();
        $key = (string)Str::uuid();
        $order = $this->create($key);
        $this->create($key);
        $this->assertSame(0, $observed);
        $this->assertFalse(is_file($this->capture));
        $this->assertDatabaseHas('order_owner_notices', ['order_id' => $order->id, 'status' => 'pending']);
        DB::commit();
        $this->assertSame(1, $observed);
        $this->create($key);
        app(OwnerOrderNotice::class)->attempt($order->id);
        $this->assertSame(1, $observed);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_owner_notices', 1);
        $this->assertDatabaseHas('order_owner_notices', ['order_id' => $order->id, 'status' => 'captured']);
        $body = file_get_contents($this->capture);
        $this->assertSame(1, substr_count($body, 'Subject: Nuevo pedido'));
        $this->assertStringContainsString($order->reference, $body);
        $this->assertStringContainsString('pendiente', $body);
        $this->assertStringContainsString('dueno-configurado@example.test', $body);
        $this->assertStringContainsString('/dueno/pedidos/'.$order->id, $body);
        foreach (['COMPRADOR-PRIVADO', 'TELEFONO-PRIVADO', 'comprador-privado@example.test', 'DIRECCION-PRIVADA', 'REGION-PRIVADA', 'inyectado@example.test', $order->access_token] as $private) {
            $this->assertStringNotContainsString($private, $body);
        }
    }
    public function test_rollback_no_genera_aviso_ni_compra(): void
    {
        Mail::fake();
        DB::beginTransaction();
        $this->create();
        DB::rollBack();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_owner_notices', 0);
        $this->assertSame(0, $this->product->fresh()->stock_reservado);
    }
    public function test_fallo_no_revierte_pedido_y_registro_no_filtra_excepcion(): void
    {
        $calls = 0;
        Event::listen(MessageSending::class, function () use (&$calls) { $calls++; throw new \RuntimeException('password=SECRETO comprador-privado@example.test'); });
        Log::spy();
        Log::shouldReceive('channel')->with('order_notices')->andReturn(new \Psr\Log\NullLogger);
        $key = (string)Str::uuid();
        $order = $this->create($key);
        $this->assertDatabaseHas('order_owner_notices', ['order_id' => $order->id, 'status' => 'failed', 'failure_code' => 'capture_failed']);
        $this->create($key);
        app(OwnerOrderNotice::class)->attempt($order->id);
        $this->assertSame(1, $calls);
        Log::shouldHaveReceived('warning')->once()->with('owner_order_notice_failed', \Mockery::on(fn ($data) => array_keys($data) === ['order_id', 'code'] && $data['code'] === 'capture_failed'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(2, $this->product->fresh()->stock_reservado);
        $this->assertSame('pendiente', $order->fresh()->estado_pago);
    }
    public function test_contador_y_revision_solo_por_dueno_de_la_tienda_sin_cambiar_estados(): void
    {
        Mail::fake();
        $first = $this->create(); $second = $this->create();
        $owner = User::factory()->create(['store_id' => $this->store->id, 'role' => 'owner', 'credential_version' => 1]);
        $foreignStore = Store::create(['slug' => 'otra-aviso', 'name' => 'Otra ficticia', 'initials' => 'OT']);
        $foreignOwner = User::factory()->create(['store_id' => $foreignStore->id, 'role' => 'owner', 'credential_version' => 1]);
        $before = $first->fresh()->getAttributes();
        $this->get('/dueno/pedidos/'.$first->id)->assertRedirect('/dueno/ingresar');
        $this->actingAs($foreignOwner)->withSession(['owner_credential_version' => 1]);
        $this->get('/dueno/pedidos')->assertOk()->assertSee('0 sin revisar')->assertDontSee($first->reference);
        $this->get('/dueno/pedidos/'.$first->id)->assertNotFound();
        $this->assertNull($first->fresh()->reviewed_at);
        $this->actingAs($owner)->withSession(['owner_credential_version' => 1]);
        $this->get('/dueno')->assertOk()->assertSee('2 sin revisar')->assertSee('2 pedidos sin revisar');
        $list = $this->get('/dueno/pedidos')->assertOk();
        $this->assertSame(2, substr_count($list->getContent(), '>Nuevo</span>'));
        $this->get('/pedido/seguimiento/'.$first->access_token)->assertOk();
        $this->assertNull($first->fresh()->reviewed_at);
        $this->get('/dueno/pedidos/'.$first->id)->assertOk()->assertSee('1 sin revisar');
        $after = $first->fresh();
        $this->assertNotNull($after->reviewed_at);
        $attributes = $after->getAttributes(); unset($attributes['reviewed_at']); unset($before['reviewed_at']);
        $this->assertSame($before, $attributes);
        $reviewTime = $after->reviewed_at;
        $this->travel(1)->hours();
        $this->get('/dueno/pedidos/'.$first->id)->assertOk();
        $this->assertTrue($reviewTime->equalTo($first->fresh()->reviewed_at));
        $this->get('/dueno/pedidos/'.$second->id)->assertOk()->assertSee('0 sin revisar');
        $this->assertSame(1, $first->events()->count());
        $this->assertSame(4, $this->product->fresh()->stock_reservado);
    }
    public function test_destinatarios_aislados_y_estado_de_pago_actual_en_aviso_pendiente(): void
    {
        Mail::fake();
        $other = Store::create(['slug' => 'otra-captura', 'name' => 'Otra ficticia', 'initials' => 'OT']);
        config(['owner_orders.recipients.otra-captura' => 'otra-configurada@example.test']);
        $copy = $this->product->replicate(); $copy->store_id = $other->id; $copy->save();
        $first = $this->create();
        $second = $this->create(store: $other, product: $copy);
        Mail::assertSent(NewOwnerOrder::class, fn ($mail) => $mail->reference === $first->reference && $mail->hasTo('dueno-configurado@example.test') && !$mail->hasTo('otra-configurada@example.test'));
        Mail::assertSent(NewOwnerOrder::class, fn ($mail) => $mail->reference === $second->reference && $mail->hasTo('otra-configurada@example.test'));
        // Aviso diferido por una interrupción: no inventar que el pago sigue pendiente.
        DB::table('order_owner_notices')->where('order_id', $second->id)->update(['status' => 'pending']);
        $second->update(['estado_pago' => 'recibido']);
        Mail::fake();
        $this->artisan('orders:notify-pending', ['--store' => $this->store->id])->assertSuccessful();
        Mail::assertNothingSent();
        $this->artisan('orders:notify-pending', ['--store' => $other->id])->assertSuccessful();
        Mail::assertSent(NewOwnerOrder::class, fn ($mail) => $mail->paymentState === 'recibido' && $mail->hasTo('otra-configurada@example.test'));
        $this->artisan('orders:notify-pending', ['--store' => $other->id])->assertSuccessful();
        Mail::assertSentCount(1);
    }
    public function test_destinatario_ausente_no_usa_correo_del_comprador(): void
    {
        Mail::fake();
        config(['owner_orders.recipients' => []]);
        Log::shouldReceive('warning')->once()->with('owner_order_notice_failed', \Mockery::on(fn ($data) => $data['code'] === 'recipient_not_configured'));
        $order = $this->create();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('order_owner_notices', ['order_id' => $order->id, 'status' => 'failed', 'failure_code' => 'recipient_not_configured']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'estado_pedido' => 'pendiente']);
    }
}
