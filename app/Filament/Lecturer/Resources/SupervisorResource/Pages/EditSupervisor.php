<?php

namespace App\Filament\Lecturer\Resources\SupervisorResource\Pages;

use App\Filament\Lecturer\Resources\SupervisorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupervisor extends EditRecord
{
    protected static string $resource = SupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
