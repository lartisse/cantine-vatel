<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ModerationService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** A8 Modération des avis. */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('statut', 'flagged');
        $type = $request->query('type', 'all');
        $sort = $request->query('tri', 'recent');
        $q = Review::with('user', 'dish', 'menu')->whereNotNull('comment');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($type === 'dish') {
            $q->whereNotNull('dish_id');
        } elseif ($type === 'menu') {
            $q->whereNull('dish_id');
        }
        match ($sort) {
            'asc' => $q->orderBy('rating'), 'desc' => $q->orderByDesc('rating'), default => $q->latest(),
        };
        $counts = Review::whereNotNull('comment')->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.reviews', ['reviews' => $q->paginate(30)->withQueryString(), 'status' => $status, 'type' => $type, 'sort' => $sort, 'counts' => $counts, 'words' => Settings::forbiddenWords()]);
    }

    public function moderate(Request $request, Review $review)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['approve', 'hide', 'republish'])], 'reason' => ['nullable', 'string', 'max:60']]);
        if ($data['action'] === 'hide') {
            $review->update(['status' => 'hidden', 'flag_reason' => $data['reason'] ?? 'Hors sujet', 'moderated_by' => $request->user()->id]);
        } else {
            $review->update(['status' => 'visible', 'approved' => true, 'moderated_by' => $request->user()->id]);
        }

        return back()->with('ok', ['approve' => 'Avis approuvé et publié', 'hide' => 'Avis masqué', 'republish' => 'Avis republié'][$data['action']]);
    }

    public function words(Request $request, ModerationService $moderation)
    {
        $words = collect(preg_split('/[\n,;]+/', (string) $request->input('words')))->map(fn ($w) => trim($w))->filter()->unique()->values()->all();
        Settings::set('forbidden_words', $words);
        // Réanalyse des avis publiés non approuvés
        $n = 0;
        foreach (Review::where('status', 'visible')->where('approved', false)->whereNotNull('comment')->get() as $r) {
            $moderation->apply($r);
            if ($r->isDirty('status')) {
                $r->save();
                $n++;
            }
        }

        return back()->with('ok', 'Liste enregistrée ('.count($words).' mots) — '.$n.' avis nouvellement signalé(s)');
    }
}
