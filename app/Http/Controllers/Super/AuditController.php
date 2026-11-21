<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** S6 Journal d'audit (lecture seule) + export CSV. */
class AuditController extends Controller
{
    protected function query(Request $request)
    {
        $action = $request->query('action');
        $q = trim((string) $request->query('q'));

        return AuditLog::query()->when($action, fn ($w) => $w->where('action', $action))
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('actor_name', 'like', "%$q%")->orWhere('target_label', 'like', "%$q%")->orWhere('details', 'like', "%$q%")))
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    public function index(Request $request)
    {
        return view('super.audit', ['logs' => $this->query($request)->paginate(40)->withQueryString(), 'labels' => AuditLogger::LABELS]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Quand', 'Qui', 'Rôle', 'Action', 'Cible', 'Détail', 'IP'], ';');
            foreach ($rows as $l) {
                fputcsv($out, [$l->created_at?->format('d/m/Y H:i'), $l->actor_name, $l->actor_role, AuditLogger::LABELS[$l->action] ?? $l->action, $l->target_label, $l->details, $l->ip], ';');
            }
            fclose($out);
        }, 'journal-audit-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
