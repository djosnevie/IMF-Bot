<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ComplaintType extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Agents habilités à traiter ce type de plainte.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
