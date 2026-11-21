<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\PaymentCredential;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Services\AccountService;
use App\Services\ReservationService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Règles critiques : rôles, heure limite, capacité, allergènes, corbeille, chiffrement des clés, images. */
class CantineRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Date fixe pour des tests reproductibles (l'application, elle, utilise l'heure réelle)
        Carbon::setTestNow('2026-11-24 09:40');
        $this->seed();
        Settings::flush();
    }

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    protected function today(): Menu
    {
        return Menu::whereDate('date', '2026-11-24')->firstOrFail();
    }

    public function test_les_pages_publiques_et_la_connexion_fonctionnent(): void
    {
        $this->get('/')->assertOk()->assertSee('mangez sans attendre', false);
        $this->post('/connexion', ['email' => 'awa.kone@vatel.ci', 'password' => 'password'])->assertRedirect('/accueil');
        $this->get('/accueil')->assertOk()->assertSee('Bonjour, Awa');
    }

    public function test_inscription_eleve(): void
    {
        $this->post('/inscription', ['type' => 'eleve', 'first_name' => 'Test', 'last_name' => 'Élève', 'email' => 'test@vatel.ci', 'password' => 'secret123', 'class_name' => '3e A', 'allergies' => ['gluten']])
            ->assertRedirect('/accueil');
        $u = $this->user('test@vatel.ci');
        $this->assertSame('user', $u->role);
        $this->assertSame(['gluten'], $u->allergies);
    }

    public function test_un_admin_recoit_403_sur_le_super_admin(): void
    {
        $this->actingAs($this->user('gestion@cantine-vatel.ci'));
        foreach (['/super/admins', '/super/comptes', '/super/corbeille', '/super/demandes', '/super/paiements', '/super/audit'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/admin')->assertOk();
    }

    public function test_un_eleve_ne_peut_pas_ouvrir_l_administration(): void
    {
        $this->actingAs($this->user('awa.kone@vatel.ci'));
        $this->get('/admin')->assertForbidden();
        $this->get('/parent')->assertForbidden();
    }

    public function test_super_admin_a_acces_a_l_administration(): void
    {
        $this->actingAs($this->user('superadmin@cantine-vatel.ci'))->get('/admin/commandes')->assertOk();
    }

    public function test_reservation_debite_le_solde_et_genere_un_code(): void
    {
        $jm = $this->user('jm.aka@vatel.ci');
        $before = $jm->balance;
        $r = app(ReservationService::class)->book($jm, $this->today(), [4 => 1, 16 => 2], '12:00');
        $this->assertSame(1900, $r->total);
        $this->assertSame($before - 1900, $jm->fresh()->balance);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $r->code);
    }

    public function test_heure_limite_bloque_la_reservation_et_l_annulation(): void
    {
        $jm = $this->user('jm.aka@vatel.ci');
        $r = app(ReservationService::class)->book($jm, $this->today(), [4 => 1], '12:00');
        Carbon::setTestNow('2026-11-24 10:05');
        $this->expectException(ValidationException::class);
        app(ReservationService::class)->cancel($r->fresh('menu', 'items.dish', 'user'));
    }

    public function test_reservation_refusee_apres_l_heure_limite(): void
    {
        Carbon::setTestNow('2026-11-24 10:01');
        $this->expectException(ValidationException::class);
        app(ReservationService::class)->book($this->user('jm.aka@vatel.ci'), $this->today(), [4 => 1], '12:00');
    }

    public function test_annulation_rembourse_immediatement(): void
    {
        $jm = $this->user('jm.aka@vatel.ci');
        $before = $jm->balance;
        $r = app(ReservationService::class)->book($jm, $this->today(), [4 => 1], '12:00');
        app(ReservationService::class)->cancel($r->fresh('menu', 'items.dish', 'user'));
        $this->assertSame($before, $jm->fresh()->balance);
        $this->assertSame('cancelled', $r->fresh()->status);
    }

    public function test_capacite_du_creneau(): void
    {
        Settings::set('slots', ['11:30' => 0, '12:00' => 50]);
        $this->expectException(ValidationException::class);
        app(ReservationService::class)->book($this->user('jm.aka@vatel.ci'), $this->today(), [4 => 1], '11:30');
    }

    public function test_allergene_exige_une_confirmation_puis_alerte_l_administration(): void
    {
        $awa = $this->user('awa.kone@vatel.ci'); // allergique à l'arachide
        $service = app(ReservationService::class);
        try {
            $service->book($awa, $this->today(), [5 => 1], '12:00');
            $this->fail('La réservation aurait dû demander une confirmation.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('allergy', $e->errors());
        }
        $admin = $this->user('gestion@cantine-vatel.ci');
        $before = $admin->appNotifications()->where('kind', 'allergy')->count();
        $r = $service->book($awa, $this->today(), [5 => 1], '12:00', true);
        $this->assertNotEmpty($r->allergy_conflict);
        $this->assertSame($before + 1, $admin->appNotifications()->where('kind', 'allergy')->count());
    }

    public function test_comptoir_valide_puis_signale_deja_servi(): void
    {
        $r = Reservation::where('code', '204863')->firstOrFail(); // Yao, mardi, 11h30
        $service = app(ReservationService::class);
        $this->assertSame('ok', $service->scan('204863')['state']);
        $this->assertSame('warn', $service->scan('204863')['state']);
        $this->assertSame('err', $service->scan('000000')['state']);
        $this->assertSame('served', $r->fresh()->status);
    }

    public function test_corbeille_deconnecte_puis_suppression_anonymise(): void
    {
        $awa = $this->user('awa.kone@vatel.ci');
        $this->actingAs($awa)->get('/accueil')->assertOk();
        $this->actingAs($this->user('superadmin@cantine-vatel.ci'));
        app(AccountService::class)->trash($awa, 'Test');
        $this->actingAs($awa->fresh())->get('/accueil')->assertRedirect('/connexion');
        $this->post('/connexion', ['email' => 'awa.kone@vatel.ci', 'password' => 'password'])->assertSessionHasErrors('email');

        $resIds = Reservation::where('user_id', $awa->id)->pluck('id');
        app(AccountService::class)->forceDelete($awa->fresh());
        $this->assertDatabaseMissing('users', ['email' => 'awa.kone@vatel.ci']);
        $this->assertTrue(Reservation::whereIn('id', $resIds)->whereNull('user_id')->count() === $resIds->count());
        $this->assertTrue(AuditLog::where('action', 'delete')->exists());
    }

    public function test_suppression_definitive_impossible_hors_corbeille(): void
    {
        $this->expectException(ValidationException::class);
        app(AccountService::class)->forceDelete($this->user('jm.aka@vatel.ci'));
    }

    public function test_cles_de_paiement_chiffrees_et_masquees(): void
    {
        $super = $this->user('superadmin@cantine-vatel.ci');
        $this->actingAs($super)->get('/super/paiements')->assertRedirect('/confirmer-mot-de-passe');
        $this->withSession(['auth.password_confirmed_at' => now()->unix()]);
        $m = PaymentMethod::where('code', 'cinetpay')->firstOrFail();
        $this->put("/super/paiements/{$m->id}/cles", ['env' => 'test', 'keys' => ['siteId' => 'site_12345678', 'apiKey' => 'cp_api_SECRET9z1Q', 'secretKey' => 'cp_sec_SECRETxx77']])->assertRedirect();
        $c = PaymentCredential::where('payment_method_id', $m->id)->where('key_name', 'apiKey')->firstOrFail();
        $this->assertNotSame('cp_api_SECRET9z1Q', $c->value);
        $this->assertSame('cp_api_SECRET9z1Q', Crypt::decryptString($c->value));
        $this->assertSame('9z1Q', $c->last4);
        $this->get('/super/paiements?env=test')->assertOk()->assertDontSee('cp_api_SECRET9z1Q')->assertSee('cp_api_••••••••9z1Q', false);
    }

    public function test_activation_refusee_sans_test_reussi(): void
    {
        $this->actingAs($this->user('superadmin@cantine-vatel.ci'))->withSession(['auth.password_confirmed_at' => now()->unix()]);
        $m = PaymentMethod::where('code', 'moov')->firstOrFail();
        $this->post("/super/paiements/{$m->id}/activer", ['env' => 'test'])->assertSessionHas('error');
        $this->assertFalse($m->fresh()->is_active_test);
    }

    public function test_moderation_signale_les_mots_interdits(): void
    {
        $this->assertGreaterThanOrEqual(2, Review::where('status', 'flagged')->count());
    }

    public function test_upload_photo_refuse_un_fichier_trop_lourd_et_compresse_en_webp(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('awa.kone@vatel.ci'));
        $this->post('/profil/photo', ['photo' => UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg')])->assertSessionHasErrors(['photo' => 'Image trop lourde (5 Mo maximum)']);
        $this->post('/profil/photo', ['photo' => UploadedFile::fake()->image('ok.png', 800, 600)])->assertSessionHasNoErrors();
        $path = $this->user('awa.kone@vatel.ci')->avatar_path;
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_liaison_parent_par_code_de_secours(): void
    {
        $this->actingAs($this->user('i.kone@gmail.com'));
        $this->post('/parent/lier/verifier', ['code' => 'INE-5Q1'])->assertRedirect('/parent/lier');
        $this->post('/parent/lier', ['code' => 'INE-5Q1'])->assertRedirect('/parent');
        $this->assertTrue($this->user('i.kone@gmail.com')->children()->where('email', 'ines.kone@vatel.ci')->exists());
        // usage unique
        $this->post('/parent/lier/verifier', ['code' => 'INE-5Q1'])->assertSessionHasErrors('code');
    }

    public function test_un_utilisateur_ne_voit_pas_la_reservation_d_un_autre(): void
    {
        $yaoRes = Reservation::where('code', '204863')->firstOrFail();
        $this->actingAs($this->user('awa.kone@vatel.ci'))->get('/reservations/'.$yaoRes->id)->assertForbidden();
        $this->actingAs($this->user('yao.kone@vatel.ci'))->get('/reservations/'.$yaoRes->id)->assertOk()->assertSee('204863');
    }

    public function test_toutes_les_pages_principales_repondent(): void
    {
        $pages = [
            'awa.kone@vatel.ci' => ['/accueil', '/menu', '/plats/4', '/reservations', '/portefeuille', '/portefeuille/recharger', '/profil', '/notifications', '/profil/qr-parent', '/profil/suppression'],
            'i.kone@gmail.com' => ['/parent', '/parent/paiements', '/parent/alertes', '/parent/lier'],
            'gestion@cantine-vatel.ci' => ['/admin', '/admin/menus', '/admin/commandes', '/admin/comptoir', '/admin/alertes', '/admin/plats', '/admin/ingredients', '/admin/avis', '/admin/utilisateurs', '/admin/statistiques/classes', '/admin/rapports', '/admin/parametres'],
            'superadmin@cantine-vatel.ci' => ['/super/admins', '/super/comptes', '/super/corbeille', '/super/demandes', '/super/audit'],
        ];
        foreach ($pages as $email => $urls) {
            $this->actingAs($this->user($email));
            foreach ($urls as $url) {
                $this->get($url)->assertOk();
            }
        }
    }
}
