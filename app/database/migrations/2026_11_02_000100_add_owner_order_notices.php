<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    private function assertDemoDatabase(): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql'
            || !in_array($connection->getDatabaseName(), ['oxman_demo', 'oxman_demo_test'], true)
            || $connection->getConfig('host') !== '127.0.0.1' || (string)$connection->getConfig('port') !== '3307'
            || $connection->getConfig('url') || $connection->getConfig('unix_socket')) {
            throw new RuntimeException('Guardia de pedidos: solo bases locales autorizadas, sin URL/socket alternativos.');
        }
    }
    public function up(): void
    {
        $this->assertDemoDatabase();
        Schema::table('orders', fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable());
        Schema::create('order_owner_notices', function (Blueprint $table) {
            $table->foreignId('order_id')->primary()->constrained('orders')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->string('failure_code', 60)->nullable();
        });
    }
    public function down(): void
    {
        $this->assertDemoDatabase();
        Schema::dropIfExists('order_owner_notices');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('reviewed_at'));
    }
};
