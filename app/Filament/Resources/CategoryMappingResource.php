<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryMappingResource\Pages;
use App\Models\CategoryMapping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryMappingResource extends Resource
{
    protected static ?string $model = CategoryMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?int $navigationSort = 3;

    /**
     * Get the number to display in the navigation badge.
     *
     * @return string|null
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_mapped', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the color of the navigation badge.
     *
     * @return string|null
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_mapped', false)->count() > 0 ? 'danger' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('source_name')
                    ->label('Source Category Name')
                    ->required()
                    ->disabled()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('magento_category_id')
                    ->label('Magento Category ID')
                    ->helperText('After creating the category in Magento, enter its numeric ID here.')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('magento_category_id')
                    ->label('Magento ID'),
                Tables\Columns\IconColumn::make('is_mapped')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoryMappings::route('/'),
            // We don't need a create page, discovery is automatic
            'edit' => Pages\EditCategoryMapping::route('/{record}/edit'),
        ];
    }
}
