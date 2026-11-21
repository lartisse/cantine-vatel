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
        // Production : liens générés en HTTPS et cookies de session uniquement transmis en HTTPS
        if ($this->app->isProduction()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            config(['session.secure' => config('session.secure') ?? true]);
            // Mots de passe : bcrypt plus coûteux et refus des mots de passe trop simples
            \Illuminate\Validation\Rules\Password::defaults(fn () => \Illuminate\Validation\Rules\Password::min(10)->letters()->numbers()->uncompromised());
        }

        // Configuration SMTP saisie par le super administrateur (prioritaire sur MAIL_* du .env quand elle est activée)
        try {
            \App\Support\MailSettings::apply();
        } catch (\Throwable) {
            // base pas encore migrée : on garde la configuration du .env
        }
    }
}
