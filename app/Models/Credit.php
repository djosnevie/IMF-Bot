<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $fillable = [
        'reference',
        'name',
        'display_name',
        'amount_range',
        'duration_range',
        'file_fee',
        'disbursement_fee',
        'interest_rate',
        'penalty',
        'repayment_mode',
        'guarantee',
        'is_active',
        'display_order',
    ];
}
