<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $forceHttps = $this->app->environment('production')
            || filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);

        if ($forceHttps) {
            URL::forceScheme('https');
        }

        // Railway / proxies: se a requisição chegou como HTTPS, força esquema
        if (! $this->app->runningInConsole() && request()->isSecure()) {
            URL::forceScheme('https');
        }

        // Garante APP_URL com https em produção (evita mixed content em asset/route)
        $appUrl = config('app.url');
        if ($forceHttps && is_string($appUrl) && str_starts_with($appUrl, 'http://')) {
            config(['app.url' => 'https://' . substr($appUrl, 7)]);
        }
    }
}
