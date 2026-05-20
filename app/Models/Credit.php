<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
