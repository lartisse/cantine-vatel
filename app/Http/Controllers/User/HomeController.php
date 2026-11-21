<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Reservation;
use App\Services\SuggestionService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** U1 Accueil + U11 confirmation de classe. */
class HomeController extends Controller
{
    public function index(Request $request, SuggestionService $suggestions)
    {
        $u = $request->user();
        $today = Menu::with('dishes')->whereDate('date', today())->where('is_published', true)->first();
        $res = $today ? Reservation::with('items.dish', 'menu')->where('user_id', $u->id)->where('menu_id', $today->id)->where('status', '!=', 'cancelled')->first() : null;
        $next = Menu::whereDate('date', '>', today())->where('is_published', true)->orderBy('date')->get()
            ->first(fn ($m) => ! Reservation::where('user_id', $u->id)->where('menu_id', $m->id)->where('status', '!=', 'cancelled')->exists());
        $toRate = Reservation::with('menu', 'items.dish')->where('user_id', $u->id)->where('status', 'served')
            ->whereDoesntHave('menu.reviews', fn ($q) => $q->where('user_id', $u->id))->latest('served_at')->first();
        $period = Settings::period();
        $askClass = $u->isStudent() && $u->class_confirmed_for !== $period['id'];
        $antigaspi = $today ? $today->items()->with('dish')->whereNotNull('antigaspi_price')->get() : collect();

        return view('user.home', [
            'u' => $u, 'today' => $today, 'res' => $res, 'next' => $next, 'toRate' => $toRate, 'askClass' => $askClass, 'period' => $period,
            'suggestion' => $suggestions->forUser($u), 'antigaspi' => $antigaspi, 'unread' => $u->unreadCount(), 'chefs' => $suggestions->chefOfTheWeek(3), 'interval' => Settings::suggestionInterval(),
        ]);
    }

    public function confirmClass(Request $request)
    {
        $u = $request->user();
        abort_unless($u->isStudent(), 403);
        $data = $request->validate(['action' => ['required', Rule::in(['confirm', 'skip', 'save'])], 'class_name' => ['nullable', Rule::in(config('cantine.classes'))]]);
        if ($data['action'] === 'save') {
            $u->class_name = $data['class_name'] ?? null;
        }
        $u->class_confirmed_for = Settings::period()['id'];
        $u->save();

        return back()->with($data['action'] === 'skip' ? 'info' : 'ok', $data['action'] === 'skip' ? __('D’accord. Vous pourrez l’ajouter depuis votre profil.') : __('Merci, classe confirmée'));
    }
}
