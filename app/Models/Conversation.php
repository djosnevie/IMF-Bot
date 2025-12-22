<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    /**
     * Get all messages for this conversation
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Scope for active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get or create a conversation for a user
     */
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
