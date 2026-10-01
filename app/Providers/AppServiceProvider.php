<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Em producao, gera todas as URLs em HTTPS (links, assets, redirects).
        // Evita conteudo misto e trafego de tokens/senhas em claro.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
