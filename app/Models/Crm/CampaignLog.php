<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLog extends Model
{
    protected $fillable = [
        'campaign_id', 'contact_id', 'message_sent', 'sent_at', 'replied_at',
    ];

    protected $casts = [
        'sent_at'    => 'datetime',
        'replied_at' => 'datetime',
    ];

    /** Campagne parente. */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** Contact ciblé par cet envoi. */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
