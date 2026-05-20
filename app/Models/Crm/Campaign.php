<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'message_template', 'targeting_criteria',
        'scheduled_at', 'status', 'recipients_count', 'response_rate', 'created_by',
    ];

    protected $casts = [
        'targeting_criteria' => 'array',
        'scheduled_at'       => 'datetime',
        'response_rate'      => 'float',
    ];

    /** Logs d'envoi individuel de cette campagne. */
    public function logs(): HasMany
    {
        return $this->hasMany(CampaignLog::class);
    }

    /** Utilisateur ayant créé la campagne. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
