<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'user_identifier',
        'platform',
        'status',
        'metadata',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
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

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function getOrCreate(string $userIdentifier, string $platform = 'whatsapp')
    {
        return static::firstOrCreate(
            [
                'user_identifier' => $userIdentifier,
                'platform' => $platform,
                'status' => 'active',
            ],
            [
                'last_message_at' => now(),
            ]
        );
    }
}
