<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage',
        'labels',
    ];

    protected $casts = [
        'labels' => 'array',
    ];
}
