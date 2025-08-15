<?php

namespace App\Filament\Lecturer\Resources\ExaminerResource\Pages;

use App\Filament\Lecturer\Resources\ExaminerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExaminer extends EditRecord
{
    protected static string $resource = ExaminerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
