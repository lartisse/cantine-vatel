<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCredential extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['value'];

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /** Affichage masqué : sk_live_••••••••4F2a (la valeur chiffrée n'est jamais renvoyée au navigateur). */
    public function masked(): string
    {
        return ($this->prefix ?? '').'••••••••'.$this->last4;
    }
}
