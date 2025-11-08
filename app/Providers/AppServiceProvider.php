<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\GoodsInward;
use App\Models\DeliveryOrderItem;
use App\Observers\GoodsInwardObserver;
use App\Observers\DeliveryOrderItemObserver;

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
        // Register model observers that update stock safely
    }
}
