<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentCount extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total', Student::get()->count())
                ->description('Total number of students'),
        ];
    }
}
