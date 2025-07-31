<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use App\Models\Product;
use App\Enums\ProductStatus;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Filament\Imports\ProductImporter;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource;
use App\Services\AdminPanel\ProductImportService;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('importProducts')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('attachment')
                        ->label('Excel File')
                        ->required()
                        ->disk('local') // Use a non-public disk.
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                ])
                // Inject the service directly into the action closure.
                ->action(function (array $data, ProductImportService $productImportService) {
                    try {
                        $filePath = $data['attachment'];
                        $fullPath = Storage::disk('local')->path($filePath);

                        // Instantiate the importer, passing the required service.
                        $importer = new ProductsImport(productImportService: $productImportService);

                        // Use queueImport to process the file in the background.
                        Excel::queueImport($importer, $fullPath);

                        // Notify the user that the job has started.
                        Notification::make()
                            ->title('Import Scheduled Successfully')
                            ->body('Your products will be imported in the background. You will be notified upon completion.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('An unexpected error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Product::count()),

            'unsynced' => Tab::make('Not Synchronized')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', "!=", ProductStatus::Synced->value))
                ->badge(Product::where('status', '!=', ProductStatus::Synced->value)->count()),
        ];
    }
}
