<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\AttributeMapping;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AttributeMappingResource\Pages;

class AttributeMappingResource extends Resource
{
    protected static ?string $model = AttributeMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?int $navigationSort = 2;

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
                TextInput::make('source_label')
                    ->label('Source Label (From Crawler)')
                    ->required()
                    ->disabled()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('magento_attribute_code')
                    ->label('Magento Attribute Code')
                    ->helperText('Enter the exact attribute_code you created in Magento.')
                    ->required()
                    ->maxLength(255),

                Select::make('magento_attribute_type')
                    ->label('Magento Attribute Type')
                    ->options([
                        'select' => 'Select (Dropdown with options)',
                        'text' => 'Text (Single line)',
                        'textarea' => 'Text Area (Multiple lines)',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_label')
                    ->label('Source Label')
                    ->searchable(),
                TextColumn::make('magento_attribute_code')
                    ->label('Magento Code')
                    ->searchable(),
                TextColumn::make('magento_attribute_type')
                    ->badge(),
                IconColumn::make('is_mapped')
                    ->label('Is Mapped?')
                    ->boolean(),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // This adds the tabs to the list view, making the workflow obvious for the admin
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributeMappings::route('/'),
            'create' => Pages\CreateAttributeMapping::route('/create'),
            'edit' => Pages\EditAttributeMapping::route('/{record}/edit'),
        ];
    }
}
