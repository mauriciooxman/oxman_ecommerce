<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('initials', 8);
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->text('compatibility');
            $table->string('image');
            $table->unsignedInteger('stock');
            $table->unsignedInteger('unit_price');
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->unique(['store_id', 'sku']);
        });
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('minimum_quantity');
            $table->unsignedInteger('unit_price');
            $table->timestamps();
            $table->unique(['product_id', 'minimum_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('stores');
    }
};
