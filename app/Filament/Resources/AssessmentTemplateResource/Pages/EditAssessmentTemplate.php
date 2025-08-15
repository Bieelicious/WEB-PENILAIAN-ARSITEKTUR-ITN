<?php

namespace App\Filament\Resources\AssessmentTemplateResource\Pages;

use App\Filament\Resources\AssessmentTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentTemplate extends EditRecord
{
    protected static string $resource = AssessmentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
