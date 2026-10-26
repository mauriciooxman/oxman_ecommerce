<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('catalog');
Route::get('/productos/{sku}', [StorefrontController::class, 'show'])->name('product');
Route::get('/carro', [StorefrontController::class, 'cart'])->name('cart');
Route::post('/carro/{sku}', [StorefrontController::class, 'add'])->name('cart.add');
Route::patch('/carro/{sku}', [StorefrontController::class, 'update'])->name('cart.update');
Route::delete('/carro/{sku}', [StorefrontController::class, 'remove'])->name('cart.remove');

Route::prefix('dueno')->name('owner.')->middleware('owner:login')->group(function () {
    Route::get('/ingresar', [\App\Http\Controllers\Owner\AuthController::class, 'create'])->name('login');
    Route::post('/ingresar', [\App\Http\Controllers\Owner\AuthController::class, 'store'])->name('authenticate');
    Route::middleware('owner')->group(function () {
        Route::post('/salir', [\App\Http\Controllers\Owner\AuthController::class, 'destroy'])->name('logout');
        Route::get('/', [\App\Http\Controllers\Owner\CatalogController::class, 'index'])->name('products');
        Route::get('/productos/nuevo', [\App\Http\Controllers\Owner\CatalogController::class, 'create'])->name('create');
        Route::post('/productos', [\App\Http\Controllers\Owner\CatalogController::class, 'store'])->name('store');
        Route::get('/productos/{product}', [\App\Http\Controllers\Owner\CatalogController::class, 'edit'])->name('edit')->whereNumber('product');
        Route::put('/productos/{product}', [\App\Http\Controllers\Owner\CatalogController::class, 'update'])->name('update')->whereNumber('product');
        Route::post('/productos/{product}/estado', [\App\Http\Controllers\Owner\CatalogController::class, 'state'])->name('state')->whereNumber('product');
        Route::get('/productos/{product}/stock', [\App\Http\Controllers\Owner\CatalogController::class, 'stock'])->name('stock')->whereNumber('product');
        Route::post('/productos/{product}/stock', [\App\Http\Controllers\Owner\CatalogController::class, 'adjust'])->name('adjust')->whereNumber('product');
        Route::get('/productos/{product}/foto', [\App\Http\Controllers\Owner\CatalogController::class, 'photo'])->name('photo')->whereNumber('product');
        Route::get('/categorias', [\App\Http\Controllers\Owner\CategoryController::class, 'index'])->name('categories');
        Route::post('/categorias', [\App\Http\Controllers\Owner\CategoryController::class, 'save'])->name('categories.create');
        Route::put('/categorias/{category}', [\App\Http\Controllers\Owner\CategoryController::class, 'save'])->name('categories.update')->whereNumber('category');
    });
});
