<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\ModerationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** U8 Avis sur les plats et/ou le menu (réservé aux convives ayant réservé ce menu). */
class ReviewController extends Controller
{
    protected function reservation(Request $request, Menu $menu): ?Reservation
    {
        return Reservation::with('items.dish')->where('user_id', $request->user()->id)->where('menu_id', $menu->id)->whereIn('status', ['paid', 'served', 'missed'])->first();
    }

    public function create(Request $request, Menu $menu)
    {
        $res = $this->reservation($request, $menu);
        $mine = Review::with('dish')->where('user_id', $request->user()->id)->where('menu_id', $menu->id)->get();

        return view('user.review', ['menu' => $menu, 'res' => $res, 'mine' => $mine, 'tooEarly' => $menu->date->isFuture()]);
    }

    public function store(Request $request, Menu $menu, ModerationService $moderation)
    {
        $u = $request->user();
        $res = $this->reservation($request, $menu);
        abort_unless($res, 403, __('Seuls les convives ayant réservé ce menu peuvent le noter.'));
        abort_if($menu->date->isFuture(), 403);
        abort_if(Review::where('user_id', $u->id)->where('menu_id', $menu->id)->exists(), 403, __('Vous avez déjà donné votre avis.'));
        $data = $request->validate([
            'dish' => ['array'], 'dish.*.rating' => ['nullable', 'integer', 'min:1', 'max:5'], 'dish.*.comment' => ['nullable', 'string', 'max:500'],
            'menu_rating' => ['nullable', 'integer', 'min:1', 'max:5'], 'menu_comment' => ['nullable', 'string', 'max:500'],
            'tags' => ['array'], 'ate_all' => ['nullable', Rule::in(['tout', 'moitie', 'peu'])],
        ]);
        $count = 0;
        $flagged = false;
        $dishIds = $res->items->pluck('dish_id')->all();
        foreach ($data['dish'] ?? [] as $dishId => $row) {
            if (! empty($row['rating']) && in_array((int) $dishId, $dishIds, true)) {
                $r = $moderation->apply(new Review(['user_id' => $u->id, 'menu_id' => $menu->id, 'dish_id' => $dishId, 'reservation_id' => $res->id, 'rating' => $row['rating'], 'comment' => $row['comment'] ?? null, 'status' => 'visible']));
                $r->save();
                $flagged = $flagged || $r->status === 'flagged';
                $count++;
            }
        }
        if (! empty($data['menu_rating'])) {
            $r = $moderation->apply(new Review(['user_id' => $u->id, 'menu_id' => $menu->id, 'reservation_id' => $res->id, 'rating' => $data['menu_rating'], 'comment' => $data['menu_comment'] ?? null, 'tags' => $data['tags'] ?? [], 'status' => 'visible']));
            $r->save();
            $flagged = $flagged || $r->status === 'flagged';
            $count++;
        }
        if (! $count) {
            return back()->withErrors(['rating' => __('Notez au moins un plat ou le menu.')]);
        }
        if (! empty($data['ate_all'])) {
            $res->update(['ate_all' => $data['ate_all']]);
        }

        return redirect()->route('user.home')->with('ok', $flagged ? __('Merci ! Votre avis sera publié après vérification.') : __('Merci pour votre avis !'));
    }
}
