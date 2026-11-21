<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** S1 Équipe : agents de comptoir, administrateurs et super administrateurs. */
class AdminController extends Controller
{
    public const STAFF_ROLES = ['counter', 'admin', 'super_admin'];

    public function index()
    {
        return view('super.admins', [
            'admins' => User::whereIn('role', self::STAFF_ROLES)->whereIn('status', ['active', 'disabled'])->orderByRaw("CASE role WHEN 'super_admin' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")->orderBy('last_name')->get(),
            'newPassword' => session('new_admin_password'),
        ]);
    }

    /**
     * Mot de passe provisoire fort, sans caractères ambigus.
     * Affiché une seule fois au super admin, à changer obligatoirement à la première connexion.
     */
    protected function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $p = '';
        for ($i = 0; $i < 14; $i++) {
            $p .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $p.random_int(2, 9);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'], 'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'unique:users,email'], 'position' => ['nullable', 'string', 'max:80'], 'role' => ['required', Rule::in(self::STAFF_ROLES)],
        ], ['email.unique' => 'Cet e-mail est déjà utilisé.']);
        $temp = $this->temporaryPassword();
        $u = new User;
        $u->fill(collect($data)->except('role')->all() + ['password' => $temp]);
        $u->forceFill(['role' => $data['role'], 'status' => 'active', 'must_change_password' => true])->save();
        AuditLogger::log('admin_create', $u, 'Rôle : '.User::ROLE_LABELS[$data['role']]);

        return redirect()->route('super.admins')->with('new_admin_password', ['name' => $u->name, 'email' => $u->email, 'password' => $temp]);
    }

    /** Génère un nouveau mot de passe provisoire (affiché une fois) et ferme les sessions ouvertes. */
    public function resetPassword(Request $request, User $user)
    {
        abort_unless($user->isStaff(), 404);
        abort_if($user->id === $request->user()->id, 422, 'Utilisez votre profil pour changer votre propre mot de passe.');
        $temp = $this->temporaryPassword();
        $user->forceFill(['password' => $temp, 'must_change_password' => true])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        AuditLogger::log('admin_update', $user, 'Mot de passe réinitialisé (provisoire)');

        return redirect()->route('super.admins')->with('new_admin_password', ['name' => $user->name, 'email' => $user->email, 'password' => $temp]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->isStaff(), 404);
        $data = $request->validate(['first_name' => ['required', 'string', 'max:60'], 'last_name' => ['required', 'string', 'max:60'], 'position' => ['nullable', 'string', 'max:80'], 'role' => ['required', Rule::in(self::STAFF_ROLES)]]);
        if ($user->id === $request->user()->id && $data['role'] !== $user->role) {
            return back()->with('error', 'Vous ne pouvez pas changer votre propre rôle.');
        }
        $old = $user->role;
        $user->fill(collect($data)->except('role')->all());
        $user->role = $data['role'];
        $user->save();
        if ($old !== $data['role']) {
            DB::table('sessions')->where('user_id', $user->id)->delete(); // droits modifiés : reconnexion obligatoire
            AuditLogger::log('role_change', $user, 'Rôle : '.User::ROLE_LABELS[$old].' → '.User::ROLE_LABELS[$data['role']]);
        } else {
            AuditLogger::log('admin_update', $user);
        }

        return back()->with('ok', 'Compte modifié');
    }

    public function toggle(Request $request, User $user)
    {
        abort_unless($user->isStaff(), 404);
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }
        if ($user->status === 'active') {
            $data = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Le motif est obligatoire.']);
            $user->forceFill(['status' => 'disabled', 'trash_reason' => $data['reason']])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            AuditLogger::log('admin_disable', $user, 'Motif : '.$data['reason']);

            return back()->with('ok', 'Compte désactivé');
        }
        $user->forceFill(['status' => 'active', 'trash_reason' => null])->save();
        AuditLogger::log('admin_enable', $user);

        return back()->with('ok', 'Compte réactivé');
    }
}
