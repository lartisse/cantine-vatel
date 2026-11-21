<?php

use App\Http\Controllers\Api\V1Controller;
use Illuminate\Support\Facades\Route;

/*
| API REST v1 — préfixe /api/v1, authentification par clé d'API (créée par le super administrateur).
| En-tête : « Authorization: Bearer cvk_… » (ou « X-Api-Key: cvk_… »). Réponses en JSON.
| Documentation : docs/API.md
*/
Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/ping', [V1Controller::class, 'ping'])->middleware('api.key')->name('ping');

    Route::middleware('api.key:menus:read')->group(function () {
        Route::get('/menus', [V1Controller::class, 'menus'])->name('menus');
        Route::get('/menus/{date}', [V1Controller::class, 'menuOfDay'])->name('menus.day');
        Route::get('/plats', [V1Controller::class, 'dishes'])->name('dishes');
    });
    Route::get('/reservations', [V1Controller::class, 'reservations'])->middleware('api.key:reservations:read')->name('reservations');
    Route::get('/commandes', [V1Controller::class, 'orders'])->middleware('api.key:orders:read')->name('orders');
    Route::post('/comptoir/servir', [V1Controller::class, 'serve'])->middleware('api.key:orders:write')->name('serve');
    Route::get('/statistiques', [V1Controller::class, 'stats'])->middleware('api.key:stats:read')->name('stats');
    Route::get('/stocks', [V1Controller::class, 'stocks'])->middleware('api.key:stocks:read')->name('stocks');
    Route::get('/utilisateurs', [V1Controller::class, 'users'])->middleware('api.key:users:read')->name('users');
    Route::post('/utilisateurs/{user}/recharges', [V1Controller::class, 'topup'])->middleware('api.key:wallet:write')->name('topup');
});
