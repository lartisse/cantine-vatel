<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\AccountService;
use Illuminate\Http\Request;

/** U6 Portefeuille, U7 Recharger. */
class WalletController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $months = Transaction::where('user_id', $u->id)->selectRaw("strftime('%Y-%m', created_at) as m")->distinct()->orderByDesc('m')->pluck('m')->all();
        if (\DB::getDriverName() !== 'sqlite') {
            $months = Transaction::where('user_id', $u->id)->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as m")->distinct()->orderByDesc('m')->pluck('m')->all();
        }
        $month = $request->query('mois', $months[0] ?? now()->format('Y-m'));
        $txs = Transaction::where('user_id', $u->id)->where('created_at', '>=', $month.'-01')->where('created_at', '<', \Illuminate\Support\Carbon::parse($month.'-01')->addMonth())->latest()->get();
        $spent = -$txs->where('type', 'meal')->sum('amount') - $txs->where('type', 'refund')->sum('amount');
        $meals = $txs->where('type', 'meal')->count();

        return view('user.wallet', ['u' => $u, 'txs' => $txs->groupBy(fn ($t) => $t->created_at->toDateString()), 'months' => array_slice($months, 0, 4), 'month' => $month, 'spent' => max(0, $spent), 'meals' => $meals]);
    }

    public function topupForm(Request $request)
    {
        return view('user.topup', ['u' => $request->user(), 'methods' => PaymentMethod::activeFor(config('cantine.payment_env'))->where('kind', 'Mobile Money')->get()]);
    }

    public function topup(Request $request, AccountService $accounts)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:100', 'max:200000'], 'method' => ['required', 'integer'], 'phone' => ['nullable', 'string', 'max:30']],
            ['amount.min' => __('Montant minimum : 100 FCFA'), 'method.required' => __('Choisissez un moyen de paiement.')]);
        $method = PaymentMethod::activeFor(config('cantine.payment_env'))->findOrFail($data['method']);
        $payment = $accounts->topup($request->user(), (int) $data['amount'], $method, $data['phone'] ?? '');

        return redirect()->route('wallet')->with('ok', __('Recharge réussie : +:m (réf. :r)', ['m' => fcfa($payment->amount), 'r' => $payment->provider_reference]));
    }

    public function receipt(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        return view('common.receipt', ['t' => $transaction, 'u' => $request->user()]);
    }
}
