<?php

namespace App\Filament\Resources\AttributeMappingResource\Pages;

use App\Filament\Resources\AttributeMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttributeMappings extends ListRecords
{
    protected static string $resource = AttributeMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
