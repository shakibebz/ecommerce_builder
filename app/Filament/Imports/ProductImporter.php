<?php

namespace App\Filament\Imports;

use App\Models\Product;
//use Filament\Actions\Imports\Importer;


use Filament\Actions\Imports\Importer;

use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;



class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;


    public static function getColumns(): array
    {
        return [
            ImportColumn::make('sku')
                ->label('SKU')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('description'),

            ImportColumn::make('price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),

            ImportColumn::make('stock_quantity')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),

            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('images'),

            ImportColumn::make('attributes'),

            ImportColumn::make('category')
                ->rules(['max:255']),

            ImportColumn::make('source_url')
                ->rules(['max:500']),

            ImportColumn::make('brand')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        // return Product::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Product();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
