<?php

namespace Marks\BraintreeCashier;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CashierServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        BraintreeGateway::loadConfiguration();
        Blade::directive('braintreeclienttoken', function() {
            return BraintreeGateway::generateClientToken();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
}
