<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** C2 Inscription, C3 Connexion. */
class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $type = $request->input('type');
        $data = $request->validate([
            'type' => ['required', Rule::in(['eleve', 'personnel', 'parent'])],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'class_name' => ['nullable', Rule::in(config('cantine.classes'))],
            'matricule' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:80'],
            'allergies' => ['array'], 'allergies.*' => [Rule::in(array_keys(config('cantine.allergens')))],
            'diets' => ['array'], 'diets.*' => [Rule::in(array_keys(config('cantine.diets')))],
        ], [
            'first_name.required' => 'Le prénom est obligatoire.', 'last_name.required' => 'Le nom est obligatoire.',
            'email.unique' => 'Cet e-mail est déjà utilisé.', 'password.min' => 'Mot de passe trop court (8 caractères minimum).',
        ]);

        $user = new User;
        $user->fill([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'phone' => $data['phone'] ?? null,
            'email' => strtolower($data['email']), 'password' => $data['password'], 'locale' => 'fr',
            'account_type' => $type === 'parent' ? null : $type,
            'class_name' => $type === 'eleve' ? ($data['class_name'] ?? null) : null,
            'matricule' => $type === 'eleve' ? ($data['matricule'] ?? null) : null,
            'position' => $type === 'personnel' ? ($data['position'] ?? null) : null,
            'allergies' => $type === 'parent' ? [] : ($data['allergies'] ?? []),
            'diets' => $type === 'parent' ? [] : ($data['diets'] ?? []),
            'class_confirmed_for' => \App\Support\Settings::period()['id'],
            'notify' => ['push' => true, 'sms' => false, 'email' => true],
        ]);
        $user->forceFill(['role' => $type === 'parent' ? 'parent' : 'user', 'status' => 'active', 'balance' => 0])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $user->inform('Bienvenue sur Cantine de Vatel', $type === 'parent' ? 'Liez le compte de votre enfant en scannant son QR code.' : 'Rechargez votre compte puis réservez votre premier repas.', null, 'info', 'smile');

        return redirect($user->homeRoute())->with('ok', 'Compte créé, bienvenue '.$user->first_name.' !');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'string'], 'password' => ['required', 'string']]);
        $login = trim($data['email']);
        $user = str_contains($login, '@')
            ? User::where('email', strtolower($login))->first()
            : User::where('phone', $login)->orWhere('phone', preg_replace('/(\d{2})(?=\d)/', '$1 ', preg_replace('/\D/', '', $login)))->first();

        if (! $user || ! $user->password || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['email' => 'Identifiants incorrects.'])->onlyInput('email');
        }
        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Ce compte est désactivé. Contactez l’administration de la cantine.'])->onlyInput('email');
        }
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($user->homeRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
