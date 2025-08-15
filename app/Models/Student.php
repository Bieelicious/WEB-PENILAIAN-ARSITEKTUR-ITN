<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'name',
        'nim',
        'title_of_the_final_project_proposal',
        'design_theme',
        'group_id',
//        'room_id',

    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
