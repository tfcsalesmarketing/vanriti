<?php

namespace App\Providers;

use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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

        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        RateLimiter::for('register.per.ip', function (Request $request): Limit {
            return Limit::perMinutes(60, 5)->by($request->ip());
        });

        RateLimiter::for('otp.send', function (Request $request): Limit {
            return Limit::perMinutes(5, 3)->by(($request->ip() ?: 'otp') . ':send');
        });

        RateLimiter::for('otp.verify', function (Request $request): Limit {
            return Limit::perMinutes(60, 10)->by(($request->ip() ?: 'otp') . ':verify');
        });

        try {
            View::share('cartLineByProduct', app(CartService::class)->items()->keyBy('product_id'));
        } catch (\Throwable) {
            View::share('cartLineByProduct', collect());
        }
    }
}
