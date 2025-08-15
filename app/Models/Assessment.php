<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Filament\Resources\AssessmentResource;

class Assessment extends Model
{
    protected $fillable = [
        'student_id',
        'group_id',
        'lecturer_id',
        'assessment_stage',
        'assessment',
        'notes',
        'type'
    ];

    protected $casts = [
        'assessment' => 'array',
    ];

    /**
     * Get the assessment value with preserved order.
     *
     * @param  array  $value
     * @return array
     */
    public function getAssessmentAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        $assessment = is_string($value) ? json_decode($value, true) : $value;
        
        // Get template based on the assessment stage
        $template = method_exists(AssessmentResource::class, 'getAssessmentData') 
            ? AssessmentResource::getAssessmentData($this->assessment_stage)
            : [];
            
        if (empty($template)) {
            return $assessment;
        }
        
        return $this->maintainAssessmentOrder($assessment, $template);
    }
    
    /**
     * Maintain assessment order
     * 
     * @param array $existingData
     * @param array $template
     * @return array
     */
    private function maintainAssessmentOrder(array $existingData, array $template): array
    {
        $result = [];
        // First add keys from template in their original order
        foreach ($template as $key => $defaultValue) {
            $result[$key] = array_key_exists($key, $existingData) ? $existingData[$key] : $defaultValue;
        }
        
        // Then add any extra keys from existing data that weren't in the template
        foreach ($existingData as $key => $value) {
            if (!array_key_exists($key, $result)) {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}
