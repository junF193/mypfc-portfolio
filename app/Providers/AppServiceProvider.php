<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Policies\FavoriteFoodPolicy;
use App\Models\FavoriteFood;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        FavoriteFood::class => FavoriteFoodPolicy::class,
    ];

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
        //
    }
}