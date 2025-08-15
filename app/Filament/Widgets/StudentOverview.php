<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $student = Student::all();
        return [
            Stat::make('Student', $student->count())
                ->description('Total number of students')
                ->url(StudentResource::getUrl('index'))
        ];
    }
}
