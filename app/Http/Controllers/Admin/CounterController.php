<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;

/** A11 Comptoir : scan QR (caméra) ou saisie du code à 6 chiffres. */
class CounterController extends Controller
{
    public function __construct(protected ReservationService $service) {}

    public function index(Request $request)
    {
        $menu = Menu::whereDate('date', today())->first();
        $total = $menu ? Reservation::where('menu_id', $menu->id)->whereIn('status', ['paid', 'served'])->count() : 0;
        $served = $menu ? Reservation::where('menu_id', $menu->id)->where('status', 'served')->count() : 0;
        $recent = $menu ? Reservation::with('user', 'items.dish')->where('menu_id', $menu->id)->where('status', 'served')->latest('served_at')->limit(6)->get() : collect();

        return view('admin.counter', ['result' => session('result'), 'total' => $total, 'served' => $served, 'recent' => $recent, 'slot' => $this->service->currentSlot()]);
    }

    public function scan(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']], ['code.required' => 'Saisissez ou scannez un code.']);
        $result = $this->service->scan($data['code'], $request->boolean('force'), $request->user());
        $r = $result['reservation'];
        $payload = ['state' => $result['state'], 'title' => $result['title'], 'can_force' => $result['can_force'] ?? false, 'id' => $r?->id, 'code' => $data['code']];

        return redirect()->route('admin.counter')->with('result', $payload);
    }
}
