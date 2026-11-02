<?php

// HTTP con cookies/CSRF reales y procesos MySQL independientes, sin tocar oxman_demo.
// Ejecutar con PHPRC local. No ejecutar a la vez que PHPUnit (misma base protegida).
$root = dirname(__DIR__);
$runtime = $root.'/.local/orders-runtime-'.bin2hex(random_bytes(5));
mkdir($runtime, 0700, true);
$overrides = ['APP_ENV'=>'local','APP_DEBUG'=>'false','DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3307','DB_DATABASE'=>'oxman_demo_test',
    'DB_USERNAME'=>'oxman_demo','DB_PASSWORD'=>'demo-local-only','DB_URL'=>'','DB_SOCKET'=>'','SESSION_DRIVER'=>'file','SESSION_COOKIE'=>'orders_runtime_session','SESSION_SECURE_COOKIE'=>'false','CACHE_STORE'=>'file'];
foreach ($overrides as $key=>$value) { putenv($key.'='.$value); $_ENV[$key]=$value; $_SERVER[$key]=$value; }
require $root.'/app/vendor/autoload.php';
$app = require $root.'/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $db['host'] !== '127.0.0.1' || (string)$db['port'] !== '3307'
    || $db['database'] !== 'oxman_demo_test' || !empty($db['url']) || !empty($db['unix_socket'])) { throw new RuntimeException('Guardia de pruebas: destino inválido'); }
$environment = array_merge(getenv(), $overrides);
$servers = [];
$store = null;
$checks = 0;
function check(bool $condition, string $description): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($description); }
    $checks++;
}
function waitFile(string $path): void {
    $deadline = microtime(true)+15;
    while (!is_file($path)) { if (microtime(true)>$deadline) { throw new RuntimeException('Proceso no alcanzó barrera'); } usleep(10000); }
}
function server(int $port, string $router) {
    global $root,$runtime,$environment,$servers;
    $occupied=@fsockopen('127.0.0.1',$port,$errno,$errstr,.1);
    if ($occupied) { fclose($occupied); throw new RuntimeException('Puerto de prueba ocupado: '.$port); }
    $process = proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$root.'/app/public',$router], [['pipe','r'],['file',$runtime.'/server-'.$port.'.log','a'],['file',$runtime.'/server-'.$port.'.log','a']], $pipes,$root.'/app',$environment,['bypass_shell'=>true,'create_no_window'=>true]);
    if (!is_resource($process)) { throw new RuntimeException('No se pudo iniciar servidor local de prueba'); }
    fclose($pipes[0]);
    $servers[]=$process;
    for ($i=0;$i<100;$i++) { $socket=@fsockopen('127.0.0.1',$port,$errno,$errstr,.1); if ($socket) { fclose($socket); return; } usleep(50000); }
    throw new RuntimeException('Servidor no responde');
}
function browser() {
    $handle=curl_init();
    curl_setopt_array($handle,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>10]);
    return $handle;
}
function request($client,string $method,string $path,array $data=[],int $port=8015): array {
    curl_setopt_array($client,[CURLOPT_URL=>'http://127.0.0.1:'.$port.$path,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_POSTFIELDS=>null]);
    if ($method!=='GET') { curl_setopt($client,CURLOPT_POSTFIELDS,http_build_query($data)); }
    $raw=curl_exec($client);
    if ($raw===false) { throw new RuntimeException('HTTP local falló'); }
    $size=curl_getinfo($client,CURLINFO_HEADER_SIZE);
    return ['status'=>curl_getinfo($client,CURLINFO_RESPONSE_CODE),'headers'=>substr($raw,0,$size),'body'=>substr($raw,$size)];
}
function token(array $response): string {
    if (!preg_match('/name="_token" value="([^"]+)"/',$response['body'],$match)) { throw new RuntimeException('Sin token CSRF'); }
    return html_entity_decode($match[1]);
}
function worker(array $input): array {
    global $root,$environment;
    $process=proc_open([PHP_BINARY,$root.'/tools/orders-worker.php'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$root.'/app',$environment,['bypass_shell'=>true,'create_no_window'=>true]);
    fwrite($pipes[0],json_encode($input)); fclose($pipes[0]);
    return [$process,$pipes];
}
function result(array $worker): array {
    [$process,$pipes]=$worker;
    $output=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $code=proc_close($process);
    if ($code!==0 || $error!=='') { throw new RuntimeException('Trabajador MySQL falló sin salida sensible'); }
    return json_decode($output,true,flags:JSON_THROW_ON_ERROR);
}
function formKey(array $response): string {
    if (!preg_match('/name="idempotency_key" value="([^"]+)"/', $response['body'], $match)) { throw new RuntimeException('Sin clave de revisión'); }
    return $match[1];
}
function postOptions($client, array $data, int $port): void {
    curl_setopt_array($client, [CURLOPT_URL => 'http://127.0.0.1:'.$port.'/checkout/confirmar', CURLOPT_CUSTOMREQUEST => 'POST', CURLOPT_POSTFIELDS => http_build_query($data)]);
}
function race(array $a, array $b, int $product): array {
    global $runtime;
    $id = bin2hex(random_bytes(4));
    $release = $runtime.'/'.$id.'-release';
    $heldByWorker = $a['hold'] ?? false;
    if (!$heldByWorker) {
        Illuminate\Support\Facades\DB::beginTransaction();
        App\Models\Product::whereKey($product)->lockForUpdate()->firstOrFail();
    }
    $workers = [];
    foreach ([$a, $b] as $i => $input) {
        $workers[] = worker([...$input, 'ready' => $runtime.'/'.$id.'-'.$i.'-ready', 'started' => $runtime.'/'.$id.'-'.$i.'-started', 'release' => $release]);
        if ($heldByWorker && $i === 0) { waitFile($runtime.'/'.$id.'-0-ready'); }
    }
    foreach ([0,1] as $i) { waitFile($runtime.'/'.$id.'-'.$i.'-ready'); }
    touch($release);
    foreach ([0,1] as $i) { waitFile($runtime.'/'.$id.'-'.$i.'-started'); }
    usleep(200000);
    if (!$heldByWorker) { Illuminate\Support\Facades\DB::commit(); }
    return [result($workers[0]), result($workers[1])];
}
try {
    $db = Illuminate\Support\Facades\DB::connection();
    $dataPath = str_replace('\\', '/', $db->selectOne('SELECT @@datadir AS path')->path);
    check(strtolower(rtrim($dataPath, '/')) === strtolower(str_replace('\\', '/', realpath($root.'/.local/mysql/data'))), 'Datadir inesperado');
    check(Illuminate\Support\Facades\Schema::hasTable('orders'), 'Primero ejecutar suite protegida para migrar pruebas');
    $store = App\Models\Store::firstOrCreate(['slug' => 'tecnologia-demo'], ['name' => 'Tienda ficticia de pruebas', 'initials' => 'TP']);
    $createdStore = $store->wasRecentlyCreated;
    $suffix = bin2hex(random_bytes(5));
    $product = $store->products()->create(['sku' => 'CHECKOUT-'.$suffix, 'name' => 'Accesorio ficticio carrera', 'category' => 'Prueba', 'description' => 'Ficticio', 'compatibility' => 'Ficticio', 'image' => 'placeholder.svg', 'stock' => 1, 'unit_price' => 3000, 'published' => true, 'version' => 1]);
    $fixtureIds = [$product->id];
    $keys = [];
    $user = App\Models\User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
    $ownerPassword = bin2hex(random_bytes(24));
    $user->forceFill(['password' => $ownerPassword, 'credential_version' => 1])->save();
    server(8015, $root.'/app/public/index.php');
    server(8016, $root.'/app/public/index.php');
    $clients = [browser(), browser()];
    $forms = [];
    $contact = ['nombre' => 'HTTP ficticio', 'telefono' => '000000000', 'correo' => 'ficticio@example.test', 'modalidad' => 'retiro'];
    foreach ($clients as $i => $client) {
        $page = request($client, 'GET', '/productos/'.$product->sku, [], 8015+$i);
        check($page['status'] === 200, 'Ficha HTTP');
        check(request($client, 'POST', '/carro/'.$product->sku, ['quantity' => 1, '_token' => token($page)], 8015+$i)['status'] === 302, 'Carro HTTP');
        $review = request($client, 'GET', '/checkout/revisar', [], 8015+$i);
        check($review['status'] === 200, 'Revisión HTTP');
        $forms[$i] = [...$contact, '_token' => token($review), 'idempotency_key' => formKey($review), 'unit_price' => 1, 'store_id' => 999];
        $keys[] = formKey($review);
    }
    check(request($clients[0], 'POST', '/checkout/confirmar', array_diff_key($forms[0], ['_token' => 1]))['status'] === 419, 'CSRF real ausente');
    check(request($clients[0], 'POST', '/checkout/confirmar', [...$forms[0], '_token' => 'invalido'])['status'] === 419, 'CSRF real inválido');
    // Dos servidores PHP y cookies distintas; MySQL tiene la fila bloqueada al arrancar ambos POST.
    $db->beginTransaction();
    App\Models\Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
    $multi = curl_multi_init();
    foreach ($clients as $i => $client) { postOptions($client, $forms[$i], 8015+$i); curl_multi_add_handle($multi, $client); }
    $deadline = microtime(true)+.3;
    do { curl_multi_exec($multi, $running); usleep(10000); } while (microtime(true)<$deadline);
    check($running === 2, 'Ambos POST deben estar en vuelo mientras MySQL bloquea la fila');
    $db->commit();
    do { curl_multi_exec($multi, $running); if ($running) { curl_multi_select($multi, .1); } } while ($running);
    $locations = [];
    foreach ($clients as $client) {
        $raw = curl_multi_getcontent($client);
        check(curl_getinfo($client, CURLINFO_RESPONSE_CODE) === 302, 'Respuesta de carrera HTTP');
        preg_match('/\r\nLocation: ([^\r\n]+)/i', $raw, $match);
        $locations[] = $match[1] ?? '';
        curl_multi_remove_handle($multi, $client);
    }
    curl_multi_close($multi);
    $successes = array_keys(array_filter($locations, fn ($location) => str_contains($location, '/checkout/confirmacion/')));
    check(count($successes) === 1 && count(array_filter($locations, fn ($location) => str_contains($location, '/checkout/revisar'))) === 1, 'Una confirmación y un rechazo por stock');
    $winner = $successes[0];
    $loser = 1-$winner;
    $order = App\Models\Order::whereIn('idempotency_key', $keys)->sole();
    check($product->fresh()->stock_reservado === 1 && $order->lines()->sole()->precio_unitario === 3000, 'Reserva única y precio del servidor');
    $failedReview = request($clients[$loser], 'GET', '/checkout/revisar', [], 8015+$loser);
    check($failedReview['status'] === 302 && str_contains($failedReview['headers'], 'Location:'), 'Sin unidades, revisión regresa al catálogo');
    $confirmation = request($clients[$winner], 'GET', '/checkout/confirmacion/'.$order->reference, [], 8015+$winner);
    check($confirmation['status'] === 200 && str_contains($confirmation['body'], $order->access_token), 'Referencia y enlace privado HTTP');
    check(request(browser(), 'GET', '/checkout/confirmacion/'.$order->reference)['status'] === 404, 'Referencia sola no autoriza');
    check(request($clients[$winner], 'POST', '/checkout/confirmar', $forms[$winner], 8015+$winner)['status'] === 302, 'Reintento HTTP');
    check(App\Models\Order::whereIn('idempotency_key', $keys)->count() === 1 && $product->fresh()->stock_reservado === 1, 'Reintento no duplica');
    $tracking = request(browser(), 'GET', '/pedido/seguimiento/'.$order->access_token);
    check($tracking['status'] === 200 && !str_contains($tracking['body'], 'ficticio@example.test') && !str_contains($tracking['body'], '000000000'), 'Seguimiento público con token sin contacto completo');
    check(str_contains(strtolower($tracking['headers']), 'no-store') && str_contains(strtolower($tracking['headers']), 'no-referrer'), 'Privacidad de caché/referer');
    $order->update(['modalidad' => 'despacho', 'estado_despacho' => 'cotizado', 'monto_despacho' => 4000]);
    $quote = request($clients[$winner], 'GET', '/pedido/seguimiento/'.$order->access_token, [], 8015+$winner);
    $quoteUrl = '/pedido/seguimiento/'.$order->access_token.'/aceptar-cotizacion';
    check(request($clients[$winner], 'POST', $quoteUrl, [], 8015+$winner)['status'] === 419, 'Aceptar sin CSRF bloqueado');
    check(request($clients[$winner], 'POST', $quoteUrl, ['_token' => token($quote)], 8015+$winner)['status'] === 302 && $order->fresh()->estado_despacho === 'aceptado', 'Aceptación HTTP con CSRF');

    // Carreras de servicios con conexiones independientes; fixtures nuevas en cada escenario.
    foreach (['ultima-unidad', 'misma-clave', 'variation', 'count', 'variation-first', 'count-first'] as $scenario) {
        $p = $product->replicate();
        $p->sku = 'RACE-'.$scenario.'-'.$suffix;
        $p->stock_reservado = 0;
        $p->save();
        $fixtureIds[] = $p->id;
        $key = (string)Illuminate\Support\Str::uuid(); $keys[] = $key;
        $a = ['action' => 'order', 'store' => $store->id, 'key' => $key, 'lines' => [$p->id => ['quantity' => 1, 'unit_price' => 3000]]];
        if (in_array($scenario, ['ultima-unidad', 'misma-clave'], true)) {
            $secondKey = $scenario === 'misma-clave' ? $key : (string)Illuminate\Support\Str::uuid(); $keys[] = $secondKey;
            $b = [...$a, 'key' => $secondKey];
        } else {
            $mode = explode('-', $scenario)[0];
            $b = ['action' => 'stock', 'user' => $user->id, 'product' => $p->id, 'data' => ['mode' => $mode, 'quantity' => $mode === 'count' ? 0 : -1, 'reason' => 'Carrera ficticia', 'version' => 1, 'operation_id' => (string)Illuminate\Support\Str::uuid()]];
            if (str_ends_with($scenario, '-first')) { [$a, $b] = [[...$b, 'hold' => true], $a]; }
        }
        $results = race($a, $b, $p->id);
        $statuses = array_column($results, 'status'); sort($statuses);
        check($statuses === ($scenario === 'misma-clave' ? [200,200] : [200,422]), 'Carrera '.$scenario.': '.json_encode($statuses));
        $fresh = $p->fresh();
        check($fresh->stock_reservado <= $fresh->stock && $fresh->stock >= 0, 'Invariante stock >= reserva: '.$scenario);
        $count = App\Models\OrderLine::where('product_id', $p->id)->count();
        check($count === $fresh->stock_reservado, 'Pedido/reserva atómicos: '.$scenario);
        if ($scenario === 'misma-clave') { check($results[0]['order'] === $results[1]['order'], 'Misma clave devuelve mismo pedido'); }
        echo 'MySQL concurrente '.$scenario.': '.json_encode($statuses).', stock='.$fresh->stock.', reserva='.$fresh->stock_reservado."\n";
    }
    $checkoutChecks = $checks;
    $ownerClient = browser();
    $login = request($ownerClient, 'GET', '/dueno/ingresar');
    check(request($ownerClient, 'POST', '/dueno/ingresar', ['_token' => token($login), 'email' => $user->email, 'password' => $ownerPassword])['status'] === 302, 'Ingreso del dueño HTTP');
    unset($ownerPassword);
    check(request($ownerClient, 'GET', '/dueno/pedidos')['status'] === 200, 'Listado privado HTTP');
    $detail = request($ownerClient, 'GET', '/dueno/pedidos/'.$order->id);
    check($detail['status'] === 200 && str_contains($detail['body'], $order->reference), 'Detalle privado HTTP');
    $ownerCsrf = token($detail);
    foreach (['cancelar', 'cotizar-despacho', 'confirmar-pago', 'preparacion', 'entregar'] as $action) {
        check(request($ownerClient, 'POST', '/dueno/pedidos/'.$order->id.'/'.$action)['status'] === 419, 'CSRF dueño: '.$action);
    }
    check(request(browser(), 'GET', '/dueno/pedidos')['status'] === 302, 'Sin sesión no permite listar');
    foreach (['confirmar-pago', 'preparacion', 'entregar'] as $action) {
        check(request($ownerClient, 'POST', '/dueno/pedidos/'.$order->id.'/'.$action, ['_token' => $ownerCsrf])['status'] === 302, 'Gestión HTTP: '.$action);
    }
    check($product->fresh()->stock === 0 && $product->fresh()->stock_reservado === 0, 'Salida HTTP descuenta y libera');
    check(request($ownerClient, 'POST', '/dueno/pedidos/'.$order->id.'/entregar', ['_token' => $ownerCsrf])['status'] === 409, 'Entrega HTTP repetida');
    $tracking = request($clients[$winner], 'GET', '/pedido/seguimiento/'.$order->access_token, [], 8015+$winner);
    check(str_contains($tracking['body'], 'Total con despacho aceptado') && !str_contains($tracking['body'], 'Despacho pendiente de cotización'), 'Seguimiento entregado conserva cotización');

    $service = app(App\Services\OrderService::class);
    foreach (['cancel-double', 'deliver-double', 'cancel-deliver', 'cancel-two-orders', 'accept-cancel'] as $scenario) {
        $p = $product->replicate();
        $p->sku = 'MANAGE-'.$scenario.'-'.$suffix;
        $p->stock = 10; $p->stock_reservado = 0; $p->version = 1; $p->save();
        $fixtureIds[] = $p->id;
        $newOrder = function () use ($service, $store, $p, &$keys) {
            $key = (string)Illuminate\Support\Str::uuid(); $keys[] = $key;
            return $service->confirmar($store, ['nombre' => 'Gestión ficticia', 'telefono' => '000000000', 'modalidad' => 'despacho', 'direccion' => 'Ficticia', 'comuna' => 'Ficticia', 'region' => 'Ficticia'], $key, [$p->id => ['quantity' => 2, 'unit_price' => 3000]]);
        };
        $managed = $newOrder();
        $service->cotizarDespacho($managed, $user, 1000);
        if ($scenario !== 'accept-cancel') {
            $service->aceptarCotizacion($store, $managed->access_token);
            $service->confirmarPago($managed, $user);
            $service->pasarAPreparacion($managed, $user);
        }
        $a = ['action' => str_starts_with($scenario, 'deliver') ? 'deliver' : 'cancel', 'user' => $user->id, 'order' => $managed->id];
        $b = $a;
        if ($scenario === 'cancel-deliver') { $b['action'] = 'deliver'; }
        if ($scenario === 'cancel-two-orders') { $second = $newOrder(); $b['order'] = $second->id; }
        if ($scenario === 'accept-cancel') { $b['action'] = 'accept'; }
        $results = race($a, $b, $p->id);
        $statuses = array_column($results, 'status'); sort($statuses);
        check($scenario === 'accept-cancel' ? in_array($statuses, [[200,200], [200,422]], true) : $statuses === match ($scenario) {
            'cancel-double', 'deliver-double' => [200,409], 'cancel-deliver' => [200,422], default => [200,200],
        }, 'Carrera gestión '.$scenario.': '.json_encode($statuses));
        $fresh = $p->fresh(); $current = $managed->fresh();
        $delivered = $current->estado_pedido === 'despachado';
        check($fresh->stock_reservado === 0 && $fresh->stock === ($delivered ? 8 : 10), 'Stock/reserva finales: '.$scenario);
        check($p->movements()->count() === ($delivered ? 1 : 0), 'Auditoría única: '.$scenario);
        check($managed->events()->where('tipo', 'estado_pedido')->whereIn('valor_nuevo', ['cancelado', 'despachado'])->count() === 1, 'Un cierre por pedido: '.$scenario);
        check($managed->lines()->sole()->precio_unitario === 3000, 'Snapshot conservado: '.$scenario);
        if ($current->estado_pedido === 'cancelado') {
            try { $service->registrarEntrega($managed, $user); throw new RuntimeException('Entrega de cancelado aceptada'); }
            catch (Symfony\Component\HttpKernel\Exception\HttpException $error) { check($error->getStatusCode() === 422, 'Cancelado no se entrega'); }
            $events = $managed->events()->orderBy('id')->get();
            check($events->last()->valor_nuevo === 'cancelado', 'No se acepta cotización después de cancelar');
        }
        echo 'MySQL gestión '.$scenario.': '.json_encode($statuses).', stock='.$fresh->stock.', reserva='.$fresh->stock_reservado."\n";
    }
    // Simula interrupción tras commit y antes del callback, sin modificar un aviso ya capturado.
    app()->instance(App\Services\OwnerOrderNotice::class, new class extends App\Services\OwnerOrderNotice {
        public function attempt(int $orderId): void {}
    });
    $p->refresh();
    $key = (string)Illuminate\Support\Str::uuid(); $keys[] = $key;
    $pending = $service->confirmar($store, ['nombre' => 'Aviso ficticio', 'telefono' => '000000000', 'modalidad' => 'retiro'], $key,
        [$p->id => ['quantity' => 1, 'unit_price' => 3000]]);
    app()->forgetInstance(App\Services\OwnerOrderNotice::class);
    check(Illuminate\Support\Facades\DB::table('order_owner_notices')->where('order_id', $pending->id)->value('status') === 'pending', 'Aviso pendiente durable');
    $results = race(['action' => 'notice', 'order' => $pending->id], ['action' => 'notice', 'order' => $pending->id], $p->id);
    check(array_column($results, 'status') === [200,200], 'Intentos concurrentes de aviso');
    check(Illuminate\Support\Facades\DB::table('order_owner_notices')->where('order_id', $pending->id)->value('status') === 'captured', 'Una captura persistida');
    $capture = file_get_contents($root.'/app/storage/app/private/order-notices/mail.log');
    check(substr_count($capture, 'Subject: Nuevo pedido '.$pending->reference) === 1, 'Correo concurrente no duplicado');
    $beforeCount = Illuminate\Support\Facades\DB::table('order_owner_notices')->count();
    $service->confirmar($store, ['nombre' => 'Aviso ficticio', 'telefono' => '000000000', 'modalidad' => 'retiro'], $key, []);
    check(Illuminate\Support\Facades\DB::table('order_owner_notices')->count() === $beforeCount, 'Reintento de pedido no genera otro aviso');
    echo "HTTP/MySQL: $checks comprobaciones aprobadas ($checkoutChecks regresión 3A; 48 gestión 3B; 5 avisos).\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Fallo de verificación: '.$error->getMessage()."\n");
    $failed = true;
} finally {
    while (Illuminate\Support\Facades\DB::transactionLevel() > 0) { Illuminate\Support\Facades\DB::rollBack(); }
    foreach ($servers as $server) { proc_terminate($server); proc_close($server); }
    // Solo fixtures creadas por esta ejecución; no borra la tienda ni catálogo existente.
    if (!empty($keys)) { App\Models\Order::whereIn('idempotency_key', $keys)->delete(); }
    if (!empty($fixtureIds)) {
        Illuminate\Support\Facades\DB::table('stock_movements')->whereIn('product_id', $fixtureIds)->delete();
        App\Models\Product::whereIn('id', $fixtureIds)->delete();
    }
    if (isset($user)) { $user->delete(); }
    if (!empty($createdStore)) { $store->delete(); }
}
exit(!empty($failed) ? 1 : 0);
