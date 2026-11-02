<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('layouts.store', function ($view) {
            $view->with('cartQuantityCount', app(\App\Services\CartService::class)->quantityCount(request(), $view->getData()['store']));
        });
        \Illuminate\Support\Facades\View::composer('owner.layout', function ($view) {
            $user = auth()->user();
            $count = $user?->ownsStore()
                ? \App\Models\Order::forStore($user->store)->whereNull('reviewed_at')->count() : 0;
            $view->with('unreviewedOrderCount', $count);
            $view->with('latestOrderCursor', $user?->ownsStore() ? (string)(\App\Models\Order::forStore($user->store)->max('id') ?? 0) : '0');
        });
    }
}
