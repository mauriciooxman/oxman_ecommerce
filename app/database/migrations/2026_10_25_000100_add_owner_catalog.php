<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', fn (Blueprint $table) => $table->boolean('active')->default(true));
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('role', 20)->nullable();
            $table->unsignedInteger('credential_version')->default(1);
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('name', 60);
            $table->timestamps();
            $table->unique(['store_id', 'name']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('photo_path')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('version')->default(1);
        });
        foreach (DB::table('products')->select('store_id', 'category')->distinct()->get() as $group) {
            $id = DB::table('categories')->insertGetId(['store_id' => $group->store_id, 'name' => $group->category, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('products')->where('store_id', $group->store_id)->where('category', $group->category)->update(['category_id' => $id]);
        }
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('operation_id')->unique();
            $table->string('mode', 12);
            $table->integer('quantity');
            $table->integer('delta');
            $table->unsignedInteger('before');
            $table->unsignedInteger('after');
            $table->string('reason', 500);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        // La auditoría no se elimina mediante rollback automático.
        throw new RuntimeException('Migración con historial: restaurar una copia revisada, no borrar movimientos.');
    }
};
