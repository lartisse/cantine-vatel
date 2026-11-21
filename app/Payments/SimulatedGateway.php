<?php

namespace App\Payments;

use App\Models\PaymentMethod;
use Illuminate\Support\Str;

/**
 * Pilote simulé (environnement Test) : aucun débit réel.
 * Les pilotes réels (WaveGateway, OrangeMoneyGateway, CinetPayGateway…) implémenteront la même interface
 * en lisant leurs clés déchiffrées via Crypt::decryptString().
 */
class SimulatedGateway implements PaymentGateway
{
    public function __construct(protected PaymentMethod $method, protected string $environment) {}

    public static function make(PaymentMethod $method, string $environment): self
    {
        return new self($method, $environment);
    }

    public function charge(int $amount, string $phone, string $label): array
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->method->name), 0, 2));

        return ['ok' => $amount > 0, 'reference' => $prefix.'-'.random_int(10000, 99999), 'message' => 'Paiement simulé accepté'];
    }

    public function testConnection(): array
    {
        $ok = $this->method->hasAllKeys($this->environment);

        return ['ok' => $ok, 'message' => $ok ? 'Connexion réussie ('.Str::upper($this->environment).')' : 'Clés manquantes'];
    }
}
