<?php

namespace App\Providers;

use App\Repositories\OrderRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use App\Observers\OrderObserver;
use App\Models\Order;
use App\Contracts\BrandContract;
use App\Contracts\CategoryContract;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Contracts\ProductContract;
use App\Models\Attributes;
use App\Repositories\AttributesRepository;
use App\Contracts\AttributesContract;
use App\Repositories\AttributeValuesRepository; // Added correct namespace
use App\Models\AttributeValues;
use App\Contracts\AttributeValuesContract; // Added correct namespace for AttributeValuesContract
use App\Contracts\CampaignContract;
use App\Contracts\InvoicesContract;
use App\Contracts\OrderAddressesContract;
use App\Contracts\OrderContract;
use App\Contracts\ProductVariantContract;
use App\Repositories\CampaignRepository; // Added correct namespace for CampaignRepository
use App\Repositories\InvoicesRepository; // Added correct namespace for InvoicesRepository
use App\Repositories\OrderAddressesRepository;
use App\Repositories\ProductVariantRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(BrandContract::class, BrandRepository::class);
        $this->app->bind(CategoryContract::class, CategoryRepository::class);
        $this->app->bind(ProductContract::class, ProductRepository::class);
        $this->app->bind(AttributesContract::class, AttributesRepository::class);
        $this->app->bind(AttributeValuesContract::class, AttributeValuesRepository::class);
        $this->app->bind(CampaignContract::class, CampaignRepository::class);
        $this->app->bind(InvoicesContract::class, InvoicesRepository::class);
        $this->app->bind(OrderContract::class, OrderRepository::class);
        $this->app->bind(OrderAddressesContract::class,OrderAddressesRepository::class);
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