<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactScoreHistory extends Model
{
    protected $fillable = [
        'contact_id', 'score', 'delta', 'reason',
    ];

    protected $casts = [
        'score' => 'integer',
        'delta' => 'integer',
    ];

    /** Contact associé à cet enregistrement de score. */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
