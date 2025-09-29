<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'student_id',
        'lecturer_id',
        'type',
        'assessment_stage',
        'notes',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssessmentItem::class);
    }
    
    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Group::class, 'group_id');
    }
    
}
