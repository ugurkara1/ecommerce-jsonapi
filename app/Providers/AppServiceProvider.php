<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use App\Observers\OrderObserver;
use App\Models\Order;


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
        // Super-Admin rolüne sahip olan kullanıcılara tüm izinleri vermek
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super admin')) {
                return true;
            }
        });
        //
        if(Request::hasHeader('Accept-Language')){
            $locale=Request::getPreferredLanguage(['en','tr']);
            App::setLocale($locale);
        }else{
            App::setLocale(config('app.fallback_locale'));
        }

        // Add explicit model binding for 'role' to Spatie's Role model
        Route::model('role', Role::class);
        Order::observe(OrderObserver::class);

    }
}
