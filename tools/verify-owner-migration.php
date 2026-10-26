<?php

// Comparación incremental, exclusivamente sobre la demo ficticia respaldada.
require __DIR__.'/../app/vendor/autoload.php';
$app = require __DIR__.'/../app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $db['host'] !== '127.0.0.1' || (string)$db['port'] !== '3307'
    || $db['database'] !== 'oxman_demo' || !empty($db['url']) || !empty($db['unix_socket'])
    || !is_file(__DIR__.'/../.local/backups/antes-panel-20261025.sql') || filesize(__DIR__.'/../.local/backups/antes-panel-20261025.sql') < 1000) {
    throw new RuntimeException('Destino o respaldo inválido. No se ejecutaron migraciones.');
}
$columns = ['id','store_id','sku','name','category','description','compatibility','image','stock','unit_price','published','created_at','updated_at'];
$before = Illuminate\Support\Facades\DB::table('products')->select($columns)->orderBy('id')->get()->toJson();
$tiers = Illuminate\Support\Facades\DB::table('price_tiers')->orderBy('id')->get()->toJson();
if (Illuminate\Support\Facades\Artisan::call('migrate', ['--force'=>true]) !== 0) { throw new RuntimeException('Migración fallida. Revisar sin repetir semillas.'); }
$after = Illuminate\Support\Facades\DB::table('products')->select($columns)->orderBy('id')->get()->toJson();
if ($before !== $after || $tiers !== Illuminate\Support\Facades\DB::table('price_tiers')->orderBy('id')->get()->toJson()) { throw new RuntimeException('La comparación detectó cambios en datos previos.'); }
echo 'Migración incremental: '.Illuminate\Support\Facades\DB::table('products')->count().' productos y '.Illuminate\Support\Facades\DB::table('price_tiers')->count()." tramos conservados íntegramente.\n";
echo 'Categorías asociadas: '.Illuminate\Support\Facades\DB::table('products')->whereNotNull('category_id')->count()."; movimientos inventados: ".Illuminate\Support\Facades\DB::table('stock_movements')->count().".\n";
