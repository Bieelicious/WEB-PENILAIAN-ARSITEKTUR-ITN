<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\GroupResource;
use App\Filament\Resources\UserResource;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Jumlah semua user (dosen, admin)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total Groups', Group::count())
                ->description('Jumlah semua kelompok mahasiswa')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([17, 16, 14, 15, 14, 13, 12]),

            Stat::make('Total Students', Student::count())
                ->description('Jumlah semua mahasiswa terdaftar')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([1, 5, 2, 8, 4, 9, 3]),
        ];
    }
}
