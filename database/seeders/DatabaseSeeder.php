<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\DeletionRequest;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\LinkToken;
use App\Models\Menu;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Clock;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Données de démonstration reprises de maquette/js/data.js. Mot de passe de tous les comptes : « password ». */
class DatabaseSeeder extends Seeder
{
    protected array $dish = [];

    protected array $ing = [];

    public function run(): void
    {
        mt_srand(42);
        $today = Carbon::today();
        // Semaine de référence = semaine en cours (ou la suivante le week-end) : les données suivent la date réelle.
        $monday = $today->isWeekend() ? $today->copy()->next(Carbon::MONDAY) : $today->copy()->startOfWeek(Carbon::MONDAY);
        $pwd = Hash::make('password');

        // ---------- Paramètres ----------
        foreach ([
            'cutoff' => config('cantine.cutoff'), 'trash_days' => config('cantine.trash_days'),
            'slots' => config('cantine.slots'), 'forbidden_words' => config('cantine.forbidden_words'),
            'period' => ['id' => '2026-T2', 'label' => '2e trimestre 2026-2027'], 'default_threshold' => 1000,
            'refund_rule' => 'Remboursement intégral sur le solde avant l’heure limite',
        ] as $k => $v) {
            Settings::set($k, $v);
        }

        // ---------- Ingrédients ----------
        foreach ([
            ['Riz', 'kg', 180, 60], ['Poulet', 'kg', 38, 40], ['Poisson (carpe)', 'kg', 22, 30], ['Attiéké', 'kg', 45, 20],
            ['Huile', 'L', 24, 10], ['Tomates', 'kg', 30, 15], ['Oignons', 'kg', 26, 10], ['Pâte d’arachide', 'kg', 4, 6],
            ['Banane plantain', 'kg', 70, 40], ['Spaghetti', 'kg', 25, 10], ['Lait', 'L', 18, 15], ['Fruits frais', 'kg', 0, 15],
            ['Fleurs de bissap', 'kg', 6, 3], ['Gingembre', 'kg', 5, 3],
        ] as $i => [$name, $unit, $stock, $th]) {
            $this->ing['i'.($i + 1)] = Ingredient::create(compact('name', 'unit', 'stock') + ['threshold' => $th])->id;
        }

        // ---------- Plats ----------
        $chefs = ['Koffi B. (BTS Cuisine)', 'Aya T. (Bachelor 2)', 'Moussa O. (BTS Cuisine)', null];
        $dishes = [
            ['Salade composée', 'entree', 300, 120, ['oeuf'], ['vegetarien', 'sans-porc'], 'Laitue, tomate, concombre, carotte râpée et œuf dur, vinaigrette maison.', [['i6', 0.08], ['i12', 0.05]]],
            ['Avocat crevettes', 'entree', 500, 210, ['fruits-de-mer'], ['sans-porc'], 'Demi-avocat garni de crevettes, sauce cocktail.', [['i12', 0.1]]],
            ['Soupe de légumes', 'entree', 300, 90, [], ['vegetarien', 'sans-porc'], 'Velouté de légumes de saison.', [['i6', 0.1], ['i7', 0.03]]],
            ['Attiéké poisson braisé', 'plat', 1500, 640, ['poisson'], ['sans-porc'], 'Attiéké frais, carpe braisée, oignons et tomates crus, piment à part.', [['i4', 0.25], ['i3', 0.3], ['i6', 0.05], ['i7', 0.04]]],
            ['Riz sauce arachide, poulet', 'plat', 1200, 720, ['arachide'], ['sans-porc'], 'Riz blanc, sauce arachide onctueuse et cuisse de poulet.', [['i1', 0.15], ['i2', 0.25], ['i8', 0.06]]],
            ['Foutou sauce graine', 'plat', 1300, 780, ['poisson'], ['sans-porc'], 'Foutou banane, sauce graine au poisson fumé et viande de bœuf.', [['i9', 0.3], ['i3', 0.1]]],
            ['Alloco, poulet braisé', 'plat', 1200, 690, [], ['sans-porc'], 'Bananes plantain frites, demi-poulet braisé, sauce tomate oignon.', [['i9', 0.25], ['i2', 0.3], ['i5', 0.03]]],
            ['Spaghetti bolognaise', 'plat', 1000, 650, ['gluten', 'lactose'], ['sans-porc'], 'Spaghetti, sauce tomate au bœuf haché, fromage râpé.', [['i10', 0.12], ['i6', 0.08]]],
            ['Riz gras aux légumes', 'plat', 900, 560, [], ['vegetarien', 'sans-porc'], 'Riz cuit dans une sauce tomate aux légumes, version végétarienne.', [['i1', 0.18], ['i6', 0.1], ['i5', 0.03]]],
            ['Placali sauce gombo', 'plat', 1100, 600, ['poisson'], ['sans-porc'], 'Placali, sauce gombo au poisson fumé.', [['i3', 0.12]]],
            ['Poulet DG', 'plat', 1500, 820, [], ['sans-porc'], 'Poulet sauté aux plantains mûrs, carottes et poivrons.', [['i2', 0.3], ['i9', 0.15], ['i5', 0.03]]],
            ['Salade de fruits', 'dessert', 300, 110, [], ['vegetarien', 'sans-porc'], 'Ananas, papaye, mangue et pastèque de saison.', [['i12', 0.15]]],
            ['Yaourt nature', 'dessert', 250, 90, ['lactose'], ['vegetarien', 'sans-porc'], 'Yaourt local au lait entier.', [['i11', 0.12]]],
            ['Dêguê', 'dessert', 350, 180, ['lactose'], ['vegetarien', 'sans-porc'], 'Mil au lait caillé sucré.', [['i11', 0.15]]],
            ['Gâteau marbré', 'dessert', 400, 260, ['gluten', 'lactose', 'oeuf'], ['vegetarien', 'sans-porc'], 'Part de gâteau marbré chocolat-vanille.', []],
            ['Jus de bissap', 'boisson', 200, 80, [], ['vegetarien', 'sans-porc'], 'Infusion d’hibiscus sucrée, servie fraîche.', [['i13', 0.02]]],
            ['Jus de gingembre', 'boisson', 200, 85, [], ['vegetarien', 'sans-porc'], 'Gingembre frais pressé, citron, menthe.', [['i14', 0.02]]],
            ['Eau minérale 50 cl', 'boisson', 150, 0, [], ['vegetarien', 'sans-porc'], 'Bouteille 50 cl.', []],
        ];
        foreach ($dishes as $i => [$name, $cat, $price, $kcal, $all, $diets, $desc, $recipe]) {
            $d = Dish::create(['name' => $name, 'category' => $cat, 'price' => $price, 'kcal' => $kcal, 'allergens' => $all, 'diets' => $diets,
                'description' => $desc, 'creator_name' => $chefs[$i % 4]]);
            foreach ($recipe as [$ik, $q]) {
                $d->ingredients()->attach($this->ing[$ik], ['quantity' => $q]);
            }
            $this->dish['d'.($i + 1)] = $d->id;
        }

        // ---------- Comptes de démonstration ----------
        $mk = function (array $a) use ($pwd) {
            $u = new User;
            $u->forceFill($a + ['password' => $pwd, 'status' => 'active', 'locale' => 'fr', 'notify' => ['push' => true, 'sms' => false, 'email' => true]])->save();

            return $u;
        };
        $awa = $mk(['role' => 'user', 'account_type' => 'eleve', 'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa.kone@vatel.ci', 'phone' => '07 07 12 34 56',
            'class_name' => '3e A', 'matricule' => 'VAT-2031', 'balance' => 4500, 'allergies' => ['arachide'], 'diets' => [], 'class_confirmed_for' => '2026-T1']);
        $yao = $mk(['role' => 'user', 'account_type' => 'eleve', 'first_name' => 'Yao', 'last_name' => 'Koné', 'email' => 'yao.kone@vatel.ci', 'phone' => '07 07 22 33 44',
            'class_name' => '6e B', 'matricule' => 'VAT-2290', 'balance' => 800, 'allergies' => [], 'diets' => [], 'class_confirmed_for' => '2026-T2']);
        $ibrahim = $mk(['role' => 'parent', 'first_name' => 'Ibrahim', 'last_name' => 'Koné', 'email' => 'i.kone@gmail.com', 'phone' => '05 55 01 02 03', 'notify' => ['push' => true, 'sms' => true, 'email' => true]]);
        $mariam = $mk(['role' => 'admin', 'first_name' => 'Mariam', 'last_name' => 'Diallo', 'email' => 'gestion@cantine-vatel.ci', 'phone' => '27 22 00 00 00', 'position' => 'Gestionnaire de la cantine']);
        $mk(['role' => 'admin', 'first_name' => 'Hervé', 'last_name' => 'Konan', 'email' => 'h.konan@cantine-vatel.ci', 'phone' => '07 48 11 22 33', 'position' => 'Adjoint gestionnaire']);
        $kader = $mk(['role' => 'super_admin', 'first_name' => 'Kader', 'last_name' => 'Touré', 'email' => 'superadmin@cantine-vatel.ci', 'phone' => '07 00 11 22 33', 'position' => 'Super administrateur']);
        $jm = $mk(['role' => 'user', 'account_type' => 'personnel', 'first_name' => 'Jean-Marc', 'last_name' => 'Aka', 'email' => 'jm.aka@vatel.ci', 'phone' => '07 58 44 33 22',
            'position' => 'Professeur de mathématiques', 'balance' => 6000, 'allergies' => [], 'diets' => ['sans-porc']]);
        $mk(['role' => 'admin', 'first_name' => 'Serge', 'last_name' => 'Kouadio', 'email' => 'comptoir1@cantine-vatel.ci', 'position' => 'Agent de comptoir']);
        $ines = $mk(['role' => 'user', 'account_type' => 'eleve', 'first_name' => 'Inès', 'last_name' => 'Koné', 'email' => 'ines.kone@vatel.ci',
            'class_name' => 'CM2', 'matricule' => 'VAT-2402', 'balance' => 2000, 'allergies' => ['lactose'], 'diets' => [], 'class_confirmed_for' => '2026-T2']);
        $ibrahim->children()->attach([$awa->id => ['linked_at' => now()->subMonths(2)], $yao->id => ['linked_at' => now()->subMonths(2)]]);
        LinkToken::create(['child_id' => $ines->id, 'token' => Str::random(40), 'code' => 'INE-5Q1', 'expires_at' => now()->addYear()]);
        LinkToken::create(['child_id' => $awa->id, 'token' => Str::random(40), 'code' => 'AWA-7K2', 'expires_at' => now()->addMinutes(15)]);

        // ---------- Autres convives ----------
        $firsts = ['Koffi', 'Aya', 'Moussa', 'Fatou', 'Jean', 'Mariam', 'Ismaël', 'Adjoua', 'Serge', 'Grâce', 'Ibrahim', 'Christelle', 'Yves', 'Aminata', 'Paul', 'Salimata', 'Kevin', 'Nadia', 'Hervé', 'Rokia', 'Arnaud', 'Esther', 'Bakary', 'Prisca', 'Didier', 'Awa', 'Lucien', 'Sandrine', 'Oumar', 'Laetitia'];
        $lasts = ['Kouassi', 'Traoré', 'Yao', 'Ouattara', 'Bamba', 'Coulibaly', 'Diabaté', 'N’Guessan', 'Koffi', 'Soro', 'Touré', 'Aka', 'Gnamien', 'Konan', 'Fofana', 'Doumbia'];
        $groups = ['6e A', '6e B', '5e A', '4e B', '3e A', '2nde C', '1re D', 'Tle A', 'Personnel', 'Personnel'];
        $postes = ['Comptable', 'Professeur de français', 'Surveillant', 'Secrétaire', 'Agent d’entretien', 'Bibliothécaire'];
        $pick = fn (array $a) => $a[mt_rand(0, count($a) - 1)];
        $others = [];
        for ($i = 0; $i < 120; $i++) {
            $g = $pick($groups);
            $staff = $g === 'Personnel';
            $noClass = ! $staff && mt_rand(0, 99) < 12;
            $first = $pick($firsts);
            $last = $pick($lasts);
            $others[] = $mk(['role' => 'user', 'account_type' => $staff ? 'personnel' : 'eleve', 'first_name' => $first, 'last_name' => $last,
                'email' => Str::slug($first.'.'.$last, '.').'.'.$i.'@vatel.ci', 'position' => $staff ? $pick($postes) : null,
                'class_name' => $staff || $noClass ? null : $g, 'matricule' => $staff ? null : 'VAT-'.(3000 + $i), 'balance' => mt_rand(0, 12) * 500,
                'allergies' => mt_rand(0, 99) < 12 ? [$pick(['arachide', 'gluten', 'lactose', 'fruits-de-mer'])] : [], 'diets' => [], 'class_confirmed_for' => '2026-T2']);
        }

        // ---------- Menus : 3 semaines passées + semaine en cours + semaine suivante (brouillon) ----------
        $weekPlan = [
            ['entree' => ['d1'], 'plat' => ['d7', 'd9'], 'dessert' => ['d12'], 'boisson' => ['d16', 'd18']],
            ['entree' => ['d3', 'd2'], 'plat' => ['d4', 'd5'], 'dessert' => ['d14', 'd12'], 'boisson' => ['d16', 'd17', 'd18']],
            ['entree' => ['d1'], 'plat' => ['d8', 'd6'], 'dessert' => ['d13'], 'boisson' => ['d17', 'd18']],
            ['entree' => ['d3'], 'plat' => ['d11', 'd9'], 'dessert' => ['d15'], 'boisson' => ['d16', 'd18']],
            ['entree' => ['d2'], 'plat' => ['d10', 'd7'], 'dessert' => ['d12'], 'boisson' => ['d16', 'd17']],
        ];
        $menus = [];
        for ($w = -3; $w <= 0; $w++) {
            foreach ($weekPlan as $di => $plan) {
                $date = $monday->copy()->addWeeks($w)->addDays($di);
                $menu = Menu::create(['date' => $date, 'is_published' => true, 'published_at' => $monday->copy()->addWeeks($w)->subDays(2)->setTime(18, 0), 'is_closed' => $date->lt($today)]);
                foreach ($plan as $cat => $ids) {
                    foreach ($ids as $k) {
                        $menu->items()->create(['dish_id' => $this->dish[$k], 'planned_portions' => $cat === 'plat' ? 80 : 60]);
                    }
                }
                $menus[$date->toDateString()] = [$menu, $plan];
            }
        }
        $next = Menu::create(['date' => $monday->copy()->addWeek(), 'is_published' => false]);
        $next->items()->create(['dish_id' => $this->dish['d4'], 'planned_portions' => 80]);
        foreach (range(1, 4) as $d) {
            Menu::create(['date' => $monday->copy()->addWeek()->addDays($d), 'is_published' => false]);
        }

        // Dernier jour de service passé et prochain jour de service (aujourd'hui en semaine)
        $dates = array_keys($menus);
        $lastPastDate = collect($dates)->filter(fn ($d) => Carbon::parse($d)->lt($today))->last();
        $nextOpenDate = collect($dates)->first(fn ($d) => Carbon::parse($d)->gte($today));
        $afterNextDate = collect($dates)->first(fn ($d) => $d > $nextOpenDate);
        // Le riz sauce arachide est au menu du prochain jour de service (cas de démonstration allergie)
        if (! in_array('d5', $menus[$nextOpenDate][1]['plat'], true)) {
            $menus[$nextOpenDate][1]['plat'][] = 'd5';
            $menus[$nextOpenDate][0]->items()->create(['dish_id' => $this->dish['d5'], 'planned_portions' => 80]);
        }

        // ---------- Réservations ----------
        $prices = Dish::pluck('price', 'id');
        $slots = array_keys(config('cantine.slots'));
        $resRows = [];
        $itemRows = [];
        $rid = 0;
        $add = function ($user, Menu $menu, string $slot, array $keys, string $status, ?string $code = null) use (&$resRows, &$itemRows, &$rid, $prices) {
            $rid++;
            $qty = array_count_values(array_map(fn ($k) => $this->dish[$k], $keys));
            $total = 0;
            foreach ($qty as $id => $q) {
                $total += $prices[$id] * $q;
                $itemRows[] = ['reservation_id' => $rid, 'dish_id' => $id, 'quantity' => $q, 'unit_price' => $prices[$id]];
            }
            $resRows[] = ['id' => $rid, 'user_id' => $user->id, 'menu_id' => $menu->id, 'slot' => $slot, 'status' => $status,
                'code' => $code ?? (string) mt_rand(100000, 999999), 'qr_token' => Str::random(32), 'total' => $total,
                'class_snapshot' => $user->account_type === 'eleve' ? $user->class_name : null,
                'served_at' => $status === 'served' ? $menu->date->copy()->setTimeFromTimeString($slot)->addMinutes(mt_rand(1, 25)) : null,
                'allergy_conflict' => null, 'ate_all' => $status === 'served' ? ['tout', 'tout', 'tout', 'moitie', 'peu'][mt_rand(0, 4)] : null,
                'created_at' => $menu->date->copy()->subDay()->setTime(18, 0), 'updated_at' => $menu->date->copy()->subDay()->setTime(18, 0)];

            return $rid;
        };
        foreach ($menus as $dateStr => [$menu, $plan]) {
            $date = Carbon::parse($dateStr);
            $isPast = $date->lt($today);
            $isToday = $dateStr === $nextOpenDate;
            if (! $isPast && ! $isToday && $dateStr !== $afterNextDate) {
                continue;
            }
            $count = $isToday ? 96 : ($dateStr === $afterNextDate ? 41 : 85 + mt_rand(0, 30));
            for ($i = 0; $i < $count; $i++) {
                $u = $others[($i * ($isToday || $isPast ? 1 : 3)) % count($others)];
                $keys = [$pick($plan['plat'])];
                if (mt_rand(0, 99) < 50) {
                    array_unshift($keys, $pick($plan['entree']));
                }
                if (mt_rand(0, 99) < 60) {
                    $keys[] = $pick($plan['dessert']);
                }
                if (mt_rand(0, 99) < 70) {
                    $keys[] = $pick($plan['boisson']);
                }
                $status = $isPast ? (mt_rand(0, 99) < 91 ? 'served' : 'missed') : 'paid';
                $add($u, $menu, $pick($slots), $keys, $status);
            }
        }
        // $mon = dernier jour servi, $tue = prochain jour de service
        [$mon, $planL] = $menus[$lastPastDate];
        [$tue, $planN] = $menus[$nextOpenDate];
        $dishName = fn ($k) => Dish::find($this->dish[$k])->name;
        $awaMain = $planL['plat'][0];
        $awaMon = $add($awa, $mon, '12:00', [$planL['entree'][0], $awaMain, $planL['dessert'][0], $planL['boisson'][0]], 'served', '481516');
        $yaoMon = $add($yao, $mon, '11:30', [$planL['plat'][1] ?? $awaMain, $planL['boisson'][0]], 'served');
        $yaoMain = $planN['plat'][0];
        $yaoTue = $add($yao, $tue, '11:30', [$yaoMain, $planN['dessert'][0]], 'paid', '204863');
        foreach (array_chunk($resRows, 300) as $chunk) {
            foreach ($chunk as &$r) {
                $r['allergy_conflict'] = $r['allergy_conflict'] ? json_encode($r['allergy_conflict']) : null;
            }
            DB::table('reservations')->insert($chunk);
        }
        foreach (array_chunk($itemRows, 500) as $chunk) {
            DB::table('reservation_items')->insert($chunk);
        }

        // Cas de démo : un élève allergique à l'arachide a réservé le riz sauce arachide aujourd'hui
        $risk = Reservation::with('user', 'menu')->where('menu_id', $tue->id)
            ->whereHas('items', fn ($q) => $q->where('dish_id', $this->dish['d5']))->whereHas('user', fn ($q) => $q->where('account_type', 'eleve')->whereNotNull('class_name'))->first();
        if ($risk) {
            $risk->user->forceFill(['allergies' => ['arachide']])->save();
            $risk->update(['allergy_conflict' => [['dish_id' => $this->dish['d5'], 'name' => 'Riz sauce arachide, poulet', 'allergens' => ['arachide']]]]);
            foreach (User::whereIn('role', ['admin', 'super_admin'])->get() as $adm) {
                $n = $adm->inform('Allergie : réservation à risque', "{$risk->user->name} ({$risk->user->groupLabel()}) a réservé « Riz sauce arachide, poulet », qui contient : arachide, allergène déclaré dans son profil. ".$risk->menu->label('l j M').', créneau '.$risk->slotLabel().'.', '/admin/alertes', 'allergy', 'alert', $risk->id);
                $n->forceFill(['created_at' => now()->subMinutes(40)])->save();
            }
        }

        // ---------- Transactions et paiements ----------
        $wave = null;
        $tx = fn ($u, $d, $type, $label, $amount, $ref = null) => Transaction::create(['user_id' => $u->id, 'type' => $type, 'label' => $label, 'amount' => $amount, 'reference' => $ref])->forceFill(['created_at' => Carbon::parse($d)->setTime(12, 0)])->save();
        $ago = fn (int $days) => Carbon::parse($lastPastDate)->subDays($days)->toDateString();
        $tx($awa, $ago(21), 'topup', 'Recharge Wave (par Ibrahim Koné)', 10000, 'WV-88213');
        $tx($awa, $ago(20), 'meal', 'Repas — Attiéké poisson braisé', -1700);
        $tx($awa, $ago(18), 'meal', 'Repas — Alloco, poulet braisé', -1400);
        $tx($awa, $ago(13), 'meal', 'Repas — Poulet DG', -1700);
        $tx($awa, $ago(11), 'refund', 'Annulation — Spaghetti bolognaise', 1200);
        $tx($awa, $ago(7), 'topup', 'Recharge Orange Money', 2500, 'OM-55102');
        $tx($awa, $ago(5), 'meal', 'Repas — Riz gras aux légumes', -1100);
        $tx($awa, $lastPastDate, 'meal', 'Repas — '.$dishName($awaMain), -2000);
        $tx($yao, $ago(14), 'topup', 'Recharge Wave (par Ibrahim Koné)', 5000, 'WV-80044');
        $tx($yao, $lastPastDate, 'meal', 'Repas — '.$dishName($planL['plat'][1] ?? $awaMain), -1100);
        $tx($yao, min($nextOpenDate, $today->toDateString()), 'meal', 'Repas — '.$dishName($yaoMain), -1800);
        $tx($jm, $ago(21), 'topup', 'Recharge Wave', 10000, 'WV-87001');

        // ---------- Moyens de paiement (clés de démonstration chiffrées) ----------
        $providers = [
            ['wave', 'Wave', 'Mobile Money', '#1dc8f2', 'W', [['apiKey', 'Clé API'], ['webhookSecret', 'Secret du webhook']], true, ['apiKey' => ['wave_sn_test_', 'wave_sn_live_'], 'webhookSecret' => ['whsec_', 'whsec_']]],
            ['om', 'Orange Money', 'Mobile Money', '#ff7900', 'OM', [['clientId', 'Client ID'], ['clientSecret', 'Client secret'], ['merchantKey', 'Clé marchand']], true, ['clientId' => ['om_', 'om_'], 'clientSecret' => ['cs_test_', 'cs_live_'], 'merchantKey' => ['mk_', 'mk_']]],
            ['momo', 'MTN MoMo', 'Mobile Money', '#ffcc00', 'M', [['subscriptionKey', 'Clé d’abonnement'], ['apiUser', 'API user'], ['apiKey', 'Clé API']], true, ['subscriptionKey' => ['', ''], 'apiUser' => ['', ''], 'apiKey' => ['', '']]],
            ['moov', 'Moov Money', 'Mobile Money', '#0066b3', 'Mv', [['merchantId', 'ID marchand'], ['apiKey', 'Clé API']], false, []],
            ['card', 'Carte bancaire', 'Carte', '#1d1d1b', 'CB', [['publicKey', 'Clé publique'], ['secretKey', 'Clé secrète']], true, ['publicKey' => ['pk_test_', 'pk_live_'], 'secretKey' => ['sk_test_', 'sk_live_']]],
            ['cinetpay', 'CinetPay', 'Agrégateur', '#2e7d32', 'CP', [['siteId', 'Site ID'], ['apiKey', 'Clé API'], ['secretKey', 'Clé secrète']], false, []],
            ['paydunya', 'PayDunya', 'Agrégateur', '#5b2d90', 'PD', [['masterKey', 'Master key'], ['privateKey', 'Clé privée'], ['token', 'Token']], false, []],
        ];
        foreach ($providers as [$code, $name, $kind, $color, $logo, $fields, $active, $keys]) {
            $pm = PaymentMethod::create(['code' => $code, 'name' => $name, 'kind' => $kind, 'color' => $color, 'logo' => $logo, 'fields' => $fields,
                'is_active_test' => $active, 'is_active_live' => $active, 'test_ok_test' => $active ?: null, 'tested_test_at' => $active ? now()->subDays(9) : null,
                'test_ok_live' => $active ?: null, 'tested_live_at' => $active ? now()->subDays(9) : null]);
            foreach ($keys as $keyName => [$pTest, $pLive]) {
                foreach (['test' => $pTest, 'live' => $pLive] as $env => $prefix) {
                    $value = $prefix.'DEMO'.Str::random(20); // valeurs factices de démonstration
                    $pm->credentials()->create(['environment' => $env, 'key_name' => $keyName, 'value' => Crypt::encryptString($value), 'prefix' => $prefix, 'last4' => substr($value, -4)]);
                }
            }
            if ($code === 'wave') {
                $wave = $pm;
            }
        }
        $om = PaymentMethod::where('code', 'om')->first();
        foreach ([[$awa, $ago(21), 10000, $wave, 'WV-88213'], [$yao, $ago(14), 5000, $wave, 'WV-80044'], [$awa, $ago(35), 10000, $om, 'OM-41120'], [$yao, $ago(35), 5000, $om, 'OM-41121']] as [$child, $d, $amount, $m, $ref]) {
            Payment::create(['payer_id' => $ibrahim->id, 'beneficiary_id' => $child->id, 'payment_method_id' => $m->id, 'amount' => $amount, 'status' => 'success', 'environment' => 'test', 'provider_reference' => $ref])
                ->forceFill(['created_at' => Carbon::parse($d)->setTime(9, 0)])->save();
        }

        // ---------- Avis ----------
        $monServed = Reservation::where('menu_id', $mon->id)->where('status', 'served')->whereNotNull('user_id')->orderBy('id')->get();
        $texts = [
            [5, 'Excellent alloco, le poulet était bien braisé.'], [4, 'Bon repas, un peu trop pimenté.'], [4, 'La salade de fruits était parfaite.'],
            [3, 'Portions un peu petites aujourd’hui.'], [5, 'Rien à dire, très bon.'], [2, 'Le riz était froid au deuxième créneau.'],
            [4, 'Bon menu, merci à l’équipe.'], [1, 'Ce menu est nul, vous êtes des idiots en cuisine.'],
            [5, 'Gagnez 50 000 FCFA en 24h, cliquez ici : bit.ly/argent-facile'], [3, 'Quelqu’un veut acheter mon téléphone ? Écrivez-moi.'],
        ];
        $mod = app(\App\Services\ModerationService::class);
        foreach ($texts as $i => [$stars, $comment]) {
            $r = $monServed[$i * 9] ?? $monServed[$i];
            $review = new Review(['user_id' => $r->user_id, 'menu_id' => $mon->id, 'reservation_id' => $r->id, 'rating' => $stars, 'comment' => $comment, 'status' => 'visible']);
            $mod->apply($review)->save();
        }
        Review::create(['user_id' => $yao->id, 'menu_id' => $mon->id, 'reservation_id' => $yaoMon, 'rating' => 4, 'comment' => 'J’ai bien aimé le riz gras.', 'tags' => ['Délicieux'], 'status' => 'visible']);
        $p2 = $planL['plat'][1] ?? $awaMain;
        foreach ([[$awaMain, 5, 'Très bon, portion généreuse.'], [$awaMain, 4, ''], [$p2, 3, 'Un peu fade, il manquait du sel.'], [$planL['dessert'][0], 5, 'Très frais.'], [$planL['entree'][0], 4, '']] as $k => [$dk, $stars, $comment]) {
            $r = $monServed->first(fn ($x, $i) => $i > 40 + $k * 7 && $x->items()->where('dish_id', $this->dish[$dk])->exists()) ?? $monServed[$k];
            Review::create(['user_id' => $r->user_id, 'menu_id' => $mon->id, 'dish_id' => $this->dish[$dk], 'reservation_id' => $r->id, 'rating' => $stars, 'comment' => $comment ?: null, 'status' => 'visible']);
        }
        // Notes historiques sur les plats (semaines passées) pour alimenter moyennes et classement
        $baseRatings = ['d4' => 4.7, 'd5' => 4.5, 'd6' => 4.2, 'd7' => 4.6, 'd8' => 3.6, 'd9' => 3.9, 'd10' => 3.4, 'd11' => 4.4, 'd1' => 4.0, 'd2' => 4.3, 'd3' => 3.5, 'd12' => 4.6, 'd13' => 4.1, 'd14' => 4.5, 'd15' => 4.2, 'd16' => 4.8, 'd17' => 4.4, 'd18' => 4.0];
        $past = Reservation::with('items')->where('status', 'served')->whereHas('menu', fn ($q) => $q->whereDate('date', '<', $monday))->inRandomOrder()->limit(260)->get();
        $rows = [];
        foreach ($past as $r) {
            $it = $r->items->first();
            $key = array_search($it->dish_id, $this->dish);
            $base = $baseRatings[$key] ?? 4;
            $rating = max(1, min(5, (int) round($base + (mt_rand(-10, 10) / 10))));
            $rows[] = ['user_id' => $r->user_id, 'menu_id' => $r->menu_id, 'dish_id' => $it->dish_id, 'reservation_id' => $r->id, 'rating' => $rating, 'status' => 'visible', 'approved' => false, 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('reviews')->insert($rows);

        // ---------- Notifications ----------
        $note = function ($u, $at, $icon, $title, $body, $url, $read = false, $kind = 'info') {
            AppNotification::create(['user_id' => $u->id, 'icon' => $icon, 'title' => $title, 'body' => $body, 'url' => $url, 'kind' => $kind, 'read_at' => $read ? now() : null])
                ->forceFill(['created_at' => $at])->save();
        };
        $lastPast = Carbon::parse($lastPastDate);
        $note($awa, now()->subMinutes(90), 'clock', 'Pensez à réserver', 'Les réservations ferment à 10h00 le jour même.', '/menu');
        $note($awa, $lastPast->copy()->setTime(13, 10), 'star', 'Comment était votre repas ?', 'Notez '.$dishName($awaMain).' ('.$mon->label('l j').').', '/avis/'.$mon->id, false, 'review');
        $note($awa, $monday->copy()->subDays(2)->setTime(18, 0), 'calendar', 'Menu de la semaine publié', 'Le menu de la semaine est en ligne.', '/menu', true, 'menu');
        $note($ibrahim, now()->subMinutes(60), 'alert', 'Solde bas — Yao', 'Le solde de Yao est de 800 FCFA (seuil : 1 000 FCFA).', '/parent/enfants/'.$yao->id.'/recharger', false, 'low_balance');
        $note($ibrahim, now()->subMinutes(70), 'utensils', 'Repas réservé — Yao', $dishName($yaoMain).', '.$tue->label('l j').', créneau 11h30.', '/parent/enfants/'.$yao->id);
        $note($ibrahim, $lastPast->copy()->setTime(12, 4), 'check-circle', 'Repas servi — Awa', $dishName($awaMain).' servi à 12h04.', '/parent/enfants/'.$awa->id, true, 'served');

        // ---------- Stocks : mouvements ----------
        foreach ([['i1', 'entree', 100, null, 'M. Diallo'], ['i2', 'sortie', 32, 'Production', 'Cuisine'], ['i12', 'perte', 4, 'Fruits abîmés', 'M. Diallo'], ['i4', 'entree', 40, null, 'M. Diallo']] as $k => [$ik, $type, $q, $reason, $who]) {
            StockMovement::create(['ingredient_id' => $this->ing[$ik], 'type' => $type, 'quantity' => $q, 'reason' => $reason, 'author' => $who])
                ->forceFill(['created_at' => $lastPast->copy()->subDays($k === 3 ? 3 : 0)->setTime(9, 0)])->save();
        }

        // ---------- Super admin : demandes, corbeille, audit ----------
        $daysAgo = fn (int $d, string $time) => $today->copy()->subDays($d)->setTimeFromTimeString($time);
        DeletionRequest::create(['user_id' => $others[7]->id, 'reason' => 'Je quitte l’établissement à la fin du mois.', 'status' => 'pending'])->forceFill(['created_at' => $daysAgo(4, '10:00')])->save();
        DeletionRequest::create(['user_id' => $others[15]->id, 'reason' => null, 'status' => 'pending'])->forceFill(['created_at' => $daysAgo(1, '10:00')])->save();
        $others[30]->forceFill(['status' => 'trashed', 'trashed_at' => $daysAgo(29, '16:05'), 'trash_reason' => 'Départ de l’établissement (demande de l’utilisateur)', 'trashed_by' => 'Kader Touré'])->save();
        $others[31]->forceFill(['status' => 'trashed', 'trashed_at' => $daysAgo(6, '08:47'), 'trash_reason' => 'Compte en double', 'trashed_by' => 'Kader Touré'])->save();
        foreach ([
            [$daysAgo(29, '16:05'), 'trash', $others[30]->name, 'Motif : Départ de l’établissement (demande de l’utilisateur)'],
            [$daysAgo(12, '09:12'), 'api_keys', 'Wave (Production)', 'Clé secrète remplacée'],
            [$daysAgo(10, '11:30'), 'role_change', 'Hervé Konan', 'Rôle : Utilisateur → Administrateur'],
            [$daysAgo(6, '08:47'), 'trash', $others[31]->name, 'Motif : Compte en double'],
        ] as [$at, $action, $target, $detail]) {
            AuditLog::create(['actor_id' => $kader->id, 'actor_name' => $kader->name, 'actor_role' => 'super_admin', 'action' => $action, 'target_label' => $target, 'details' => $detail, 'ip' => '127.0.0.1', 'created_at' => $at]);
        }
    }
}
