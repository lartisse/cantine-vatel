<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DeletionRequest;
use App\Models\LinkToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** U12 QR code de liaison parent, U13 demande de suppression de compte. */
class ProfileController extends Controller
{
    /**
     * Code de secours : 3 lettres du prénom + 6 caractères aléatoires sans ambiguïté (pas de 0/O, 1/I/L),
     * soit 31^6 ≈ 887 millions de combinaisons ; combiné à 15 min de validité, à l'usage unique
     * et à la limite de 5 essais par minute, il ne peut pas être deviné.
     */
    public static function backupCode(?string $firstName): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $random = '';
        for ($i = 0; $i < 6; $i++) {
            $random .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $prefix = strtoupper(Str::substr(preg_replace('/[^A-Za-z]/', '', Str::ascii((string) $firstName)), 0, 3)) ?: 'CVL';

        return $prefix.'-'.$random;
    }

    public static function newToken($child): LinkToken
    {
        LinkToken::where('child_id', $child->id)->whereNull('used_at')->update(['expires_at' => now()]);

        return LinkToken::create([
            'child_id' => $child->id, 'token' => Str::random(40),
            'code' => self::backupCode($child->first_name),
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    public function parentQr(Request $request)
    {
        $u = $request->user();
        abort_unless($u->isStudent(), 403);
        $token = LinkToken::where('child_id', $u->id)->whereNull('used_at')->where('expires_at', '>', now())->latest()->first() ?? self::newToken($u);

        return view('user.parent-qr', ['u' => $u, 'token' => $token, 'parents' => $u->parents]);
    }

    public function newParentQr(Request $request)
    {
        abort_unless($request->user()->isStudent(), 403);
        self::newToken($request->user());

        return back()->with('ok', __('Nouveau QR code généré'));
    }

    public function deletion(Request $request)
    {
        return view('user.deletion', ['u' => $request->user(), 'req' => $request->user()->deletionRequests()->latest()->first()]);
    }

    public function requestDeletion(Request $request)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $u = $request->user();
        abort_if($u->deletionRequests()->where('status', 'pending')->exists(), 422);
        DeletionRequest::create(['user_id' => $u->id, 'reason' => $data['reason'] ?? null, 'status' => 'pending']);
        foreach (\App\Models\User::where('role', 'super_admin')->active()->get() as $s) {
            $s->inform('Demande de suppression', $u->name.' demande la suppression de son compte.', route('super.requests'), 'account', 'mail');
        }

        return redirect()->route('profile')->with('ok', __('Demande envoyée au super administrateur'));
    }

    public function cancelDeletion(Request $request)
    {
        $request->user()->deletionRequests()->where('status', 'pending')->update(['status' => 'cancelled']);

        return back()->with('ok', __('Demande annulée'));
    }
}
