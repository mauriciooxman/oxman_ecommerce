<?php

namespace Tests\Feature;

use App\Models\{Order, Product, Store, User};
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Support\GuardsDemoDatabase;
use Tests\TestCase;

class OwnerAlertsTest extends TestCase
{
    use RefreshDatabase, GuardsDemoDatabase {
        GuardsDemoDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }
    private Store $store;
    private User $owner;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
        $this->store = Store::where('slug', 'tecnologia-demo')->firstOrFail();
        $this->owner = User::factory()->create(['store_id' => $this->store->id, 'role' => 'owner', 'credential_version' => 1]);
    }
    private function login(): void { $this->actingAs($this->owner)->withSession(['owner_credential_version' => 1]); }
    private function order(): Order
    {
        $product = Product::where('store_id', $this->store->id)->where('sku', 'DEMO-C1')->firstOrFail();
        return app(OrderService::class)->confirmar($this->store,
            ['nombre' => 'Persona ficticia privada', 'telefono' => '000000000', 'modalidad' => 'retiro'],
            (string)Str::uuid(), [$product->id => ['quantity' => 1, 'unit_price' => 3000]]);
    }
    public function test_baseline_datos_minimos_y_consulta_sin_mutar_pedidos(): void
    {
        $order = $this->order();
        $this->login();
        $before = $order->fresh()->getAttributes();
        $this->getJson('/dueno/pedidos/avisos')->assertOk()->assertExactJson(['cursor' => (string)$order->id, 'unreviewed_count' => 1, 'orders' => []])->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/dueno/pedidos/avisos?after=0')->assertExactJson(['cursor' => (string)$order->id, 'unreviewed_count' => 1,
            'orders' => [['id' => (string)$order->id, 'reference' => $order->reference, 'units' => 1, 'product_subtotal' => 3000, 'delivery' => 'retiro']]])->assertDontSee('Persona ficticia privada')->assertDontSee('000000000');
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame(1, $order->events()->count());
        $this->get('/dueno')->assertOk()->assertSee('Activar sonido')->assertSee('Probar sonido')->assertSee('Tienes una compra nueva')->assertSee('role="dialog"', false)->assertSee('owner-order-alerts.js');
    }
    public function test_nuevo_id_detectado_aunque_contador_permanezca_igual_y_no_se_repite(): void
    {
        $first = $this->order(); $this->login();
        $this->get('/dueno/pedidos/'.$first->id)->assertOk();
        $second = $this->order();
        $this->getJson('/dueno/pedidos/avisos?after='.$first->id)->assertExactJson(['cursor' => (string)$second->id, 'unreviewed_count' => 1,
            'orders' => [['id' => (string)$second->id, 'reference' => $second->reference, 'units' => 1, 'product_subtotal' => 3000, 'delivery' => 'retiro']]]);
        $this->getJson('/dueno/pedidos/avisos?after='.$second->id)->assertExactJson(['cursor' => (string)$second->id, 'unreviewed_count' => 1, 'orders' => []]);
        $this->assertNull($second->fresh()->reviewed_at);
    }
    public function test_aislamiento_roles_sesion_vencida_y_cursor_invalido(): void
    {
        $this->getJson('/dueno/pedidos/avisos')->assertUnauthorized();
        $own = $this->order();
        $other = Store::create(['slug' => 'otra-alerta', 'name' => 'Otra ficticia', 'initials' => 'OT']);
        $foreign = $own->replicate();
        $foreign->store_id = $other->id; $foreign->reference = 'OTRA-REF';
        $foreign->idempotency_key = (string)Str::uuid(); $foreign->access_token = (string)Str::uuid(); $foreign->save();
        $this->login();
        $this->getJson('/dueno/pedidos/avisos?after=0')->assertJsonCount(1, 'orders')->assertJsonPath('cursor', (string)$own->id)->assertDontSee('OTRA-REF');
        $this->getJson('/dueno/pedidos/avisos?after=-1')->assertUnprocessable();
        $this->getJson('/dueno/pedidos/avisos?after[]=1')->assertUnprocessable();
        $this->getJson('/dueno/pedidos/avisos?store_id='.$other->id)->assertUnprocessable();
        $viewer = User::factory()->create(['store_id' => $this->store->id, 'role' => 'viewer', 'credential_version' => 1]);
        $this->actingAs($viewer)->withSession(['owner_credential_version' => 1]);
        $this->getJson('/dueno/pedidos/avisos')->assertForbidden();
        $this->login();
        $this->owner->forceFill(['credential_version' => 2])->save();
        $this->getJson('/dueno/pedidos/avisos')->assertUnauthorized();
        $this->getJson('/dueno/pedidos/avisos')->assertUnauthorized();
    }
    public function test_lotes_limitados_sin_saltar_llegadas(): void
    {
        $orders = collect();
        for ($i = 0; $i < 27; $i++) { $orders->push($this->order()); }
        $this->login();
        $first = $this->getJson('/dueno/pedidos/avisos?after=0')->assertOk()->assertJsonCount(25, 'orders')->assertJsonPath('unreviewed_count', 27);
        $cursor = $first->json('cursor');
        $this->getJson('/dueno/pedidos/avisos?after='.$cursor)->assertJsonCount(2, 'orders')->assertJsonPath('cursor', (string)$orders->last()->id);
    }
}
