<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** API REST v1 et gestion des clés d'API par le super administrateur. */
class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-24 09:40');
        $this->seed();
    }

    protected function key(array $scopes, array $extra = []): string
    {
        [, $plain] = ApiKey::generate(array_merge(['name' => 'Test', 'scopes' => $scopes, 'rate_limit' => 60, 'is_active' => true], $extra));

        return $plain;
    }

    protected function api(string $method, string $uri, ?string $key, array $data = [])
    {
        $this->flushHeaders();

        return $this->withHeaders($key ? ['Authorization' => 'Bearer '.$key] : [])->json($method, '/api/v1'.$uri, $data);
    }

    public function test_cle_obligatoire_et_valide(): void
    {
        $this->api('GET', '/ping', null)->assertStatus(401)->assertJson(['error' => 'unauthenticated']);
        $this->api('GET', '/ping', 'cvk_inconnue')->assertStatus(401);
        $key = $this->key(['menus:read']);
        $this->api('GET', '/ping', $key)->assertOk()->assertJson(['ok' => true, 'key' => ['scopes' => ['menus:read']]]);
        $this->withHeaders(['X-Api-Key' => $key])->getJson('/api/v1/ping')->assertOk();
    }

    public function test_la_cle_n_est_jamais_stockee_en_clair(): void
    {
        $plain = $this->key(['menus:read']);
        $k = ApiKey::first();
        $this->assertNotSame($plain, $k->key_hash);
        $this->assertSame(hash('sha256', $plain), $k->key_hash);
        $this->assertDatabaseMissing('api_keys', ['key_hash' => $plain]);
    }

    public function test_permission_manquante_renvoie_403(): void
    {
        $key = $this->key(['menus:read']);
        $this->api('GET', '/menus', $key)->assertOk()->assertJsonStructure(['from', 'to', 'data' => [['date', 'slots', 'dishes']]]);
        $this->api('GET', '/stocks', $key)->assertStatus(403)->assertJson(['error' => 'forbidden']);
    }

    public function test_cle_revoquee_suspendue_ou_expiree_refusee(): void
    {
        $revoked = $this->key(['menus:read'], ['revoked_at' => now()]);
        $suspended = $this->key(['menus:read'], ['is_active' => false]);
        $expired = $this->key(['menus:read'], ['expires_at' => now()->subDay()]);
        foreach ([$revoked, $suspended, $expired] as $k) {
            $this->api('GET', '/ping', $k)->assertStatus(401);
        }
    }

    public function test_limite_de_requetes(): void
    {
        $key = $this->key(['menus:read'], ['rate_limit' => 2]);
        $this->api('GET', '/ping', $key)->assertOk()->assertHeader('X-RateLimit-Limit', '2');
        $this->api('GET', '/ping', $key)->assertOk();
        $this->api('GET', '/ping', $key)->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_lectures_principales(): void
    {
        $key = $this->key(['menus:read', 'reservations:read', 'orders:read', 'stats:read', 'stocks:read', 'users:read']);
        $this->api('GET', '/menus/2026-11-24', $key)->assertOk()->assertJsonPath('data.date', '2026-11-24');
        $this->api('GET', '/plats?categorie=dessert', $key)->assertOk()->assertJsonPath('data.0.category', 'dessert');
        $res = $this->api('GET', '/reservations?date=2026-11-24', $key)->assertOk()->assertJsonStructure(['data' => [['id', 'slot', 'status', 'items']], 'meta' => ['total']]);
        $this->assertArrayNotHasKey('code', $res->json('data.0'));
        $this->api('GET', '/commandes?date=2026-11-24', $key)->assertOk()->assertJsonStructure(['totals' => ['reserved', 'to_serve'], 'dishes' => [['name', 'to_prepare', 'by_slot']]]);
        $this->api('GET', '/statistiques?jours=7', $key)->assertOk()->assertJsonStructure(['totals' => ['reserved', 'pickup_rate'], 'daily']);
        $this->api('GET', '/stocks', $key)->assertOk()->assertJsonStructure(['data' => [['name', 'stock', 'status']]]);
        $this->api('GET', '/utilisateurs?type=eleve', $key)->assertOk()->assertJsonPath('data.0.type', 'eleve');
        $this->api('GET', '/menus/2026-12-25', $key)->assertNotFound();
        $this->api('GET', '/reservations?date=24-11-2026', $key)->assertStatus(422);
    }

    public function test_service_au_comptoir_via_api(): void
    {
        $key = $this->key(['orders:write']);
        $this->api('POST', '/comptoir/servir', $key, ['code' => '204863'])->assertOk()->assertJson(['state' => 'ok']);
        $this->api('POST', '/comptoir/servir', $key, ['code' => '204863'])->assertStatus(409)->assertJson(['state' => 'warn']);
        $this->api('POST', '/comptoir/servir', $key, ['code' => '000000'])->assertNotFound();
        $this->assertSame('served', Reservation::where('code', '204863')->value('status'));
    }

    public function test_recharge_via_api_idempotente(): void
    {
        $key = $this->key(['wallet:write']);
        $awa = User::where('email', 'awa.kone@vatel.ci')->first();
        $before = $awa->balance;
        $this->api('POST', "/utilisateurs/{$awa->id}/recharges", $key, ['amount' => 5000, 'reference' => 'WAVE-1'])->assertCreated()->assertJson(['duplicate' => false, 'balance' => $before + 5000]);
        $this->api('POST', "/utilisateurs/{$awa->id}/recharges", $key, ['amount' => 5000, 'reference' => 'WAVE-1'])->assertOk()->assertJson(['duplicate' => true]);
        $this->assertSame($before + 5000, $awa->fresh()->balance);
        $this->api('POST', "/utilisateurs/{$awa->id}/recharges", $key, ['amount' => 10])->assertStatus(422);
        $this->assertTrue(AuditLog::where('action', 'api_topup')->exists());
    }

    public function test_gestion_des_cles_par_le_super_admin(): void
    {
        $admin = User::where('email', 'gestion@cantine-vatel.ci')->first();
        $super = User::where('email', 'superadmin@cantine-vatel.ci')->first();
        $this->actingAs($admin)->get('/super/api')->assertForbidden();

        $this->actingAs($super);
        $this->post('/super/api', ['name' => 'ERP', 'scopes' => ['stats:read'], 'rate_limit' => 30, 'current_password' => 'faux'])->assertSessionHasErrors('current_password');
        $resp = $this->post('/super/api', ['name' => 'ERP', 'scopes' => ['stats:read'], 'rate_limit' => 30, 'current_password' => 'password'])->assertRedirect('/super/api');
        $plain = $resp->getSession()->get('new_api_key')['key'];
        $this->assertStringStartsWith('cvk_', $plain);
        $key = ApiKey::where('name', 'ERP')->first();

        // Affichée une seule fois : sur la page qui suit la création, puis plus jamais
        $this->get('/super/api')->assertOk()->assertSee($plain);
        $this->get('/super/api')->assertOk()->assertDontSee($plain)->assertSee(substr($plain, 0, 10).'••••••••'.substr($plain, -4), false);
        $this->api('GET', '/statistiques', $plain)->assertOk();

        // Renouveler : l'ancienne clé est révoquée, la nouvelle fonctionne
        $new = $this->actingAs($super)->post("/super/api/{$key->id}/renouveler", ['current_password' => 'password'])->getSession()->get('new_api_key')['key'];
        $this->api('GET', '/statistiques', $plain)->assertStatus(401);
        $this->api('GET', '/statistiques', $new)->assertOk();

        // Suspendre puis révoquer et supprimer
        $current = ApiKey::whereNull('revoked_at')->where('name', 'ERP')->first();
        $this->actingAs($super)->post("/super/api/{$current->id}/statut");
        $this->api('GET', '/statistiques', $new)->assertStatus(401);
        $this->actingAs($super)->post("/super/api/{$current->id}/revoquer");
        $this->actingAs($super)->delete("/super/api/{$current->id}")->assertRedirect();
        $this->assertDatabaseMissing('api_keys', ['id' => $current->id]);
        $this->assertTrue(AuditLog::where('action', 'api_key_create')->exists());
        $this->assertTrue(AuditLog::where('action', 'api_key_rotate')->exists());
    }
}
