<?php

namespace App\Providers;

use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        Paginator::useBootstrapFive();

        RateLimiter::for('register.per.ip', function (Request $request): Limit {
            return Limit::perMinutes(60, 5)->by($request->ip());
        });

        try {
            View::share('cartLineByProduct', app(CartService::class)->items()->keyBy('product_id'));
        } catch (\Throwable) {
            View::share('cartLineByProduct', collect());
        }
    }
}
