<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactTag extends Model
{
    protected $fillable = ['contact_id', 'name', 'source'];

    /**
     * Contact auquel ce tag appartient.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
