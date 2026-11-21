<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Menu;
use App\Services\ImageService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** C1 Accueil public, C4 Profil, C5 Notifications, C6 Confirmation du mot de passe. */
class CommonController extends Controller
{
    public function welcome()
    {
        $menu = Menu::with('dishes')->whereDate('date', today())->where('is_published', true)->first()
            ?? Menu::with('dishes')->whereDate('date', '>=', today())->where('is_published', true)->orderBy('date')->first();
        $items = $menu ? $menu->dishes->sortBy(fn ($d) => array_search($d->category, ['plat', 'entree', 'dessert', 'boisson']))->take(3) : collect();

        return view('welcome', ['menu' => $menu, 'items' => $items, 'cutoff' => Settings::get('cutoff', '10:00')]);
    }

    public function publicMenu()
    {
        $monday = today()->startOfWeek();
        $menus = Menu::with('dishes')->whereBetween('date', [$monday, $monday->copy()->addDays(4)])->where('is_published', true)->orderBy('date')->get();

        return view('public-menu', compact('menus'));
    }

    public function profile(Request $request)
    {
        $u = $request->user();
        $view = $u->isStaff() ? 'common.profile-admin' : 'common.profile';

        return view($view, ['u' => $u, 'pending' => $u->deletionRequests()->latest()->first()]);
    }

    public function updateProfile(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
            'class_name' => ['nullable', Rule::in(config('cantine.classes'))],
            'matricule' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:80'],
            'allergies' => ['array'], 'allergies.*' => [Rule::in(array_keys(config('cantine.allergens')))],
            'diets' => ['array'], 'diets.*' => [Rule::in(array_keys(config('cantine.diets')))],
            'low_balance_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'locale' => ['nullable', Rule::in(['fr', 'en'])],
            'notify' => ['array'],
        ], ['first_name.required' => 'Le prénom est obligatoire.', 'last_name.required' => 'Le nom est obligatoire.']);

        $u->first_name = $data['first_name'];
        $u->last_name = $data['last_name'];
        $u->phone = $data['phone'] ?? null;
        if ($u->role === 'user') {
            if ($u->account_type === 'eleve') {
                $u->class_name = $data['class_name'] ?? null;
                $u->matricule = $data['matricule'] ?? null;
            } else {
                $u->position = $data['position'] ?? null;
            }
            $u->allergies = $data['allergies'] ?? [];
            $u->diets = $data['diets'] ?? [];
            $u->low_balance_threshold = $data['low_balance_threshold'] ?? $u->low_balance_threshold;
        } elseif ($u->isStaff()) {
            $u->position = $data['position'] ?? $u->position;
        }
        if (! $u->isStaff()) {
            $u->locale = $data['locale'] ?? $u->locale;
            $u->notify = ['push' => isset($data['notify']['push']), 'sms' => isset($data['notify']['sms']), 'email' => isset($data['notify']['email'])];
        }
        $u->save();

        return back()->with('ok', __('Profil enregistré'));
    }

    public function setLocale(Request $request)
    {
        $request->validate(['locale' => ['required', Rule::in(['fr', 'en'])]]);
        $request->user()->update(['locale' => $request->input('locale')]);

        return back()->with('ok', $request->input('locale') === 'en' ? 'Language: English' : 'Langue : Français');
    }

    public function updatePassword(Request $request)
    {
        $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(8), 'different:current_password']],
            ['current_password.current_password' => 'Mot de passe incorrect', 'password.different' => 'Le nouveau mot de passe doit être différent de l’actuel.']);
        $request->user()->forceFill(['password' => $request->input('password'), 'must_change_password' => false])->save();
        $request->session()->regenerate();

        return back()->with('ok', __('Mot de passe modifié'));
    }

    public function uploadPhoto(Request $request, ImageService $images)
    {
        $request->validate(['photo' => ImageService::RULES], ImageService::MESSAGES);
        $u = $request->user();
        $before = $request->file('photo')->getSize();
        $u->avatar_path = $images->store($request->file('photo'), 'avatars', 512, $u->avatar_path);
        $u->save();
        $after = Storage::disk('public')->size($u->avatar_path);

        return back()->with('ok', __('Photo enregistrée').' ('.round($before / 1024).' Ko → '.round($after / 1024).' Ko)');
    }

    public function deletePhoto(Request $request, ImageService $images)
    {
        $u = $request->user();
        $images->delete($u->avatar_path);
        $u->avatar_path = null;
        $u->save();

        return back()->with('ok', __('Photo supprimée'));
    }

    public function notifications(Request $request)
    {
        $u = $request->user();
        if ($u->isCounter()) {
            return redirect()->route('admin.counter');
        }
        if ($u->isAdmin()) {
            return redirect()->route('admin.alerts');
        }
        $items = $u->appNotifications()->latest()->limit(60)->get();

        return view('common.notifications', compact('items'));
    }

    public function openNotification(Request $request, AppNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return redirect($notification->url ?: route('notifications'));
    }

    public function readAll(Request $request)
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    /** Changement obligatoire d'un mot de passe provisoire (compte créé ou réinitialisé par le super admin). */
    public function showChangePassword(Request $request)
    {
        return view('auth.change-password', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Mot de passe provisoire incorrect',
            'password.different' => 'Le nouveau mot de passe doit être différent du mot de passe provisoire.',
            'password.min' => '10 caractères minimum.',
        ]);
        $u = $request->user();
        $u->forceFill(['password' => $request->input('password'), 'must_change_password' => false])->save();
        $request->session()->regenerate();

        return redirect($u->homeRoute())->with('ok', 'Mot de passe enregistré');
    }

    public function showConfirm()
    {
        return view('auth.confirm');
    }

    public function confirm(Request $request)
    {
        if (! Hash::check((string) $request->input('password'), (string) $request->user()->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect']);
        }
        $request->session()->put('auth.password_confirmed_at', now()->unix());
        // Une action sensible (PUT/POST/DELETE) interrompue ne peut pas être rejouée en GET : retour à l'écran S5.
        $intended = (string) $request->session()->get('url.intended', '');
        if (preg_match('#/super/paiements/.+#', $intended)) {
            $request->session()->forget('url.intended');

            return redirect()->route('super.payments')->with('info', 'Identité confirmée : relancez l’action.');
        }

        return redirect()->intended($request->user()->homeRoute());
    }
}
