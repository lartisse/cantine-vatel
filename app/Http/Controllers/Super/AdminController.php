<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** S1 Administrateurs : créer, modifier, changer de rôle, désactiver. */
class AdminController extends Controller
{
    public function index()
    {
        return view('super.admins', ['admins' => User::whereIn('role', ['admin', 'super_admin'])->whereIn('status', ['active', 'disabled'])->orderBy('role', 'desc')->orderBy('last_name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'], 'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'unique:users,email'], 'position' => ['nullable', 'string', 'max:80'], 'role' => ['required', Rule::in(['admin', 'super_admin'])],
        ], ['email.unique' => 'Cet e-mail est déjà utilisé.']);
        $temp = Str::password(12);
        $u = new User;
        $u->fill(collect($data)->except('role')->all() + ['password' => $temp]);
        $u->forceFill(['role' => $data['role'], 'status' => 'active'])->save();
        AuditLogger::log('admin_create', $u, 'Rôle : '.User::ROLE_LABELS[$data['role']]);

        return back()->with('ok', 'Administrateur créé. E-mail d’activation envoyé (simulé) à '.$u->email.'.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->isAdmin(), 404);
        $data = $request->validate(['first_name' => ['required', 'string', 'max:60'], 'last_name' => ['required', 'string', 'max:60'], 'position' => ['nullable', 'string', 'max:80'], 'role' => ['required', Rule::in(['admin', 'super_admin'])]]);
        if ($user->id === $request->user()->id && $data['role'] !== $user->role) {
            return back()->with('error', 'Vous ne pouvez pas changer votre propre rôle.');
        }
        $old = $user->role;
        $user->fill(collect($data)->except('role')->all());
        $user->role = $data['role'];
        $user->save();
        if ($old !== $data['role']) {
            AuditLogger::log('role_change', $user, 'Rôle : '.User::ROLE_LABELS[$old].' → '.User::ROLE_LABELS[$data['role']]);
        } else {
            AuditLogger::log('admin_update', $user);
        }

        return back()->with('ok', 'Administrateur modifié');
    }

    public function toggle(Request $request, User $user)
    {
        abort_unless($user->isAdmin(), 404);
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
