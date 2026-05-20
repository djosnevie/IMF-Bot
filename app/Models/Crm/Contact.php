<?php

namespace App\Models\Crm;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Contact extends Model
{
    protected $fillable = [
        'uuid',
        'whatsapp_number',
        'display_name',
        'detected_language',
        'commercial_status',
        'interest_score',
        'first_contact_at',
        'last_contact_at',
        'assigned_to',
        'metadata',
    ];

    protected $casts = [
        'metadata'        => 'array',
        'first_contact_at' => 'datetime',
        'last_contact_at'  => 'datetime',
        'interest_score'   => 'integer',
    ];

    protected static function boot(): void
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

    // ─── Relations ────────────────────────────────────────────────────────────

    /** Agent responsable du contact. */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** Toutes les conversations WhatsApp liées à ce numéro. */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_identifier', 'whatsapp_number');
    }

    /** Tags associés (auto + manuels). */
    public function tags(): HasMany
    {
        return $this->hasMany(ContactTag::class);
    }

    /** Historique des changements de stage pipeline. */
    public function pipelineHistory(): HasMany
    {
        return $this->hasMany(ContactPipelineHistory::class)->orderBy('created_at', 'desc');
    }

    /** Logs de réception de campagnes. */
    public function campaignLogs(): HasMany
    {
        return $this->hasMany(CampaignLog::class);
    }

    /** Alertes générées pour les agents concernant ce contact. */
    public function alerts(): HasMany
    {
        return $this->hasMany(AgentAlert::class);
    }

    /** Historique chronologique des scores. */
    public function scoreHistory(): HasMany
    {
        return $this->hasMany(ContactScoreHistory::class)->orderBy('created_at', 'asc');
    }

    // ─── Timeline 360° ────────────────────────────────────────────────────────

    /**
     * Retourne une collection chronologique de tous les événements du contact :
     * messages WhatsApp, changements de stage, relances reçues.
     *
     * @return Collection Collection triée par date décroissante avec type et données
     */
    public function timeline(): Collection
    {
        $events = collect();

        // Conversations (chaque message = un événement)
        foreach ($this->conversations()->with('messages')->get() as $conversation) {
            foreach ($conversation->messages as $message) {
                $events->push([
                    'type'    => 'message',
                    'date'    => $message->created_at,
                    'icon'    => $message->sender_type === 'user' ? 'fa-comment' : 'fa-robot',
                    'color'   => $message->sender_type === 'user' ? 'blue' : 'purple',
                    'label'   => $message->sender_type === 'user' ? 'Message client' : 'Réponse Sophie',
                    'content' => Str::limit($message->content, 120),
                    'data'    => $message,
                ]);
            }
        }

        // Changements de stage pipeline
        foreach ($this->pipelineHistory()->with(['toStage', 'changedBy'])->get() as $history) {
            $events->push([
                'type'    => 'pipeline',
                'date'    => $history->created_at,
                'icon'    => 'fa-exchange-alt',
                'color'   => 'orange',
                'label'   => 'Changement de stage',
                'content' => 'Passage vers : ' . ($history->toStage->label ?? '—'),
                'data'    => $history,
            ]);
        }

        // Relances reçues
        foreach ($this->campaignLogs()->with('campaign')->get() as $log) {
            $events->push([
                'type'    => 'campaign',
                'date'    => $log->sent_at ?? $log->created_at,
                'icon'    => 'fa-paper-plane',
                'color'   => 'green',
                'label'   => 'Relance : ' . ($log->campaign->name ?? '—'),
                'content' => Str::limit($log->message_sent, 120),
                'data'    => $log,
            ]);
        }

        return $events->sortByDesc('date')->values();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Retourne les tags auto uniquement. */
    public function autoTags(): HasMany
    {
        return $this->hasMany(ContactTag::class)->where('source', 'auto');
    }

    /** Retourne les tags manuels uniquement. */
    public function manualTags(): HasMany
    {
        return $this->hasMany(ContactTag::class)->where('source', 'manual');
    }
}
