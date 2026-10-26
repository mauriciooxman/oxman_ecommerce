<?php

// Proceso auxiliar de verify-owner-runtime.php; solo base exclusiva de pruebas.
require __DIR__.'/../app/vendor/autoload.php';
$app = require __DIR__.'/../app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $db['host'] !== '127.0.0.1' || (string)$db['port'] !== '3307'
    || $db['database'] !== 'oxman_demo_test' || !empty($db['url']) || !empty($db['unix_socket'])) { exit(2); }
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$held = false;
try {
    $user = App\Models\User::findOrFail($input['user']);
    if ($input['hold'] ?? false) {
        Illuminate\Support\Facades\DB::beginTransaction();
        App\Models\Product::lockForUpdate()->findOrFail($input['product']);
        $held = true;
    }
    touch($input['ready']);
    $deadline = microtime(true) + 15;
    while (!is_file($input['release'])) {
        if (microtime(true) > $deadline) { throw new RuntimeException('Sin señal de inicio'); }
        usleep(10000);
    }
    $movement = app(App\Services\StockService::class)->adjust($user, $input['product'], $input['data']);
    if ($held) { Illuminate\Support\Facades\DB::commit(); }
    echo json_encode(['status'=>200,'after'=>$movement->after,'delta'=>$movement->delta]);
} catch (Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $error) {
    if ($held) { Illuminate\Support\Facades\DB::rollBack(); }
    echo json_encode(['status'=>$error->getStatusCode()]);
} catch (Throwable $error) {
    if ($held) { Illuminate\Support\Facades\DB::rollBack(); }
    echo json_encode(['status'=>500,'type'=>get_class($error)]);
    exit(1);
}
