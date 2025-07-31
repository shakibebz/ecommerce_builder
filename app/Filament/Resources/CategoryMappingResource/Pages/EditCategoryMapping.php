<?php

namespace App\Filament\Resources\CategoryMappingResource\Pages;

use App\Filament\Resources\CategoryMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoryMapping extends EditRecord
{
    protected static string $resource = CategoryMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
