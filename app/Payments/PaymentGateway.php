<?php

namespace App\Payments;

use App\Models\PaymentMethod;

/** Un pilote par moyen de paiement (Wave, Orange Money, MTN MoMo, Moov, carte, CinetPay, PayDunya). */
interface PaymentGateway
{
    /** @return array{ok: bool, reference: string, message: string} */
    public function charge(int $amount, string $phone, string $label): array;

    /** @return array{ok: bool, message: string} */
    public function testConnection(): array;

    public static function make(PaymentMethod $method, string $environment): self;
}
