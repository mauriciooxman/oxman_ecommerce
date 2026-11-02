<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Guardia: verificar que el destino sea la base exclusiva de demo antes de DDL.
     * Solo demo y pruebas locales; rechazar destinos alternativos antes de DDL.
     */
    private function assertDemoDatabase(): void
    {
        $db   = DB::connection()->getDatabaseName();
        $host = DB::connection()->getConfig('host');
        $port = (string) DB::connection()->getConfig('port');

        if (DB::connection()->getDriverName() !== 'mysql'
            || ! in_array($db, ['oxman_demo', 'oxman_demo_test'], true)
            || $host !== '127.0.0.1' || $port !== '3307'
            || DB::connection()->getConfig('url')
            || DB::connection()->getConfig('unix_socket')) {
            throw new \RuntimeException(
                "Guardia de pedidos: destino inesperado ($db en $host:$port). " .
                "Solo se permiten oxman_demo y oxman_demo_test en MySQL 127.0.0.1:3307 sin URL/socket alternativos."
            );
        }
    }

    public function up(): void
    {
        $this->assertDemoDatabase();

        // Columna stock_reservado en products
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_reservado')->default(0)->after('stock');
        });

        // Pedidos
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->string('reference', 20)->unique();          // visible: OXM-YYYYMMDD-XXXX
            $table->string('idempotency_key', 36)->unique();    // UUID del formulario
            $table->string('access_token', 36)->unique();       // UUID para URL de seguimiento
            $table->enum('modalidad', ['despacho', 'retiro']);
            $table->enum('estado_pedido', ['pendiente', 'preparacion', 'despachado', 'retirado', 'cancelado'])
                  ->default('pendiente');
            $table->enum('estado_pago', ['pendiente', 'recibido'])
                  ->default('pendiente');
            $table->enum('estado_despacho', [
                'pendiente_cotizacion',
                'cotizado',
                'aceptado',
                'despachado_registrado',
                'retirado_registrado',
            ])->default('pendiente_cotizacion');
            $table->unsignedInteger('monto_despacho')->nullable();
            $table->string('nombre', 160);
            $table->string('telefono', 20);
            $table->string('correo', 254)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('comuna', 100)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('motivo_cancelacion', 500)->nullable();
            $table->timestamps();
        });

        // Líneas — snapshot inmutable
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('nombre_producto', 160);
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('precio_unitario');
            $table->unsignedInteger('subtotal');
            $table->timestamp('created_at')->useCurrent();
        });

        // Eventos — historial append-only
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('tipo', ['estado_pedido', 'estado_pago', 'estado_despacho', 'nota']);
            $table->string('valor_anterior', 60)->nullable();
            $table->string('valor_nuevo', 60)->nullable();
            $table->string('notas', 500)->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        $this->assertDemoDatabase();

        Schema::dropIfExists('order_events');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock_reservado');
        });
    }
};

