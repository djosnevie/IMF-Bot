<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentAlert extends Model
{
    protected $fillable = [
        'type', 'contact_id', 'agent_id', 'message', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /** Contact à l'origine de l'alerte. */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** Agent destinataire de l'alerte. */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** Vérifie si l'alerte a été lue. */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
