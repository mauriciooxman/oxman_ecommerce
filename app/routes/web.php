<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('catalog');
Route::get('/productos/{sku}', [StorefrontController::class, 'show'])->name('product');
Route::get('/carro', [StorefrontController::class, 'cart'])->name('cart');
Route::post('/carro/{sku}', [StorefrontController::class, 'add'])->name('cart.add');
Route::patch('/carro/{sku}', [StorefrontController::class, 'update'])->name('cart.update');
Route::delete('/carro/{sku}', [StorefrontController::class, 'remove'])->name('cart.remove');

Route::middleware(\App\Http\Middleware\PrivateCheckout::class)->group(function () {
    $controller = \App\Http\Controllers\CheckoutController::class;
    Route::get('/checkout/revisar', [$controller, 'review'])->name('checkout.review');
    Route::post('/checkout/confirmar', [$controller, 'confirm'])->name('checkout.confirm');
    Route::get('/checkout/confirmacion/{referencia}', [$controller, 'confirmation'])->name('checkout.confirmation');
    Route::get('/pedido/seguimiento/{token}', [$controller, 'tracking'])->name('orders.tracking');
    Route::post('/pedido/seguimiento/{token}/aceptar-cotizacion', [$controller, 'acceptQuote'])->name('orders.accept');
});

Route::prefix('dueno')->name('owner.')->middleware('owner:login')->group(function () {
    Route::get('/ingresar', [\App\Http\Controllers\Owner\AuthController::class, 'create'])->name('login');
    Route::post('/ingresar', [\App\Http\Controllers\Owner\AuthController::class, 'store'])->name('authenticate');
    Route::middleware('owner')->group(function () {
        Route::prefix('pedidos')->name('orders.')->controller(\App\Http\Controllers\Owner\OrderController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{order}', 'show')->whereNumber('order')->name('show');
            Route::post('/{order}/cancelar', 'cancel')->whereNumber('order')->name('cancel');
            Route::post('/{order}/cotizar-despacho', 'quoteShipping')->whereNumber('order')->name('quote');
            Route::post('/{order}/confirmar-pago', 'confirmPayment')->whereNumber('order')->name('payment');
            Route::post('/{order}/preparacion', 'toPreparation')->whereNumber('order')->name('prepare');
            Route::post('/{order}/entregar', 'deliver')->whereNumber('order')->name('deliver');
        });
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
