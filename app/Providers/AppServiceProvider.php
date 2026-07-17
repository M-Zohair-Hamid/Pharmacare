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
    // If the request is arriving through a proxy/tunnel (e.g. ngrok) that
    // terminates TLS in front of us, Laravel's dev server still thinks the
    // connection is plain HTTP. Trust the forwarded proto header in that
    // case so generated URLs (route(), asset(), Vite, etc.) come out as
    // https over ngrok, while staying http on a direct 127.0.0.1 visit.
    if ($this->app->environment('local')) {
        if (request()->header('X-Forwarded-Proto') === 'https' || str_contains(request()->getHost(), 'ngrok')) {
            \URL::forceScheme('https');
        }
    }
}


}
