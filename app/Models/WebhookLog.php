<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'platform',
        'payload',
        'response',
        'status',
        'error_message',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];

    /**
     * Scope for failed webhooks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for successful webhooks
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }
}
