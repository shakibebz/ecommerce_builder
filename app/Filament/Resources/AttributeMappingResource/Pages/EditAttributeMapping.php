<?php

namespace App\Filament\Resources\AttributeMappingResource\Pages;

use App\Filament\Resources\AttributeMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttributeMapping extends EditRecord
{
    protected static string $resource = AttributeMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
