<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** A4 Stocks : besoins calculés depuis les menus et recettes, seuils, entrées, pertes, bon de commande. */
class StockController extends Controller
{
    /** Besoin de la semaine par ingrédient = Σ (portions prévues × quantité par portion). */
    public static function needs(): array
    {
        $monday = today()->startOfWeek();
        $needs = [];
        foreach (Menu::with('items.dish.ingredients')->whereBetween('date', [$monday, $monday->copy()->addDays(4)])->get() as $m) {
            foreach ($m->items as $it) {
                foreach ($it->dish?->ingredients ?? [] as $ing) {
                    $needs[$ing->id] = ($needs[$ing->id] ?? 0) + $ing->pivot->quantity * $it->planned_portions;
                }
            }
        }

        return $needs;
    }

    public function index()
    {
        $ingredients = Ingredient::orderBy('name')->get();
        $needs = self::needs();
        $moves = StockMovement::with('ingredient')->latest()->limit(12)->get();

        return view('admin.stocks', compact('ingredients', 'needs', 'moves'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'unit' => ['required', 'string', 'max:10'], 'stock' => ['required', 'numeric', 'min:0'], 'threshold' => ['required', 'numeric', 'min:0']]);
        Ingredient::create($data);

        return back()->with('ok', 'Ingrédient ajouté');
    }

    public function move(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate(['type' => ['required', Rule::in(['entree', 'perte'])], 'quantity' => ['required', 'numeric', 'min:0.01'], 'reason' => ['nullable', 'string', 'max:120']]);
        if ($data['type'] === 'perte' && empty($data['reason'])) {
            return back()->withErrors(['reason' => 'Le motif de la perte est obligatoire.']);
        }
        $ingredient->stock = max(0, $ingredient->stock + ($data['type'] === 'entree' ? 1 : -1) * $data['quantity']);
        $ingredient->save();
        StockMovement::create(['ingredient_id' => $ingredient->id, 'type' => $data['type'], 'quantity' => $data['quantity'], 'reason' => $data['reason'] ?? null, 'author' => $request->user()->name]);

        return back()->with('ok', ($data['type'] === 'entree' ? 'Entrée' : 'Perte').' enregistrée : '.$ingredient->name);
    }

    public function order()
    {
        $needs = self::needs();
        $lines = Ingredient::orderBy('name')->get()->map(function ($i) use ($needs) {
            $need = $needs[$i->id] ?? 0;
            $qty = max(0, ceil($need + $i->threshold - $i->stock));

            return ['ing' => $i, 'need' => $need, 'qty' => $qty];
        })->filter(fn ($l) => $l['qty'] > 0);

        return view('admin.purchase-order', compact('lines'));
    }
}
