<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentItem extends Model
{
    protected $fillable = [
        'assessment_id',
        'label',
        'criteria',
        'score',
        'description',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
