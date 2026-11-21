<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** A12 Alertes allergie : voir la commande, contacter, marquer comme vérifié (audit). */
class AlertController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $items = $u->appNotifications()->with('reservation.user.parents', 'reservation.checker', 'reservation.menu')->latest()->limit(80)->get();

        return view('admin.alerts', compact('items'));
    }

    public function check(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->allergy_conflict, 404);
        $reservation->update(['allergy_checked_by' => $request->user()->id, 'allergy_checked_at' => now()]);
        $request->user()->appNotifications()->where('reservation_id', $reservation->id)->update(['read_at' => now()]);
        AuditLogger::log('allergy_checked', $reservation->user ?? 'Reservation', 'Réservation '.$reservation->code.' · '.collect($reservation->allergy_conflict)->pluck('name')->join(', '));

        return back()->with('ok', 'Alerte marquée comme vérifiée');
    }

    public function contact(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['to' => ['required', 'in:user,parent'], 'message' => ['required', 'string', 'max:1000']]);
        $targets = $data['to'] === 'parent' ? $reservation->user?->parents ?? collect() : collect([$reservation->user])->filter();
        foreach ($targets as $t) {
            $t->inform('Message de la cantine', $data['message'], null, 'message', 'message', $reservation->id);
        }

        return back()->with('ok', $targets->count() ? 'Message envoyé ('.$targets->count().')' : 'Aucun destinataire');
    }

    public function readAll(Request $request)
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
