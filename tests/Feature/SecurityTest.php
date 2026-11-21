<?php

namespace Tests\Feature;

use App\Http\Controllers\User\ProfileController;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Durcissement : liaison parent, mots de passe provisoires, rôle comptoir, en-têtes, audit, seeder. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-24 09:40');
        $this->seed();
    }

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_code_de_secours_long_et_essais_limites(): void
    {
        $code = ProfileController::backupCode('Awa');
        $this->assertMatchesRegularExpression('/^AWA-[A-HJ-NP-Z2-9]{6}$/', $code);

        $this->actingAs($this->user('i.kone@gmail.com'));
        for ($i = 0; $i < 5; $i++) {
            $this->post('/parent/lier/verifier', ['code' => 'XXX-ZZZZZ'.$i])->assertSessionHasErrors('code');
        }
        // 6e essai dans la minute : bloqué
        $this->post('/parent/lier/verifier', ['code' => 'INE-7KQ2MX'])->assertStatus(429);
    }

    public function test_compte_cree_avec_mot_de_passe_provisoire_a_changer(): void
    {
        $this->actingAs($this->user('superadmin@cantine-vatel.ci'));
        $resp = $this->post('/super/admins', ['first_name' => 'Nina', 'last_name' => 'Bamba', 'email' => 'n.bamba@cantine-vatel.ci', 'role' => 'admin']);
        $temp = $resp->getSession()->get('new_admin_password')['password'];
        $this->assertGreaterThanOrEqual(15, strlen($temp));
        $this->get('/super/admins')->assertSee($temp);       // affiché une fois…
        $this->get('/super/admins')->assertDontSee($temp);   // …puis plus jamais

        auth()->logout();
        $this->post('/connexion', ['email' => 'n.bamba@cantine-vatel.ci', 'password' => $temp]);
        $this->get('/admin')->assertRedirect('/mot-de-passe/nouveau');
        $this->put('/mot-de-passe/nouveau', ['current_password' => $temp, 'password' => $temp, 'password_confirmation' => $temp])->assertSessionHasErrors('password');
        $this->put('/mot-de-passe/nouveau', ['current_password' => $temp, 'password' => 'Nouveau2026x', 'password_confirmation' => 'Nouveau2026x'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
        $this->assertFalse($this->user('n.bamba@cantine-vatel.ci')->must_change_password);
    }

    public function test_reinitialisation_du_mot_de_passe_par_le_super_admin(): void
    {
        $admin = $this->user('gestion@cantine-vatel.ci');
        $this->actingAs($this->user('superadmin@cantine-vatel.ci'))->post("/super/admins/{$admin->id}/mot-de-passe")->assertSessionHas('new_admin_password');
        $this->assertTrue($admin->fresh()->must_change_password);
        $this->actingAs($admin->fresh())->get('/admin/menus')->assertRedirect('/mot-de-passe/nouveau');
    }

    public function test_agent_de_comptoir_limite_au_comptoir(): void
    {
        $this->post('/connexion', ['email' => 'comptoir1@cantine-vatel.ci', 'password' => 'password'])->assertRedirect('/admin/comptoir');
        $this->get('/admin/comptoir')->assertOk();
        foreach (['/admin', '/admin/menus', '/admin/plats', '/admin/utilisateurs', '/admin/parametres', '/super/admins'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/comptoir', ['code' => '204863'])->assertRedirect('/admin/comptoir');
        // Un administrateur garde l'accès au comptoir
        $this->actingAs($this->user('gestion@cantine-vatel.ci'))->get('/admin/comptoir')->assertOk();
    }

    public function test_en_tetes_de_securite(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("default-src 'self'", $this->get('/connexion')->headers->get('Content-Security-Policy'));
    }

    public function test_journal_d_audit_verrouille_en_base(): void
    {
        $this->assertGreaterThan(0, AuditLog::count());
        $blockedUpdate = false;
        try {
            DB::table('audit_logs')->update(['details' => 'falsifié']);
        } catch (\Throwable) {
            $blockedUpdate = true;
        }
        $blockedDelete = false;
        try {
            DB::table('audit_logs')->delete();
        } catch (\Throwable) {
            $blockedDelete = true;
        }
        $this->assertTrue($blockedUpdate && $blockedDelete);
        $this->assertSame(0, AuditLog::where('details', 'falsifié')->count());
    }

    public function test_pas_de_donnees_de_demo_en_production(): void
    {
        $before = User::count();
        $this->app['env'] = 'production';
        try {
            (new DatabaseSeeder)->run();
        } finally {
            $this->app['env'] = 'testing';
        }
        $this->assertSame($before, User::count());
    }
}
