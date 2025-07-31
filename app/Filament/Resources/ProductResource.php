<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ProductStatus;
use Filament\Actions\Action;
use App\Services\MagentoService;
use Filament\Resources\Resource;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Filament\Imports\ProductImporter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;

use HayderHatem\FilamentExcelImport\Actions\Concerns\CanImportExcelRecords;

use Filament\Actions;

class ProductResource extends Resource
{
    use CanImportExcelRecords;
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([

                    Section::make(__('Product Details'))
                        ->columnSpan(2)
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Product Name')
                                    ->translateLabel()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->translateLabel()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('brand')
                                    ->label('Brand')
                                    ->translateLabel()
                                    ->maxLength(255),

                                TextInput::make('category')
                                    ->label('Category')
                                    ->translateLabel()
                                    ->maxLength(255),
                            ]),

                            RichEditor::make('description')
                                ->label('Product Description')
                                ->translateLabel()
                                ->required()
                                ->maxLength(5000)
                                ->columnSpanFull(),
                        ]),

                    Section::make(__('Status & Pricing'))
                        ->columnSpan(1)
                        ->schema([
                            Select::make('status')
                                ->required()
                                ->translateLabel()
                                ->options(ProductStatus::class)
                                ->default(ProductStatus::PendingReview),

                            TextInput::make('price')
                                ->required()
                                ->translateLabel()
                                ->numeric(),

                            TextInput::make('stock_quantity')
                                ->label('Stock Quantity')
                                ->translateLabel()
                                ->numeric()
                                ->default(0),

                            TextInput::make('source_url')
                                ->label('Source URL')
                                ->translateLabel()
                                ->url()
                                ->maxLength(2048)
                                ->disabled()
                                ->columnSpanFull(),
                        ]),
                ]),

                Section::make(__('Product Images'))
                    ->collapsible()
                    ->schema([
                        FileUpload::make('images')
                            ->label('Gallery Images')
                            ->translateLabel()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            ->directory('products')
                            ->image()
                            ->imageEditor()
                            ->helperText('Add or reorder the product gallery images.')
                    ]),


                Section::make('Advanced & Crawler Data')
                    ->collapsible()
                    ->columns(2)
                    ->schema([

                        KeyValue::make('attributes')
                            ->translateLabel()
                            ->label('Product Attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Attribute Value')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('name')
                    ->searchable()
                    ->words(5),

                TextColumn::make('price')
                    ->money()
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(ProductStatus $state): string => match ($state) {
                        ProductStatus::PendingReview => 'warning',
                        ProductStatus::Approved => 'gray',
                        ProductStatus::Rejected => 'danger',
                        ProductStatus::Synced => 'success',
                        ProductStatus::SyncFailed => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getMagentoSyncAction(): Action
    {
        return Action::make('syncToMagento')
            ->label('Sync to Magento')
            ->icon('heroicon-o-arrow-path')
            ->color('success') // Make it stand out
            ->requiresConfirmation() // Good practice to prevent accidental clicks
            ->modalHeading('Sync Product to Magento')
            ->modalDescription('Are you sure you want to sync this product now? This will create or update the product in Magento.')
            ->action(function (Product $record) {
                // The core logic of the action
                try {
                    // Use the service container to resolve your MagentoService
                    $magentoService = app(MagentoService::class);
                    $magentoService->createOrUpdateProduct($record);

                    $record->update([
                        'status' => ProductStatus::Synced,
                        'sync_error_message' => null,
                    ]);


                    // Send a success notification
                    Notification::make()
                        ->title('Sync Successful')
                        ->body('The product has been successfully synced to Magento.')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    // Send a failure notification
                    Notification::make()
                        ->title('Sync Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
