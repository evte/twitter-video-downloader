<?php

namespace App\Providers;

use App\Services\TwitterVideoService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TwitterVideoService::class, function ($app) {
            return new TwitterVideoService();
        });
    }
}