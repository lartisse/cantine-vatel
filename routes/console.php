<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge quotidienne des comptes en corbeille depuis plus de CANTINE_TRASH_DAYS jours
Schedule::call(fn () => app(\App\Services\AccountService::class)->purge())->daily()->name('purge-corbeille');
