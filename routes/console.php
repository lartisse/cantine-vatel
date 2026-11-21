<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

// Purge quotidienne des comptes en corbeille depuis plus de CANTINE_TRASH_DAYS jours
Schedule::call(fn () => app(\App\Services\AccountService::class)->purge())->daily()->name('purge-corbeille');

/*
| Création du premier super administrateur en production (sans données de démonstration).
| php artisan cantine:super-admin admin@exemple.ci --prenom=Kader --nom=Touré
| Le mot de passe est demandé de façon masquée ; il n'apparaît ni à l'écran ni dans l'historique du terminal.
*/
Artisan::command('cantine:super-admin {email} {--prenom=Super} {--nom=Administrateur}', function (string $email) {
    if (User::where('email', strtolower($email))->exists()) {
        $this->error('Un compte existe déjà avec cet e-mail.');

        return 1;
    }
    $password = $this->secret('Mot de passe (12 caractères minimum, lettres et chiffres)');
    $v = Validator::make(['email' => $email, 'password' => $password], [
        'email' => ['required', 'email'],
        'password' => ['required', \Illuminate\Validation\Rules\Password::min(12)->letters()->numbers()],
    ]);
    if ($v->fails()) {
        $this->error($v->errors()->first());

        return 1;
    }
    if ($this->secret('Confirmez le mot de passe') !== $password) {
        $this->error('Les deux mots de passe ne correspondent pas.');

        return 1;
    }
    $u = new User;
    $u->fill(['first_name' => $this->option('prenom'), 'last_name' => $this->option('nom'), 'email' => strtolower($email), 'password' => $password, 'position' => 'Super administrateur']);
    $u->forceFill(['role' => 'super_admin', 'status' => 'active'])->save();
    $this->info("Super administrateur créé : {$u->email}");

    return 0;
})->purpose('Créer le premier super administrateur (production)');
