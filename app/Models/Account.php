<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'reference',
        'account_type',
        'display_name',
        'category',
        'currency',
        'interest_rate',
        'initial_deposit',
        'maintenance_fee',
        'statement_fee',
        'deposit_rule',
        'withdrawal_rule',
        'withdrawal_fee',
        'duration',
        'min_age',
        'max_age',
        'is_active',
        'display_order',
    ];
}
