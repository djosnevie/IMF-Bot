<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
