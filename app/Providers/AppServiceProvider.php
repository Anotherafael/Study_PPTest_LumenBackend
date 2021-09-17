<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Retailer;
use Laravel\Passport\Passport;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot()/*  */
    {
        User::observe(UserObserver::class);
        Retailer::observe(Retailer::class);
        Passport::ignoreMigrations();
        \Dusterio\LumenPassport\LumenPassport::routes($this->app);
    }
}
