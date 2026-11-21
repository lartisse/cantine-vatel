<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureAccountActive::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->api(append: [\App\Http\Middleware\SecurityHeaders::class]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'api.key' => \App\Http\Middleware\AuthenticateApiKey::class,
        ]);
        $middleware->redirectGuestsTo('/connexion');
        $middleware->redirectUsersTo(fn () => auth()->user()?->homeRoute() ?? '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // L'API répond toujours en JSON (erreurs de validation, 404…)
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
    })->create();
