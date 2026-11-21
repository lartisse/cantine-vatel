<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;

/** A7 Utilisateurs (consultation) + ajustement manuel du solde. */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $q = trim((string) $request->query('q'));
        $users = User::active()->whereIn('role', ['user', 'parent'])
            ->when($role === 'eleve' || $role === 'personnel', fn ($w) => $w->where('account_type', $role))
            ->when($role === 'parent', fn ($w) => $w->where('role', 'parent'))
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")->orWhere('matricule', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->orderBy('last_name')->paginate(30)->withQueryString();

        return view('admin.users', compact('users', 'role', 'q'));
    }

    public function show(User $user)
    {
        abort_if($user->isStaff() || $user->status === 'trashed', 404);
        $user->load('parents', 'children');

        return view('admin.user', ['u' => $user, 'txs' => $user->transactions()->latest()->limit(10)->get(), 'res' => $user->reservations()->with('menu')->latest()->limit(8)->get()]);
    }

    public function adjust(Request $request, User $user, AccountService $accounts)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'], 'reason' => ['required', 'string', 'max:120']],
            ['reason.required' => 'Le motif est obligatoire.']);
        $accounts->adjust($user, (int) $data['amount'], $data['reason']);

        return back()->with('ok', 'Solde ajusté : '.($data['amount'] > 0 ? '+' : '').fcfa($data['amount']));
    }
}
