<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\DeletionRequest;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** S2 Comptes, S3 Corbeille, S4 Demandes de suppression. */
class AccountController extends Controller
{
    public function __construct(protected AccountService $accounts) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $users = User::with('parents', 'children')->active()->whereIn('role', ['user', 'parent'])
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->orderBy('last_name')->paginate(30)->withQueryString();

        return view('super.accounts', compact('users', 'q'));
    }

    public function trash(Request $request, User $user)
    {
        abort_if($user->isSuper() || $user->id === $request->user()->id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Le motif est obligatoire.']);
        $this->accounts->trash($user, $data['reason']);
        if ($request->filled('request_id')) {
            DeletionRequest::where('id', $request->input('request_id'))->update(['status' => 'accepted', 'handled_by' => $request->user()->id]);
        }

        return redirect()->route('super.trash')->with('ok', $user->name.' placé(e) en corbeille');
    }

    public function trashIndex()
    {
        return view('super.trash', ['users' => User::where('status', 'trashed')->orderBy('trashed_at')->get(), 'days' => Settings::trashDays()]);
    }

    public function restore(User $user)
    {
        abort_unless($user->status === 'trashed', 404);
        $this->accounts->restore($user);

        return back()->with('ok', $user->name.' restauré(e)');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->status === 'trashed', 404);
        $mode = $request->input('mode', 'name');
        $ok = $mode === 'password'
            ? Hash::check((string) $request->input('confirm'), (string) $request->user()->password)
            : mb_strtolower(trim((string) $request->input('confirm'))) === mb_strtolower($user->name);
        if (! $ok) {
            return back()->with('error', $mode === 'password' ? 'Mot de passe incorrect' : 'Le nom saisi ne correspond pas.');
        }
        $name = $user->name;
        $this->accounts->forceDelete($user);

        return back()->with('ok', $name.' supprimé(e) définitivement — statistiques anonymisées');
    }

    public function purge()
    {
        $n = $this->accounts->purge();

        return back()->with('ok', $n ? "{$n} compte(s) purgé(s)" : 'Aucun compte à purger');
    }

    public function delay(Request $request)
    {
        $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);
        Settings::set('trash_days', $data['days']);
        AuditLogger::log('purge_setting', 'Settings', 'Purge après '.$data['days'].' jours', 'Corbeille');

        return back()->with('ok', 'Délai de purge : '.$data['days'].' jours');
    }

    public function requests()
    {
        return view('super.requests', [
            'pending' => DeletionRequest::with('user')->where('status', 'pending')->latest()->get(),
            'handled' => DeletionRequest::with('user', 'handler')->whereIn('status', ['accepted', 'refused'])->latest()->limit(20)->get(),
        ]);
    }

    public function handleRequest(Request $request, DeletionRequest $deletionRequest)
    {
        $data = $request->validate(['action' => ['required', 'in:refuse'], 'message' => ['required', 'string', 'max:500']], ['message.required' => 'Le message de justification est obligatoire.']);
        $deletionRequest->update(['status' => 'refused', 'response_message' => $data['message'], 'handled_by' => $request->user()->id]);
        $deletionRequest->user?->inform('Demande de suppression refusée', $data['message'], route('profile.deletion'), 'account', 'mail');
        AuditLogger::log('refuse_deletion', $deletionRequest->user ?? 'User', $data['message']);

        return back()->with('ok', 'Demande refusée — message envoyé');
    }
}
