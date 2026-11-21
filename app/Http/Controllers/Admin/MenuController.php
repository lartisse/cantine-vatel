<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** A2 Menus de la semaine : glisser-déposer depuis le catalogue, portions, duplication, publication. */
class MenuController extends Controller
{
    protected function week(Request $request): array
    {
        $offset = (int) $request->input('semaine', 1);
        $monday = today()->startOfWeek()->addWeeks($offset);
        $menus = collect();
        for ($i = 0; $i < 5; $i++) {
            $day = $monday->copy()->addDays($i);
            $menus->push(Menu::whereDate('date', $day)->first() ?? Menu::create(['date' => $day, 'is_published' => false]));
        }

        return [$offset, $monday, $menus];
    }

    public function index(Request $request)
    {
        [$offset, $monday, $menus] = $this->week($request);
        $menus->each->load('items.dish');
        $reserved = [];
        foreach ($menus as $m) {
            $reserved[$m->id] = $m->reservedByDish();
        }
        $catalog = Dish::available()->orderBy('category')->orderBy('name')->get();

        return view('admin.menus', compact('offset', 'monday', 'menus', 'reserved', 'catalog'));
    }

    public function addDish(Request $request, Menu $menu)
    {
        $data = $request->validate(['dish_id' => ['required', 'exists:dishes,id'], 'planned_portions' => ['nullable', 'integer', 'min:1', 'max:1000'], 'category' => ['nullable', 'string']]);
        $dish = Dish::findOrFail($data['dish_id']);
        if (! empty($data['category']) && $data['category'] !== $dish->category) {
            return $this->reply($request, false, 'Ce plat est un(e) '.mb_strtolower($dish->categoryLabel()).' : déposez-le dans la bonne ligne.');
        }
        if ($menu->items()->where('dish_id', $dish->id)->exists()) {
            return $this->reply($request, false, 'Ce plat est déjà au menu ce jour-là.');
        }
        $menu->items()->create(['dish_id' => $dish->id, 'planned_portions' => $data['planned_portions'] ?? ($dish->category === 'plat' ? 80 : 60)]);

        return $this->reply($request, true, $dish->name.' ajouté au '.$menu->label('l j M'));
    }

    public function updateItem(Request $request, MenuItem $item)
    {
        $data = $request->validate(['planned_portions' => ['required', 'integer', 'min:0', 'max:1000']]);
        $item->update($data);

        return back()->with('ok', 'Portions mises à jour');
    }

    public function removeDish(Request $request, MenuItem $item, ReservationService $service)
    {
        $item->load('menu', 'dish');
        $affected = Reservation::with('items.dish', 'menu', 'user')->where('menu_id', $item->menu_id)->where('status', 'paid')
            ->whereHas('items', fn ($q) => $q->where('dish_id', $item->dish_id))->get();
        foreach ($affected as $r) {
            // Remboursement et notification des convives concernés
            $r->update(['status' => 'cancelled']);
            if ($r->user) {
                $r->user->balance += $r->total;
                $r->user->save();
                \App\Models\Transaction::create(['user_id' => $r->user_id, 'type' => 'refund', 'amount' => $r->total, 'label' => 'Plat retiré du menu — '.$item->dish->name]);
                $r->user->inform('Plat retiré du menu', $item->dish->name.' a été retiré du menu du '.$item->menu->label('j M').'. Votre réservation est remboursée.', route('menu'), 'refund', 'undo');
            }
        }
        $item->delete();

        return back()->with('ok', $item->dish->name.' retiré'.($affected->count() ? ' — '.$affected->count().' réservation(s) remboursée(s)' : ''));
    }

    public function duplicate(Request $request)
    {
        [$offset, $monday, $menus] = $this->week($request);
        foreach ($menus as $i => $menu) {
            $prev = Menu::with('items')->whereDate('date', $monday->copy()->subWeek()->addDays($i))->first();
            if (! $prev) {
                continue;
            }
            foreach ($prev->items as $it) {
                $menu->items()->firstOrCreate(['dish_id' => $it->dish_id], ['planned_portions' => $it->planned_portions]);
            }
        }

        return redirect()->route('admin.menus', ['semaine' => $offset])->with('ok', 'Semaine précédente dupliquée');
    }

    public function publish(Request $request)
    {
        [$offset, $monday, $menus] = $this->week($request);
        $empty = $menus->filter(fn ($m) => $m->items()->count() === 0);
        if ($empty->count() === $menus->count()) {
            return back()->with('error', 'Aucun plat dans cette semaine.');
        }
        foreach ($menus as $m) {
            if ($m->items()->count()) {
                $m->update(['is_published' => true, 'published_at' => now()]);
            }
        }
        $label = 'du '.$monday->locale('fr')->translatedFormat('j').' au '.$monday->copy()->addDays(4)->locale('fr')->translatedFormat('j F');
        User::whereIn('role', ['user', 'parent'])->active()->select('id')->chunk(200, function ($users) use ($label) {
            $now = now();
            \App\Models\AppNotification::insert($users->map(fn ($u) => ['user_id' => $u->id, 'title' => 'Menu de la semaine publié', 'body' => 'Le menu '.$label.' est en ligne.', 'url' => '/menu', 'kind' => 'menu', 'icon' => 'calendar', 'created_at' => $now, 'updated_at' => $now])->all());
        });

        return back()->with('ok', 'Menu publié'.($empty->count() ? ' ('.$empty->count().' jour(s) sans plat non publiés)' : '').' — convives notifiés');
    }

    protected function reply(Request $request, bool $ok, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'ok' : 'error', $message);
    }
}
