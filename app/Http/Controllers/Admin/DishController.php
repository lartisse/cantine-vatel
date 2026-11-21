<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\ReservationItem;
use App\Services\AuditLogger;
use App\Services\ImageService;
use App\Services\SuggestionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** A5 Plats et recettes. */
class DishController extends Controller
{
    public function index(Request $request)
    {
        $cat = $request->query('cat');
        $q = trim((string) $request->query('q'));
        $dishes = Dish::query()->when($cat, fn ($w) => $w->where('category', $cat))->when($q, fn ($w) => $w->where('name', 'like', "%$q%"))
            ->when(! $request->boolean('archives'), fn ($w) => $w->where('is_archived', false))->orderBy('category')->orderBy('name')->get();
        SuggestionService::decorate($dishes);

        return view('admin.dishes', compact('dishes', 'cat', 'q'));
    }

    public function create()
    {
        return view('admin.dish-form', ['d' => new Dish(['category' => 'plat', 'allergens' => [], 'diets' => []]), 'ingredients' => Ingredient::orderBy('name')->get(), 'usage' => 0]);
    }

    public function edit(Dish $dish)
    {
        $dish->load('ingredients');

        return view('admin.dish-form', ['d' => $dish, 'ingredients' => Ingredient::orderBy('name')->get(), 'usage' => ReservationItem::where('dish_id', $dish->id)->sum('quantity')]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'], 'price' => ['required', 'integer', 'min:0', 'max:100000'],
            'category' => ['required', Rule::in(array_keys(config('cantine.categories')))], 'kcal' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'description' => ['nullable', 'string', 'max:1000'], 'creator_name' => ['nullable', 'string', 'max:120'],
            'allergens' => ['array'], 'allergens.*' => [Rule::in(array_keys(config('cantine.allergens')))],
            'diets' => ['array'], 'diets.*' => [Rule::in(array_keys(config('cantine.diets')))],
            'recipe' => ['array'], 'recipe.*.ingredient_id' => ['nullable', 'exists:ingredients,id'], 'recipe.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ], ['name.required' => 'Le nom est obligatoire.', 'price.required' => 'Le prix est obligatoire.']);
    }

    protected function save(Dish $dish, array $data): Dish
    {
        $dish->fill(collect($data)->except('recipe')->all() + ['allergens' => $data['allergens'] ?? [], 'diets' => $data['diets'] ?? []]);
        $dish->save();
        $sync = [];
        foreach ($data['recipe'] ?? [] as $row) {
            if (! empty($row['ingredient_id']) && (float) ($row['quantity'] ?? 0) > 0) {
                $sync[$row['ingredient_id']] = ['quantity' => $row['quantity']];
            }
        }
        $dish->ingredients()->sync($sync);

        return $dish;
    }

    public function store(Request $request)
    {
        $dish = $this->save(new Dish, $this->validated($request));

        return redirect()->route('admin.dishes.edit', $dish)->with('ok', $dish->name.' publié dans le catalogue');
    }

    public function update(Request $request, Dish $dish)
    {
        $this->save($dish, $this->validated($request));

        return back()->with('ok', 'Plat enregistré');
    }

    public function photo(Request $request, Dish $dish, ImageService $images)
    {
        $request->validate(['photo' => ImageService::RULES], ImageService::MESSAGES);
        $dish->update(['image_path' => $images->store($request->file('photo'), 'dishes', 640, $dish->image_path)]);

        return back()->with('ok', 'Photo enregistrée');
    }

    public function deletePhoto(Dish $dish, ImageService $images)
    {
        $images->delete($dish->image_path);
        $dish->update(['image_path' => null]);

        return back()->with('ok', 'Photo supprimée — image par défaut de la catégorie');
    }

    public function archive(Dish $dish)
    {
        $dish->update(['is_archived' => ! $dish->is_archived]);

        return redirect()->route('admin.dishes')->with('ok', $dish->is_archived ? 'Plat archivé' : 'Plat réactivé');
    }

    public function destroy(Dish $dish)
    {
        if (ReservationItem::where('dish_id', $dish->id)->exists()) {
            return back()->with('error', 'Ce plat figure dans des réservations : archivez-le plutôt.');
        }
        $dish->delete(); // suppression douce
        AuditLogger::log('dish_delete', $dish);

        return redirect()->route('admin.dishes')->with('ok', 'Plat supprimé');
    }
}
