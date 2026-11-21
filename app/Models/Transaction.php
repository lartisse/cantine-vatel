<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'];

    public const TYPES = ['topup' => 'Recharge', 'meal' => 'Repas', 'refund' => 'Remboursement', 'adjustment' => 'Ajustement'];

    public const ICONS = ['topup' => 'arrow-up', 'meal' => 'utensils', 'refund' => 'undo', 'adjustment' => 'wrench'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
