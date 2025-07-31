<?php

namespace App\Filament\Resources\CategoryMappingResource\Pages;

use App\Filament\Resources\CategoryMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoryMappings extends ListRecords
{
    protected static string $resource = CategoryMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


}
