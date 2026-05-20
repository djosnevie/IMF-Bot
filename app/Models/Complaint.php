<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'conversation_id',
        'whatsapp_number',
        'subject',
        'description',
        'category',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Conversation WhatsApp liée à cette plainte.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Ticket généré pour cette plainte.
     */
    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}
