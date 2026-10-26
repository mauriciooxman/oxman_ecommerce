<?php

// HTTP con cookies/CSRF reales y procesos MySQL independientes, sin tocar oxman_demo.
// Ejecutar con PHPRC local. No ejecutar a la vez que PHPUnit (misma base protegida).
$root = dirname(__DIR__);
$runtime = $root.'/.local/owner-runtime-'.bin2hex(random_bytes(5));
mkdir($runtime, 0700, true);
$overrides = ['APP_ENV'=>'local','APP_DEBUG'=>'false','DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3307','DB_DATABASE'=>'oxman_demo_test',
    'DB_USERNAME'=>'oxman_demo','DB_PASSWORD'=>'demo-local-only','DB_URL'=>'','DB_SOCKET'=>'','SESSION_DRIVER'=>'file','SESSION_COOKIE'=>'owner_runtime_session','SESSION_SECURE_COOKIE'=>'false','CACHE_STORE'=>'file'];
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
function request($client,string $method,string $path,array $data=[],int $port=8005): array {
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
    $process=proc_open([PHP_BINARY,$root.'/tools/owner-stock-worker.php'],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$root.'/app',$environment,['bypass_shell'=>true,'create_no_window'=>true]);
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
try {
    $suffix=bin2hex(random_bytes(5));
    $store=App\Models\Store::create(['slug'=>'runtime-'.$suffix,'name'=>'Tienda de prueba efímera','initials'=>'RT']);
    $category=$store->categories()->create(['name'=>'Pruebas ficticias']);
    $password=bin2hex(random_bytes(24));
    $user=new App\Models\User;
    $user->forceFill(['name'=>'Dueño de prueba efímero','email'=>'runtime-'.$suffix.'@example.test','password'=>$password,'store_id'=>$store->id,'role'=>'owner','credential_version'=>1])->save();
    $product=$store->products()->create(['sku'=>'RUNTIME','name'=>'Producto ficticio runtime','category'=>'Pruebas ficticias','category_id'=>$category->id,'description'=>'Ejemplo','compatibility'=>'Ejemplo','image'=>'placeholder.svg','stock'=>5,'unit_price'=>3000,'published'=>false,'version'=>1]);
    server(8005,$root.'/app/public/index.php');
    $client=browser();
    $first=request($client,'GET','/dueno/ingresar');
    check($first['status']===200,'Ingreso disponible');
    $beforeCookies=curl_getinfo($client,CURLINFO_COOKIELIST);
    check(request($client,'POST','/dueno/ingresar',['email'=>$user->email,'password'=>$password])['status']===419,'CSRF ausente no bloqueó');
    check(request($client,'POST','/dueno/ingresar',['_token'=>'incorrecto','email'=>$user->email,'password'=>$password])['status']===419,'CSRF inválido no bloqueó');
    check(request($client,'POST','/dueno/ingresar',['_token'=>token($first),'email'=>$user->email,'password'=>$password])['status']===302,'Ingreso real');
    check($beforeCookies!==curl_getinfo($client,CURLINFO_COOKIELIST),'ID de sesión no regenerado');
    $private=request($client,'GET','/dueno');
    check($private['status']===200 && str_contains($private['body'],'Producto ficticio runtime'),'Sesión real no permite leer tienda propia');
    $copy=browser(); foreach(curl_getinfo($client,CURLINFO_COOKIELIST) as $cookie) { curl_setopt($copy,CURLOPT_COOKIELIST,$cookie); }
    $stock=request($client,'GET','/dueno/productos/'.$product->id.'/stock');
    check($stock['status']===200,'Formulario de ajuste');
    $operation=(string)Illuminate\Support\Str::uuid();
    $adjust=['_token'=>token($stock),'mode'=>'variation','quantity'=>1,'reason'=>'Verificación HTTP ficticia','version'=>1,'operation_id'=>$operation];
    check(request($client,'POST','/dueno/productos/'.$product->id.'/stock',array_diff_key($adjust,['_token'=>1]))['status']===419,'Mutación sin CSRF');
    check(request($client,'POST','/dueno/productos/'.$product->id.'/stock',$adjust)['status']===302,'Ajuste HTTP con CSRF válido');
    check(request($client,'POST','/dueno/productos/'.$product->id.'/stock',$adjust)['status']===302,'Reintento HTTP');
    check($product->fresh()->stock===6 && $product->movements()->count()===1,'Ajuste HTTP no idempotente');
    $bad=request($client,'POST','/dueno/productos/'.$product->id.'/stock',array_replace($adjust,['operation_id'=>(string)Illuminate\Support\Str::uuid(),'reason'=>'','version'=>2]));
    check($bad['status']===302,'Validación HTTP debe volver al formulario');
    check(request($client,'POST','/dueno/salir',['_token'=>token($stock)])['status']===302,'Salida real');
    check(request($client,'GET','/dueno')['status']===302 && request($copy,'GET','/dueno')['status']===302,'Cookie anterior continúa autorizada');
    // Expiración del archivo de una sesión auténtica, dentro de su vigencia configurada.
    $login=request($client,'GET','/dueno/ingresar');
    check(request($client,'POST','/dueno/ingresar',['_token'=>token($login),'email'=>$user->email,'password'=>$password])['status']===302,'Segundo ingreso');
    $sessionId=null;
    foreach(curl_getinfo($client,CURLINFO_COOKIELIST) as $cookie) {
        $parts=explode("\t",$cookie);
        if (($parts[5]??'')==='owner_runtime_session') {
            $decrypted=app('encrypter')->decrypt(urldecode($parts[6]),false);
            $sessionId=substr($decrypted,strrpos($decrypted,'|')+1);
        }
    }
    check(is_string($sessionId) && preg_match('/^[a-zA-Z0-9]{40}$/D',$sessionId)===1,'No se resolvió la sesión propia de prueba');
    $sessionPath=config('session.files').'/'.$sessionId;
    check(is_file($sessionPath),'No existe archivo de sesión propio');
    touch($sessionPath,time()-(config('session.lifetime')+1)*60);
    check(request($client,'GET','/dueno')['status']===302,'Sesión expirada conserva acceso');
    // La versión de credencial revoca cookies auténticas.
    $login=request($client,'GET','/dueno/ingresar');
    check(request($client,'POST','/dueno/ingresar',['_token'=>token($login),'email'=>$user->email,'password'=>$password])['status']===302,'Tercer ingreso');
    $user->forceFill(['credential_version'=>2])->save();
    check(request($client,'GET','/dueno')['status']===302,'Credencial revocada conserva acceso');
    unset($password);
    // Dos conexiones compiten bajo un bloqueo real del mismo producto.
    app(App\Services\StockService::class)->adjust($user, $product->id, ['mode'=>'variation','quantity'=>-1,'reason'=>'Preparar stock5 de carrera ficticia','version'=>2,'operation_id'=>(string)Illuminate\Support\Str::uuid()]);
    Illuminate\Support\Facades\DB::beginTransaction();
    App\Models\Product::lockForUpdate()->findOrFail($product->id);
    $release=$runtime.'/race-start';
    $base=['user'=>$user->id,'product'=>$product->id,'release'=>$release,'hold'=>false];
    $payload=['mode'=>'variation','quantity'=>-4,'reason'=>'Baja competidora ficticia','version'=>3];
    $a=worker($base+['ready'=>$runtime.'/a-ready','data'=>$payload+['operation_id'=>(string)Illuminate\Support\Str::uuid()]]);
    $b=worker($base+['ready'=>$runtime.'/b-ready','data'=>$payload+['operation_id'=>(string)Illuminate\Support\Str::uuid()]]);
    waitFile($runtime.'/a-ready');waitFile($runtime.'/b-ready');touch($release);usleep(150000);
    Illuminate\Support\Facades\DB::commit();
    $results=[result($a)['status'],result($b)['status']];sort($results);
    check($results===[200,409] && $product->fresh()->stock===1,'Carrera de bajas: estados='.json_encode($results).', stock='.$product->fresh()->stock);
    check($product->movements()->count()===3,'Carrera duplicó historial');
    // Un conteo obsoleto espera a una variación que ya tiene el bloqueo.
    $release2=$runtime.'/variation-release';
    $base2=['user'=>$user->id,'product'=>$product->id,'release'=>$release2];
    $variation=worker($base2+['hold'=>true,'ready'=>$runtime.'/variation-ready','data'=>['mode'=>'variation','quantity'=>2,'reason'=>'Variación nueva ficticia','version'=>4,'operation_id'=>(string)Illuminate\Support\Str::uuid()]]);
    waitFile($runtime.'/variation-ready');
    $count=worker(['user'=>$user->id,'product'=>$product->id,'release'=>$release,'hold'=>false,'ready'=>$runtime.'/count-ready','data'=>['mode'=>'count','quantity'=>0,'reason'=>'Conteo obsoleto ficticio','version'=>4,'operation_id'=>(string)Illuminate\Support\Str::uuid()]]);
    waitFile($runtime.'/count-ready');usleep(150000);touch($release2);
    check(result($variation)['status']===200 && result($count)['status']===409 && $product->fresh()->stock===3,'Conteo viejo pisó variación nueva');
    check((int)$product->movements()->sum('delta')===-2 && $product->movements()->count()===4,'Auditoría no concuerda con stock');
    server(8006,$root.'/tools/demo-share-router.php');
    foreach (['GET','POST'] as $method) { foreach (['/dueno','/dueno/ingresar','/dueno/salir','/dueno/productos/1','/%64ueno/ingresar'] as $path) {
        $blocked=request($client,$method,$path,[],8006);
        check($blocked['status']===404 && $blocked['body']==='Página no disponible.','El router dejó llegar el panel a Laravel');
    } }
    echo "HTTP real/CSRF/cookies/revocación + MySQL concurrente + router: $checks comprobaciones aprobadas.\n";
    echo "Carrera bajas: una aplicada y otra409, stock1; conteo viejo409 tras variación, stock3; historial atómico.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Fallo de verificación: '.$error->getMessage()."\n");
    $failed = true;
} finally {
    while (Illuminate\Support\Facades\DB::transactionLevel()>0) { Illuminate\Support\Facades\DB::rollBack(); }
    foreach ($servers as $server) { proc_terminate($server);proc_close($server); }
    if ($store) {
        // Solo fixtures propias de esta ejecución en la base de pruebas guardada.
        Illuminate\Support\Facades\DB::table('stock_movements')->where('store_id',$store->id)->delete();
        $store->products()->delete();
        App\Models\User::where('store_id',$store->id)->delete();
        $store->categories()->delete();
        $store->delete();
    }
}
exit(isset($failed) ? 1 : 0);
