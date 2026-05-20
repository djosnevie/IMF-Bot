<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    protected $fillable = ['label', 'color', 'sort_order', 'is_final'];

    protected $casts = [
        'is_final'   => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Historique des passages vers ce stage.
     */
    public function history(): HasMany
    {
        return $this->hasMany(ContactPipelineHistory::class, 'to_stage_id');
    }
}
