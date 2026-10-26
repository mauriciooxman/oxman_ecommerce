<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Tests\Support\GuardsDemoDatabase;
use Tests\TestCase;

class OwnerPanelTest extends TestCase
{
    use RefreshDatabase, GuardsDemoDatabase {
        GuardsDemoDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    private User $owner;
    private User $other;
    private Product $product;
    private Product $foreign;
    private string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->password = Str::random(30);
        $store = Store::where('slug', 'tecnologia-demo')->firstOrFail();
        $otherStore = Store::create(['slug' => 'otra', 'name' => 'Otra tienda ficticia', 'initials' => 'OT']);
        $this->owner = $this->account($store, 'dueno@example.test');
        $this->other = $this->account($otherStore, 'otro@example.test');
        $this->product = $store->products()->where('sku', 'DEMO-C1')->firstOrFail();
        $this->foreign = $this->product->replicate();
        $this->foreign->store_id = $otherStore->id;
        $this->foreign->category_id = $otherStore->categories()->create(['name' => 'Cables'])->id;
        $this->foreign->save();
        Storage::fake('catalog');
    }

    private function account(Store $store, string $email): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Dueño ficticio', 'email' => $email, 'password' => $this->password, 'store_id' => $store->id, 'role' => 'owner', 'credential_version' => 1])->save();
        return $user;
    }

    private function loginAs(?User $user = null): static
    {
        $user ??= $this->owner;
        return $this->actingAs($user)->withSession(['owner_credential_version' => $user->credential_version]);
    }

    private function data(?Product $product = null): array
    {
        $product ??= $this->product;
        return ['sku' => $product->sku, 'name' => 'Producto editado ficticio', 'description' => '<script>alert(1)</script>', 'compatibility' => 'USB ficticio',
            'category_id' => $product->category_id, 'unit_price' => '3500', 'version' => $product->version,
            'tiers' => [['minimum_quantity' => '6', 'unit_price' => '2800'], ['minimum_quantity' => '24', 'unit_price' => '2100']]];
    }

    private function adjustment(array $overrides = []): array
    {
        return $overrides + ['mode' => 'variation', 'quantity' => '-3', 'reason' => 'Conteo ficticio', 'version' => $this->product->fresh()->version, 'operation_id' => (string) Str::uuid()];
    }

    private function getWithSession(string $path)
    {
        // Conservar la cookie evita reiniciar un error bag JSON ya deserializado por las aserciones.
        return $this->withCookie(config('session.cookie'), session()->getId())->get($path);
    }

    public function test_invitados_en_todos_los_endpoints_y_restriccion_local(): void
    {
        foreach ([['get','/dueno'], ['get','/dueno/productos/nuevo'], ['post','/dueno/productos'], ['get','/dueno/productos/1'], ['put','/dueno/productos/1'],
            ['post','/dueno/productos/1/estado'], ['get','/dueno/productos/1/stock'], ['post','/dueno/productos/1/stock'], ['get','/dueno/productos/1/foto'],
            ['get','/dueno/categorias'], ['post','/dueno/categorias'], ['put','/dueno/categorias/1'], ['post','/dueno/salir']] as [$method,$path]) {
            $this->$method($path)->assertRedirect('/dueno/ingresar')->assertDontSee('DEMO-C1');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->get('/dueno/ingresar')->assertNotFound();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('http://ajeno.example/dueno/ingresar')->assertNotFound();
    }

    public function test_login_regenera_sesion_logout_y_revocacion(): void
    {
        $this->get('/dueno/ingresar')->assertOk();
        $oldSession = session()->getId();
        $this->post('/dueno/ingresar', ['email' => $this->owner->email, 'password' => $this->password])->assertRedirect('/dueno');
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertAuthenticatedAs($this->owner);
        $this->get('/dueno')->assertOk()->assertSee('Tecnología Demo');
        $this->owner->forceFill(['credential_version' => 2])->save();
        $this->get('/dueno')->assertRedirect('/dueno/ingresar');
        $this->assertGuest();
        $this->loginAs($this->owner->fresh())->post('/dueno/salir')->assertRedirect('/dueno/ingresar');
        $this->assertGuest();
        $this->get('/dueno')->assertRedirect('/dueno/ingresar');
    }

    public function test_login_invalido_generico_limite_y_recuperacion(): void
    {
        for ($index = 0; $index < 5; $index++) {
            $this->post('/dueno/ingresar', ['email' => $this->owner->email, 'password' => 'incorrecta'])->assertSessionHasErrors(['email' => 'No fue posible ingresar con esas credenciales.']);
        }
        $this->post('/dueno/ingresar', ['email' => strtoupper($this->owner->email), 'password' => $this->password])->assertSessionHasErrors(['email' => 'Demasiados intentos. Vuelve a intentar en un minuto.']);
        $this->travel(61)->seconds();
        $this->post('/dueno/ingresar', ['email' => $this->owner->email, 'password' => $this->password])->assertRedirect('/dueno');
        $this->assertAuthenticated();
        $this->assertArrayNotHasKey('password', session()->getOldInput());
    }

    public function test_sin_rol_sin_tienda_inactiva_y_sin_rutas_de_alta_web(): void
    {
        foreach (['role' => null, 'store_id' => null] as $field => $value) {
            $user = $this->owner->replicate();
            $user->email = $field.'@example.test';
            $user->$field = $value;
            $user->save();
            $this->loginAs($user)->get('/dueno')->assertForbidden();
        }
        $this->owner->store->update(['active' => false]);
        $this->loginAs($this->owner->fresh())->get('/dueno')->assertForbidden();
        foreach (['register','forgot-password','reset-password','invite','dueno/registro'] as $route) { $this->get('/'.$route)->assertNotFound(); }
    }

    public function test_aislamiento_en_cada_endpoint_y_campos_manipulados(): void
    {
        $id = $this->foreign->id;
        $this->loginAs();
        foreach ([['get',"/dueno/productos/$id"], ['put',"/dueno/productos/$id"], ['post',"/dueno/productos/$id/estado"],
            ['get',"/dueno/productos/$id/stock"], ['post',"/dueno/productos/$id/stock"], ['get',"/dueno/productos/$id/foto"],
            ['put','/dueno/categorias/'.$this->foreign->category_id]] as [$method,$path]) {
            $this->$method($path, $this->data())->assertNotFound();
        }
        $this->get('/dueno')->assertDontSee('Otra tienda ficticia');
        foreach (['store_id' => $this->other->store_id, 'stock' => 900, 'user_id' => $this->other->id, 'published' => true, 'archived_at' => now()->toDateTimeString(), 'photo_path' => '../ajena.webp'] as $field => $value) {
            $this->put('/dueno/productos/'.$this->product->id, $this->data() + [$field => $value])->assertSessionHasErrors($field);
        }
        $this->put('/dueno/productos/'.$this->product->id, array_replace($this->data(), ['category_id' => $this->foreign->category_id]))->assertSessionHasErrors('category_id');
        $this->assertSame(40, $this->product->fresh()->stock);
        $this->assertEquals($this->foreign->getAttributes(), $this->foreign->fresh()->getAttributes());
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_crear_editar_validar_conflicto_y_reflejar_catalogo_carro(): void
    {
        $this->post('/carro/DEMO-C1', ['quantity' => 6]);
        $this->loginAs()->put('/dueno/productos/'.$this->product->id, $this->data())->assertRedirect();
        $this->assertSame(40, $this->product->fresh()->stock);
        $this->get('/productos/DEMO-C1')->assertOk()->assertSee('Producto editado ficticio')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>', false);
        $this->get('/carro')->assertSee('Cambió el precio')->assertSee('16.800');
        $this->put('/dueno/productos/'.$this->product->id, $this->data())->assertConflict();
        $new = array_replace($this->data(), ['sku' => 'NUEVO-1', 'version' => null, 'tiers' => []]);
        $this->post('/dueno/productos', $new)->assertRedirect();
        $created = Product::where('sku','NUEVO-1')->firstOrFail();
        $this->assertSame(0, $created->stock);
        $this->assertFalse($created->published);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->post('/dueno/productos', $new)->assertSessionHasErrors('sku');
        $this->loginAs($this->other)->post('/dueno/productos', array_replace($new, ['category_id' => $this->foreign->category_id]))->assertRedirect();
        $this->assertSame(2, Product::where('sku','NUEVO-1')->count());
    }

    public function test_precios_tramos_invalidos_y_rollback_integral(): void
    {
        $this->loginAs();
        $before = $this->product->getAttributes();
        $tiers = $this->product->tiers->toArray();
        foreach ([['unit_price' => 0], ['unit_price' => '1.5'], ['unit_price' => 'abc'], ['unit_price' => 1000000001], ['name' => str_repeat('x',161)],
            ['description' => str_repeat('x',5001)], ['sku' => '../malo'], ['compatibility' => str_repeat('x',2001)],
            ['tiers' => [['minimum_quantity' => 2, 'unit_price' => 3500]]], ['tiers' => [['minimum_quantity' => 2, 'unit_price' => 2000], ['minimum_quantity' => 2, 'unit_price' => 1000]]]] as $invalid) {
            $this->put('/dueno/productos/'.$this->product->id, array_replace($this->data(), $invalid))->assertSessionHasErrors();
            $this->assertSame($before, $this->product->fresh()->getAttributes());
            $this->assertSame($tiers, $this->product->fresh()->tiers->toArray());
        }
        $this->put('/dueno/productos/'.$this->product->id, array_replace($this->data(), ['tiers' => []]))->assertRedirect();
        $this->assertCount(0, $this->product->fresh()->tiers);
    }

    public function test_categorias_normalizacion_renombrado_y_aislamiento(): void
    {
        $category = $this->product->category_id;
        $this->loginAs()->put('/dueno/categorias/'.$category, ['name' => '  Cables   nuevos  '])->assertRedirect();
        $this->assertSame('Cables nuevos', $this->product->fresh()->category);
        $this->assertSame('Cables', $this->foreign->fresh()->category);
        $this->assertSame(2, Product::where('store_id',$this->owner->store_id)->where('category','Cables nuevos')->count());
        $this->get('/?category=Cables%20nuevos')->assertSee('DEMO-C1')->assertSee('DEMO-C2');
        $this->get('/productos/DEMO-C1')->assertSee('Cables nuevos');
        $this->post('/dueno/categorias', ['name' => 'Cables nuevos'])->assertSessionHasErrors('name');
        $this->post('/dueno/categorias', ['name' => '   '])->assertSessionHasErrors('name');
        $this->post('/dueno/categorias', ['name' => 'Otra', 'store_id' => $this->other->store_id])->assertSessionHasErrors('store_id');
        $this->loginAs($this->other)->post('/dueno/categorias', ['name' => 'Cables nuevos'])->assertRedirect();
    }

    public function test_fotos_formatos_reales_recodificacion_y_reemplazo(): void
    {
        $this->loginAs();
        foreach (['jpg', 'png', 'webp'] as $extension) {
            $file = UploadedFile::fake()->image('principal.'.$extension, 40, 30);
            $data = $this->data($this->product->fresh());
            $this->put('/dueno/productos/'.$this->product->id, $data + ['photo' => $file])->assertRedirect();
            $item = $this->product->fresh();
            $this->assertStringEndsWith('.webp', $item->photo_path);
            $this->assertCount(1, Storage::disk('catalog')->allFiles());
            $bytes = Storage::disk('catalog')->get($item->photo_path);
            $this->assertSame(IMAGETYPE_WEBP,getimagesizefromstring($bytes)[2]);
            $this->assertSame([40,30],array_slice(getimagesizefromstring($bytes),0,2));
            $this->assertSame('RIFF',substr($bytes,0,4));
            $this->assertSame('WEBP',substr($bytes,8,4));
            $this->assertSame($bytes,$this->get('/dueno/productos/'.$item->id.'/foto')->assertOk()->assertHeader('Content-Type', 'image/webp')->streamedContent());
            $this->assertSame($bytes,$this->get('/productos/DEMO-C1?foto=1')->assertOk()->assertHeader('Content-Type', 'image/webp')->streamedContent());
        }
        $this->get('/')->assertSee('/productos/DEMO-C1?foto=1', false);
        $this->product->refresh()->update(['published' => false]);
        $this->get('/productos/DEMO-C1?foto=1')->assertNotFound();
        $this->loginAs($this->other)->get('/dueno/productos/'.$this->product->id.'/foto')->assertNotFound();
    }

    public function test_fotos_invalidas_y_fallo_disco_conservan_producto_y_foto(): void
    {
        $this->loginAs();
        $this->put('/dueno/productos/'.$this->product->id, $this->data() + ['photo' => UploadedFile::fake()->image('valida.png')])->assertRedirect();
        $before = $this->product->fresh()->getAttributes();
        foreach ([UploadedFile::fake()->createWithContent('falsa.png', '<?php echo 1;'), UploadedFile::fake()->createWithContent('script.svg', '<svg><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('doble.php.png'), UploadedFile::fake()->image('alta.png',4097,1), UploadedFile::fake()->image('grande.png')->size(5121)] as $photo) {
            $this->put('/dueno/productos/'.$this->product->id, $this->data($this->product->fresh()) + ['photo' => $photo])->assertSessionHasErrors('photo');
            $this->assertSame($before, $this->product->fresh()->getAttributes());
            $this->assertCount(1, Storage::disk('catalog')->allFiles());
        }
        Storage::shouldReceive('disk')->with('catalog')->andReturn(new class { public function put($path,$bytes) { return false; } public function delete($path) { return true; } });
        $this->put('/dueno/productos/'.$this->product->id, $this->data($this->product->fresh()) + ['photo' => UploadedFile::fake()->image('bien.png')])->assertSessionHasErrors('photo');
        $this->assertSame($before, $this->product->fresh()->getAttributes());
    }

    public function test_archivo_restauracion_oculta_y_carro_revalidado(): void
    {
        $this->post('/carro/DEMO-C1', ['quantity' => 6]);
        $this->loginAs()->post('/dueno/productos/'.$this->product->id.'/estado', ['action' => 'archive', 'version' => 1])->assertSessionHasErrors('confirm');
        $this->post('/dueno/productos/'.$this->product->id.'/estado', ['action' => 'archive', 'version' => 1, 'confirm' => 1])->assertRedirect();
        $this->get('/')->assertDontSee('DEMO-C1');
        $this->get('/productos/DEMO-C1')->assertNotFound();
        $this->post('/carro/DEMO-C1', ['quantity' => 1])->assertNotFound();
        $this->get('/carro')->assertSee('dejó de estar disponible');
        $this->get('/dueno')->assertDontSee('DEMO-C1');
        $this->get('/dueno?archived=1')->assertSee('DEMO-C1');
        $this->put('/dueno/productos/'.$this->product->id, $this->data($this->product->fresh()))->assertConflict();
        $this->post('/dueno/productos/'.$this->product->id.'/stock', $this->adjustment())->assertConflict();
        $this->post('/dueno/productos/'.$this->product->id.'/estado', ['action' => 'publish', 'version' => 2])->assertConflict();
        $this->post('/dueno/productos/'.$this->product->id.'/estado', ['action' => 'restore', 'version' => 2])->assertRedirect();
        $this->assertFalse($this->product->fresh()->published);
        $this->get('/productos/DEMO-C1')->assertNotFound();
        $this->post('/dueno/productos/'.$this->product->id.'/estado', ['action' => 'publish', 'version' => 3])->assertRedirect();
        $this->get('/productos/DEMO-C1')->assertOk();
        $this->assertSame(40,$this->product->fresh()->stock);
        $this->assertCount(2,$this->product->fresh()->tiers);
    }

    public function test_variacion_conteo_auditoria_e_idempotencia_de_ambos_modos(): void
    {
        $this->loginAs();
        foreach ([['variation',-3,37,-3], ['variation',3,40,3], ['count',37,37,-3], ['count',37,37,0], ['count',0,0,-37]] as [$mode,$quantity,$after,$delta]) {
            $data = $this->adjustment(['mode' => $mode, 'quantity' => $quantity]);
            $this->post('/dueno/productos/'.$this->product->id.'/stock',$data)->assertRedirect();
            $movement = StockMovement::latest('id')->firstOrFail();
            $this->assertSame($after,$this->product->fresh()->stock);
            $this->assertSame($delta,$movement->delta);
            $this->assertSame($after,$movement->after);
            $this->assertSame($after-$delta,$movement->before);
            $this->assertSame($mode,$movement->mode);
            $this->assertSame($quantity,$movement->quantity);
            $this->assertEquals($this->owner->id,$movement->user_id);
            $this->assertEquals($this->owner->store_id,$movement->store_id);
            $this->assertNotNull($movement->created_at);
            $count = StockMovement::count();
            $this->post('/dueno/productos/'.$this->product->id.'/stock',$data)->assertRedirect();
            $this->assertDatabaseCount('stock_movements',$count);
            $this->post('/dueno/productos/'.$this->product->id.'/stock',array_replace($data,['reason'=>'Otro motivo']))->assertConflict();
        }
        $this->get('/dueno/productos/'.$this->product->id.'/stock')->assertSee('America/Santiago')->assertSee('Conteo ficticio')->assertSee('Dueño ficticio');
        $this->assertSame(-40, (int)StockMovement::sum('delta'));
    }

    public function test_ajustes_invalidos_conflicto_y_sin_auditoria_parcial(): void
    {
        $this->loginAs();
        foreach ([['quantity' => 0], ['quantity' => '-41'], ['quantity' => '1.5'], ['quantity' => 1000001], ['mode'=>'count','quantity'=>-1], ['reason'=>' '],
            ['user_id'=>$this->other->id], ['store_id'=>$this->other->store_id], ['created_at'=>'2020-01-01'], ['operation_id'=>'sin-uuid']] as $invalid) {
            $this->post('/dueno/productos/'.$this->product->id.'/stock',array_replace($this->adjustment(),$invalid))->assertSessionHasErrors();
            $this->assertSame(40,$this->product->fresh()->stock);
            $this->assertDatabaseCount('stock_movements',0);
        }
        $stale = $this->adjustment(['mode'=>'count','quantity'=>10]);
        $this->post('/dueno/productos/'.$this->product->id.'/stock',$this->adjustment(['quantity'=>3]))->assertRedirect();
        $this->post('/dueno/productos/'.$this->product->id.'/stock',$stale)->assertConflict();
        $this->assertSame(43,$this->product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements',1);
    }

    public function test_comando_interactivo_alta_reset_hash_y_revocacion_sin_secretos(): void
    {
        $freshPassword = Str::random(30);
        $confirmation = $freshPassword;
        $this->artisan('owner:account', ['email'=>'nueva@example.test','--store'=>$this->owner->store_id,'--role'=>'owner'])
            ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres, máximo 72 bytes)',$freshPassword)->expectsQuestion('Repite la contraseña',$confirmation)
            ->expectsOutput('Cuenta guardada. Las sesiones anteriores quedaron revocadas.')->assertSuccessful();
        $account = User::where('email','nueva@example.test')->firstOrFail();
        $this->assertTrue(Hash::check($freshPassword,$account->password));
        $this->assertNotSame($freshPassword,$account->password);
        $this->loginAs($account);
        $this->artisan('owner:account', ['email'=>$account->email,'--store'=>$account->store_id,'--role'=>'owner','--reset'=>true])
            ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres, máximo 72 bytes)',$this->password)->expectsQuestion('Repite la contraseña',$this->password)->assertSuccessful();
        $this->assertFalse(Hash::check($freshPassword,$account->fresh()->password));
        $this->get('/dueno')->assertRedirect('/dueno/ingresar');
    }

    public function test_limite_exacto_de_foto_y_bcrypt_sin_truncamiento(): void
    {
        $png = UploadedFile::fake()->image('limite.png', 2, 2)->getContent();
        $this->loginAs();
        $atLimit = UploadedFile::fake()->createWithContent('limite.png', str_pad($png, 5 * 1024 * 1024, "\0"));
        $this->put('/dueno/productos/'.$this->product->id, $this->data() + ['photo'=>$atLimit])->assertRedirect();
        $tooLarge = UploadedFile::fake()->createWithContent('limite.png', str_pad($png, 5 * 1024 * 1024 + 1, "\0"));
        $before = $this->product->fresh()->getAttributes();
        $this->put('/dueno/productos/'.$this->product->id, $this->data($this->product->fresh()) + ['photo'=>$tooLarge])->assertSessionHasErrors('photo');
        $this->assertSame($before,$this->product->fresh()->getAttributes());
        foreach ([str_repeat('x',73), str_repeat('ñ',37)] as $long) {
            $this->artisan('owner:account',['email'=>'larga@example.test','--store'=>$this->owner->store_id,'--role'=>'owner'])
                ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres, máximo 72 bytes)',$long)->expectsQuestion('Repite la contraseña',$long)
                ->expectsOutput('Contraseña inválida o confirmación diferente. No se guardó.')->assertFailed();
        }
        $this->assertDatabaseMissing('users',['email'=>'larga@example.test']);
        $boundary = Str::random(72);
        $this->artisan('owner:account',['email'=>'limite@example.test','--store'=>$this->owner->store_id,'--role'=>'owner'])
            ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres, máximo 72 bytes)',$boundary)->expectsQuestion('Repite la contraseña',$boundary)->assertSuccessful();
        $this->assertTrue(Hash::check($boundary,User::where('email','limite@example.test')->firstOrFail()->password));
    }

    public function test_fallo_transaccional_tras_foto_nueva_conserva_version_anterior(): void
    {
        $this->loginAs();
        $this->put('/dueno/productos/'.$this->product->id, $this->data()+['photo'=>UploadedFile::fake()->image('anterior.png')])->assertRedirect();
        $before = $this->product->fresh()->getAttributes();
        $tiers = $this->product->fresh()->tiers->toArray();
        \App\Models\PriceTier::creating(function () { throw new \RuntimeException('Fallo ficticio de persistencia'); });
        try {
            app(\App\Services\OwnerCatalogService::class)->save($this->owner,$this->product->id,$this->data($this->product->fresh()),UploadedFile::fake()->image('nueva.png'));
            $this->fail('Se esperaba fallo de persistencia');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo ficticio de persistencia',$error->getMessage());
        } finally {
            \App\Models\PriceTier::flushEventListeners();
        }
        $this->assertSame($before,$this->product->fresh()->getAttributes());
        $this->assertSame($tiers,$this->product->fresh()->tiers->toArray());
        Storage::disk('catalog')->assertExists($before['photo_path']);
        $this->assertCount(1,Storage::disk('catalog')->allFiles());
    }

    public function test_enlace_publico_solo_para_demo_con_sku_homonimo(): void
    {
        $this->loginAs()->get('/dueno/productos/'.$this->product->id)->assertOk()->assertSee('Ver ficha pública');
        $this->loginAs($this->other)->get('/dueno/productos/'.$this->foreign->id)->assertOk()->assertDontSee('Ver ficha pública');
        $this->get('/productos/DEMO-C1')->assertOk()->assertSee('Tecnología Demo')->assertDontSee('Otra tienda ficticia');
    }

    public function test_categorias_error_restituye_el_formulario_y_el_resumen_correctos(): void
    {
        $this->loginAs();
        $path='/dueno/categorias/'.$this->product->category_id;
        $form='category-'.$this->product->category_id;
        $duplicate='Cargadores';
        $this->from('/dueno/categorias')->put($path,['name'=>$duplicate])->assertRedirect('/dueno/categorias')->assertSessionHasErrors('name')->assertSessionHasInput('category_form',$form);
        $response=$this->getWithSession('/dueno/categorias')->assertOk()->assertSee('href="#'.$form.'-name"',false);
        $dom=new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $this->assertSame($duplicate,$dom->getElementById($form.'-name')->getAttribute('value'));
        $this->assertSame('true',$dom->getElementById($form.'-name')->getAttribute('aria-invalid'));
        $this->assertSame($form.'-name-error',$dom->getElementById($form.'-name')->getAttribute('aria-describedby'));
        $this->assertSame('',$dom->getElementById('category-new-name')->getAttribute('value'));
        $this->assertSame('Cables',$this->product->fresh()->category);
        $this->from('/dueno/categorias')->post('/dueno/categorias',['name'=>'Cables'])->assertSessionHasErrors('name')->assertSessionHasInput('category_form','category-new');
        $response=$this->getWithSession('/dueno/categorias')->assertSee('href="#category-new-name"',false);
        @$dom->loadHTML($response->getContent());
        $this->assertSame('Cables',$dom->getElementById('category-new-name')->getAttribute('value'));
        $this->assertSame('true',$dom->getElementById('category-new-name')->getAttribute('aria-invalid'));
    }

    public function test_categorias_1062_real_en_alta_y_renombrado_se_convierte_en_error(): void
    {
        $this->loginAs();
        $before=Category::count();
        foreach (['creating','updating'] as $event) {
            Category::$event(function (Category $category) {
                // Competidor posterior a la validación: dispara la restricción1062 real.
                DB::table('categories')->insert(['store_id'=>$category->store_id,'name'=>$category->name,'created_at'=>now(),'updated_at'=>now()]);
            });
            try {
                $form=$event==='creating'?'category-new':'category-'.$this->product->category_id;
                $path=$event==='creating'?'/dueno/categorias':'/dueno/categorias/'.$this->product->category_id;
                $method=$event==='creating'?'post':'put';
                $this->from('/dueno/categorias')->$method($path,['name'=>'Carrera ficticia'])->assertRedirect('/dueno/categorias')->assertSessionHasErrors(['name'=>'Este nombre ya existe en tu tienda.'])->assertSessionHasInput('category_form',$form);
                $this->getWithSession('/dueno/categorias')->assertOk()->assertSee('href="#'.$form.'-name"',false)->assertSee('value="Carrera ficticia"',false);
                $this->assertDatabaseCount('categories',$before);
                $this->assertSame('Cables',$this->product->fresh()->category);
            } finally { Category::flushEventListeners(); }
        }
    }

    public function test_login_rechaza_sufijo_bcrypt_y_limites_ascii_multibyte_sin_flash(): void
    {
        foreach ([Str::random(72),str_repeat('ñ',36)] as $boundary) {
            $this->owner->forceFill(['password'=>$boundary])->save();
            $this->post('/dueno/ingresar',['email'=>$this->owner->email,'password'=>$boundary.'x'])->assertSessionHasErrors('password');
            $this->assertGuest();
            $this->assertArrayNotHasKey('password',session()->getOldInput());
            $response=$this->getWithSession('/dueno/ingresar')->assertOk()->assertDontSee($boundary);
            $dom=new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $this->assertSame('true',$dom->getElementById('password')->getAttribute('aria-invalid'));
            $this->assertSame('password-error',$dom->getElementById('password')->getAttribute('aria-describedby'));
            $this->assertFalse($dom->getElementById('password')->hasAttribute('value'));
            $this->post('/dueno/ingresar',['email'=>$this->owner->email,'password'=>$boundary])->assertRedirect('/dueno');
            $this->assertAuthenticatedAs($this->owner);
            $this->post('/dueno/salir')->assertRedirect('/dueno/ingresar');
        }
        $this->post('/dueno/ingresar',['email'=>$this->owner->email,'password'=>str_repeat('ñ',37)])->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->post('/dueno/ingresar',['email'=>'invalido','password'=>['invalida']])->assertSessionHasErrors(['email','password']);
        $response=$this->getWithSession('/dueno/ingresar')->assertOk();
        @$dom->loadHTML($response->getContent());
        $this->assertSame('true',$dom->getElementById('email')->getAttribute('aria-invalid'));
    }

    public function test_errores_de_producto_y_tramos_tienen_asociaciones_navegables(): void
    {
        $data=array_replace($this->data(),['sku'=>'../invalido','name'=>'','description'=>'','compatibility'=>str_repeat('x',2001),
            'category_id'=>$this->foreign->category_id,'unit_price'=>0,'tiers'=>[['minimum_quantity'=>1,'unit_price'=>0]],'photo'=>UploadedFile::fake()->image('grande.png')->size(5121)]);
        $path='/dueno/productos/'.$this->product->id;
        $this->loginAs()->from($path)->put($path,$data)->assertRedirect($path)->assertSessionHasErrors();
        $response=$this->getWithSession($path)->assertOk();
        $dom=new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $this->assertNotNull($dom->getElementById('tiers'));
        $this->assertNotNull($dom->getElementById('tiers-error'));
        foreach (['sku','name','description','compatibility','category_id','unit_price','photo','tiers-0-minimum_quantity','tiers-0-unit_price'] as $field) {
            $element=$dom->getElementById($field);
            $this->assertNotNull($element,$field);
            $this->assertSame('true',$element->getAttribute('aria-invalid'),$field);
            $this->assertStringContainsString($field.'-error',$element->getAttribute('aria-describedby'));
            $this->assertNotNull($dom->getElementById($field.'-error'));
            $response->assertSee('href="#'.$field.'"',false);
        }
    }

    private function orientedJpeg(int $orientation): UploadedFile
    {
        $image=imagecreatetruecolor(96,64);
        foreach ([[0,0,47,31,255,0,0],[48,0,95,31,0,255,0],[0,32,47,63,0,0,255],[48,32,95,63,255,255,0]] as [$x1,$y1,$x2,$y2,$red,$green,$blue]) {
            imagefilledrectangle($image,$x1,$y1,$x2,$y2,imagecolorallocate($image,$red,$green,$blue));
        }
        ob_start();imagejpeg($image,null,100);$jpeg=ob_get_clean();imagedestroy($image);
        $exif="Exif\0\0II".pack('vVv',42,8,1).pack('vvVv',0x0112,3,1,$orientation)."\0\0".pack('V',0);
        $comment='MARCADOR-PRIVADO-METADATOS-ORIGINALES';
        $jpeg=substr($jpeg,0,2)."\xFF\xE1".pack('n',strlen($exif)+2).$exif."\xFF\xFE".pack('n',strlen($comment)+2).$comment.substr($jpeg,2).$comment;
        return UploadedFile::fake()->createWithContent('orientada.jpg',$jpeg);
    }

    public function test_jpeg_normaliza_ocho_orientaciones_y_webp_real_sin_metadatos(): void
    {
        $this->loginAs();
        $colors=['R'=>[255,0,0],'G'=>[0,255,0],'B'=>[0,0,255],'Y'=>[255,255,0]];
        $corners=[1=>['R','G','B','Y'],2=>['G','R','Y','B'],3=>['Y','B','G','R'],4=>['B','Y','R','G'],
            5=>['R','B','G','Y'],6=>['B','R','Y','G'],7=>['Y','G','B','R'],8=>['G','Y','R','B']];
        foreach ($corners as $orientation=>$expected) {
            $file=$this->orientedJpeg($orientation);
            $this->assertSame($orientation,(int)exif_read_data($file->getRealPath(),'IFD0',true)['IFD0']['Orientation']);
            $this->put('/dueno/productos/'.$this->product->id,$this->data($this->product->fresh())+['photo'=>$file])->assertRedirect();
            $item=$this->product->fresh();$bytes=Storage::disk('catalog')->get($item->photo_path);
            $size=getimagesizefromstring($bytes);
            $this->assertSame(IMAGETYPE_WEBP,$size[2]);
            $this->assertSame($orientation>=5?[64,96]:[96,64],array_slice($size,0,2));
            $this->assertStringNotContainsString('MARCADOR-PRIVADO-METADATOS-ORIGINALES',$bytes);
            $this->assertStringNotContainsString('Exif',$bytes);
            $this->assertStringNotContainsString('EXIF',$bytes);
            $decoded=imagecreatefromstring($bytes);
            foreach ([[8,8],[$size[0]-9,8],[8,$size[1]-9],[$size[0]-9,$size[1]-9]] as $index=>[$x,$y]) {
                $pixel=imagecolorsforindex($decoded,imagecolorat($decoded,$x,$y));
                foreach (['red','green','blue'] as $channel=>$name) {
                    $this->assertLessThan(35,abs($pixel[$name]-$colors[$expected[$index]][$channel]),'Orientación'.$orientation.' '.$index.' '.$name);
                }
            }
            imagedestroy($decoded);
            $this->assertSame($bytes,$this->get('/dueno/productos/'.$item->id.'/foto')->assertOk()->streamedContent());
            $this->assertSame($bytes,$this->get('/productos/DEMO-C1?foto=1')->assertOk()->streamedContent());
        }
    }

    public function test_leyendas_distinguen_foto_subida_y_svg_interno(): void
    {
        $this->get('/')->assertSee('alt="Ilustración de '.$this->product->name.'"',false);
        $this->get('/productos/DEMO-C1')->assertSee('Ilustración · Fotografía pendiente · Producto ficticio');
        $this->loginAs()->put('/dueno/productos/'.$this->product->id,$this->data()+['photo'=>UploadedFile::fake()->image('principal.png')])->assertRedirect();
        $name=$this->product->fresh()->name;
        $this->get('/')->assertSee('alt="Fotografía de '.$name.'"',false)->assertSee('Fotografía · Producto ficticio');
        $this->get('/productos/DEMO-C1')->assertSee('alt="Fotografía de '.$name.'"',false)->assertSee('Fotografía · Producto ficticio')->assertDontSee('Fotografía pendiente')->assertDontSee('Ilustración');
    }

    public function test_conflicto_conserva_borrador_escapado_sin_secretos_y_aislado(): void
    {
        $this->loginAs();
        $stale=$this->data();
        $this->put('/dueno/productos/'.$this->product->id,$stale)->assertRedirect();
        $draft='Borrador </textarea><script>alert(99)</script>';
        $stale['description']=$draft;$stale['_token']='TOKEN-NO-COPIAR';
        $stale['photo']=UploadedFile::fake()->image('ARCHIVO-NO-COPIAR.png');
        $response=$this->put('/dueno/productos/'.$this->product->id,$stale)->assertConflict()->assertSee('Copia de tu borrador')->assertSee($draft)->assertDontSee('<script>',false)
            ->assertSee('href="'.route('owner.edit',$this->product).'"',false)->assertDontSee('TOKEN-NO-COPIAR')->assertDontSee('ARCHIVO-NO-COPIAR');
        $this->assertSame($this->data()['description'],$this->product->fresh()->description);
        $adjust=$this->adjustment();
        $this->post('/dueno/productos/'.$this->product->id.'/stock',$adjust)->assertRedirect();
        $adjust['reason']='Motivo <script>sin ejecutar</script>';$adjust['_token']='TOKEN-NO-COPIAR';
        $this->post('/dueno/productos/'.$this->product->id.'/stock',$adjust)->assertConflict()->assertSee($adjust['reason'])->assertDontSee('<script>',false)
            ->assertSee('href="'.route('owner.stock',$this->product).'"',false)->assertDontSee($adjust['operation_id'])->assertDontSee('TOKEN-NO-COPIAR');
        $this->loginAs($this->other)->put('/dueno/productos/'.$this->product->id,$stale)->assertNotFound()->assertDontSee('Copia de tu borrador')->assertDontSee($draft);
        $this->loginAs()->put('/dueno/productos/'.$this->product->id,$stale+['password'=>'CLAVE-NO-COPIAR'])->assertSessionHasErrors('password');
        $this->assertArrayNotHasKey('password',session()->getOldInput());
    }

    public function test_foto_abierta_antes_del_reemplazo_se_transmite_y_ausente_da404(): void
    {
        $this->loginAs()->put('/dueno/productos/'.$this->product->id,$this->data()+['photo'=>UploadedFile::fake()->image('anterior.png',40,30)])->assertRedirect();
        $item=$this->product->fresh();$old=Storage::disk('catalog')->get($item->photo_path);
        $private=$this->get('/dueno/productos/'.$item->id.'/foto')->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $public=$this->get('/productos/DEMO-C1?foto=1')->assertOk()->assertHeader('Cache-Control','no-store, private');
        $privateStream=(new \ReflectionFunction($private->baseResponse->getCallback()))->getStaticVariables()['stream'];
        $publicStream=(new \ReflectionFunction($public->baseResponse->getCallback()))->getStaticVariables()['stream'];
        $this->assertIsResource($privateStream);$this->assertIsResource($publicStream);
        $this->put('/dueno/productos/'.$item->id,$this->data($item)+['photo'=>UploadedFile::fake()->image('nueva.png',60,50)])->assertRedirect();
        $this->assertSame($old,$private->streamedContent());$this->assertSame($old,$public->streamedContent());
        $this->assertFalse(is_resource($privateStream));$this->assertFalse(is_resource($publicStream));
        Storage::disk('catalog')->delete($this->product->fresh()->photo_path);
        $this->get('/dueno/productos/'.$item->id.'/foto')->assertNotFound();
        $this->get('/productos/DEMO-C1?foto=1')->assertNotFound();
    }

    public function test_ocultar_via_endpoint_revalida_tienda_ficha_foto_y_carro(): void
    {
        $this->loginAs()->put('/dueno/productos/'.$this->product->id,$this->data()+['photo'=>UploadedFile::fake()->image('principal.png')])->assertRedirect();
        $this->post('/carro/DEMO-C1',['quantity'=>6])->assertRedirect('/carro');
        $item=$this->product->fresh();
        $this->post('/dueno/productos/'.$item->id.'/estado',['action'=>'hide','version'=>$item->version])->assertRedirect();
        $this->assertFalse($item->fresh()->published);$this->assertNull($item->fresh()->archived_at);
        $this->get('/')->assertDontSee('DEMO-C1');$this->get('/productos/DEMO-C1')->assertNotFound();
        $this->get('/productos/DEMO-C1?foto=1')->assertNotFound();$this->post('/carro/DEMO-C1',['quantity'=>1])->assertNotFound();
        $this->get('/carro')->assertSee('dejó de estar disponible')->assertSee('Tu carro está vacío');
        $this->assertSame(40,$item->fresh()->stock);$this->assertDatabaseCount('stock_movements',0);
    }
}
