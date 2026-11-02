<?php

// Conexión MySQL independiente para carreras de checkout y ajustes. Solo pruebas.
require __DIR__.'/../app/vendor/autoload.php';
$app = require __DIR__.'/../app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $db['host'] !== '127.0.0.1' || (string)$db['port'] !== '3307'
    || $db['database'] !== 'oxman_demo_test' || !empty($db['url']) || !empty($db['unix_socket'])) { exit(2); }
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
try {
    if ($input['hold'] ?? false) {
        Illuminate\Support\Facades\DB::beginTransaction();
        App\Models\Product::whereKey($input['product'])->lockForUpdate()->firstOrFail();
    }
    touch($input['ready']);
    $deadline = microtime(true) + 15;
    while (!is_file($input['release'])) {
        if (microtime(true) > $deadline) { throw new RuntimeException('Sin señal de inicio'); }
        usleep(10000);
    }
    touch($input['started']);
    if ($input['action'] === 'order') {
        $order = app(App\Services\OrderService::class)->confirmar(App\Models\Store::findOrFail($input['store']),
            ['nombre' => 'Carrera ficticia', 'telefono' => '000000000', 'modalidad' => 'retiro'], $input['key'], $input['lines']);
        $result = ['status' => 200, 'order' => $order->id];
    } elseif ($input['action'] === 'notice') {
        app(App\Services\OwnerOrderNotice::class)->attempt($input['order']);
        $result = ['status' => 200];
    } elseif (in_array($input['action'], ['cancel', 'deliver', 'accept'], true)) {
        $service = app(App\Services\OrderService::class);
        $order = App\Models\Order::findOrFail($input['order']);
        if ($input['action'] === 'accept') {
            $service->aceptarCotizacion($order->store, $order->access_token);
        } else {
            $user = App\Models\User::findOrFail($input['user']);
            if ($input['action'] === 'cancel') { $service->cancelar($order, $user, 'Cancelación concurrente ficticia'); }
            else { $service->registrarEntrega($order, $user); }
        }
        $result = ['status' => 200, 'order' => $order->id];
    } else {
        $movement = app(App\Services\StockService::class)->adjust(App\Models\User::findOrFail($input['user']), $input['product'], $input['data']);
        $result = ['status' => 200, 'movement' => $movement->id];
    }
    if ($input['hold'] ?? false) { Illuminate\Support\Facades\DB::commit(); }
    echo json_encode($result);
} catch (Illuminate\Validation\ValidationException $error) {
    echo json_encode(['status' => 422]);
} catch (Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $error) {
    echo json_encode(['status' => $error->getStatusCode()]);
} catch (Throwable $error) {
    echo json_encode(['status' => 500, 'type' => get_class($error)]);
    exit(1);
} finally {
    while (Illuminate\Support\Facades\DB::transactionLevel() > 0) { Illuminate\Support\Facades\DB::rollBack(); }
}
