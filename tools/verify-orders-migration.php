<?php

// Respaldo/restauración de prueba y migración incremental; nunca migrate:fresh en demo.
require __DIR__.'/../app/vendor/autoload.php';
$app = require __DIR__.'/../app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
$config = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $config['database'] !== 'oxman_demo'
    || $config['host'] !== '127.0.0.1' || (string)$config['port'] !== '3307'
    || !empty($config['url']) || !empty($config['unix_socket'])) { throw new RuntimeException('Destino no autorizado'); }
$datadir = str_replace('\\', '/', DB::selectOne('SELECT @@datadir AS path')->path);
$expected = str_replace('\\', '/', realpath(__DIR__.'/../.local/mysql/data'));
if (rtrim(strtolower($datadir), '/') !== strtolower($expected)) { throw new RuntimeException('Datadir ajeno'); }
$backup = __DIR__.'/../.local/backups/'.(in_array('--notices', $argv, true) ? 'antes-avisos-pedidos-20261102.sql' : 'antes-pedidos-3a-20261102.sql');
$manifest = $backup.'.json';
$snapshot = function ($connection) {
    $result = [];
    foreach ($connection->select('SHOW TABLES') as $table) {
        $name = array_values((array)$table)[0];
        $rows = array_map(fn ($row) => json_encode($row, JSON_UNESCAPED_UNICODE), $connection->table($name)->get()->all());
        sort($rows);
        $result[$name] = ['count' => count($rows), 'hash' => hash('sha256', implode("\n", $rows))];
    }
    ksort($result);
    return $result;
};
$run = function (array $command, $input = null) use ($config) {
    $pipes = [];
    $process = proc_open($command, [0 => $input ? ['file', $input, 'r'] : ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, [...getenv(), 'MYSQL_PWD' => $config['password']]);
    if (!$input) { fclose($pipes[0]); }
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { throw new RuntimeException('Fallo herramienta MySQL: '.$error); }
};
echo 'Destino MySQL/datadir comprobado. Migraciones: '.DB::table('migrations')->count().'; tabla orders existe: '.(int)Illuminate\Support\Facades\Schema::hasTable('orders')."\n";
if (($argv[1] ?? '') === '--prepare') {
    if (file_exists($backup)) { throw new RuntimeException('Respaldo ya existe; conservar y comprobar antes de repetir'); }
    $before = $snapshot(DB::connection());
    $bin = 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/';
    $args = ['--no-defaults', '--protocol=TCP', '--host=127.0.0.1', '--port=3307', '--user='.$config['username']];
    $run([$bin.'mysqldump.exe', ...$args, '--single-transaction', '--no-tablespaces', '--result-file='.$backup, 'oxman_demo']);
    if (!str_contains(file_get_contents($backup), '-- Dump completed')) { throw new RuntimeException('Dump incompleto'); }
    $run([$bin.'mysql.exe', ...$args, '--database=oxman_demo_test'], $backup);
    config(['database.connections.backup_check' => [...$config, 'database' => 'oxman_demo_test']]);
    if ($before !== $snapshot(DB::connection('backup_check')) || $before !== $snapshot(DB::connection())) { throw new RuntimeException('Respaldo no reproduce los datos o demo cambió'); }
    file_put_contents($manifest, json_encode(['sha256' => hash_file('sha256', $backup), 'tables' => $before], JSON_PRETTY_PRINT));
    echo "Respaldo nuevo restaurado y comparado en oxman_demo_test: todas las tablas/filas coinciden.\n";
} elseif (($argv[1] ?? '') === '--apply') {
    $proof = json_decode(file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
    if ($proof['sha256'] !== hash_file('sha256', $backup) || $proof['tables'] !== $snapshot(DB::connection())) { throw new RuntimeException('Respaldo desactualizado o modificado; no migrar'); }
    $old = [];
    foreach (array_keys($proof['tables']) as $table) {
        $old[$table] = ['columns' => Illuminate\Support\Facades\Schema::getColumnListing($table), 'rows' => DB::table($table)->get()->map(fn ($row) => (array)$row)->all()];
    }
    if (Artisan::call('migrate', ['--force' => true]) !== 0) { throw new RuntimeException('Migración falló: revisar sin repetir'); }
    foreach ($old as $table => $snapshot) {
        if ($table === 'migrations') { continue; }
        $after = DB::table($table)->select($snapshot['columns'])->get()->map(fn ($row) => (array)$row)->all();
        if ($snapshot['rows'] !== $after) { throw new RuntimeException('Datos previos alterados: '.$table); }
    }
    echo 'Migración aplicada: datos previos íntegros; '.DB::table('products')->count().' productos / '.DB::table('price_tiers')->count()." tramos.\n";
} else { echo "Solo inspección. Usar --prepare o --apply.\n"; }
