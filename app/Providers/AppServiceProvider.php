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
        // Configuration SMTP saisie par le super administrateur (prioritaire sur MAIL_* du .env quand elle est activée)
        try {
            \App\Support\MailSettings::apply();
        } catch (\Throwable) {
            // base pas encore migrée : on garde la configuration du .env
        }
    }
}
