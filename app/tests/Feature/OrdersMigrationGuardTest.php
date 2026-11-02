<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OrdersMigrationGuardTest extends TestCase
{
    public static function migrations(): array
    {
        return [['2026_10_27_000100_create_orders.php'], ['2026_11_02_000100_add_owner_order_notices.php']];
    }

    #[DataProvider('migrations')]
    public function test_guardia_admite_solo_demo_y_pruebas_locales_sin_ddl(string $file): void
    {
        $migration = require database_path('migrations/'.$file);
        $method = new \ReflectionMethod($migration, 'assertDemoDatabase');
        $base = ['driver' => 'mysql', 'database' => 'oxman_demo', 'host' => '127.0.0.1', 'port' => '3307', 'url' => null, 'unix_socket' => ''];
        foreach ([$base, [...$base, 'database' => 'oxman_demo_test']] as $config) {
            config(['database.connections.guard_probe' => $config, 'database.default' => 'guard_probe']);
            DB::purge('guard_probe');
            $method->invoke($migration);
            $this->addToAssertionCount(1);
        }
        foreach (['database' => 'base_ajena', 'host' => 'localhost', 'port' => '3306', 'driver' => 'sqlite', 'url' => 'mysql://alternate', 'unix_socket' => '/tmp/mysql.sock'] as $key => $value) {
            $config = [...$base, $key => $value];
            config(['database.connections.guard_probe' => $config, 'database.default' => 'guard_probe']);
            DB::purge('guard_probe');
            foreach (['up', 'down'] as $direction) {
                try {
                    $migration->$direction();
                    $this->fail('Debió rechazar antes del DDL');
                } catch (\RuntimeException $error) {
                    $this->assertStringContainsString('Guardia de pedidos', $error->getMessage());
                }
            }
        }
    }
}
