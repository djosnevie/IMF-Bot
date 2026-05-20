<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactPipelineHistory extends Model
{
    protected $fillable = [
        'contact_id', 'from_stage_id', 'to_stage_id', 'changed_by', 'reason',
    ];

    /** Contact concerné par ce changement. */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** Stage d'origine (peut être null pour le premier placement). */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'from_stage_id');
    }

    /** Nouveau stage atteint. */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'to_stage_id');
    }

    /** Utilisateur ayant effectué le changement (null si système). */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
