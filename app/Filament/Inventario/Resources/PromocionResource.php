<?php

namespace App\Filament\Inventario\Resources;

use App\Enums\TipoPromocionStatatus;
use App\Filament\Inventario\Resources\PromocionResource\Pages;
use App\Http\Controllers\ProductoController;
use App\Models\Producto;
use App\Models\Promocion;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromocionResource extends Resource
{
    protected static ?string $model = Promocion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('codigo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('tipo')
                    ->options(TipoPromocionStatatus::class)
                    ->live()
                    ->required(),
                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cantidad')
                    ->numeric()
                    ->required()
                    ->visible(fn (Get $get) => in_array($get('tipo'), ['mix', 'bonificacion', 'descuento']))
                    ->default(null),
                Forms\Components\TextInput::make('bonificacion')
                    ->numeric()
                    ->label('Bonificación')
                    ->visible(fn (Get $get) => in_array($get('tipo'), ['mix', 'bonificacion']))
                    ->required()
                    ->default(null),
                Forms\Components\TextInput::make('descuento')
                    ->numeric()
                    ->required()
                    ->prefix('%')
                    ->visible(fn (Get $get) => in_array($get('tipo'), ['descuento']))
                    ->default(null),
                Forms\Components\Select::make('marca_id')
                    ->relationship('marca', 'marca')
                    ->visible(fn (Get $get) => in_array($get('tipo'), ['mix']))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('presentacion_id')
                    ->relationship('presentacion', 'presentacion')
                    ->visible(fn (Get $get) => in_array($get('tipo'), ['mix']))
                    ->searchable()
                    ->required(),

                Repeater::make('detalles')
                    ->label('')
                    ->required()
                    ->visible(fn (Get $get) => in_array($get('tipo'), ['combo']))
                    ->collapsible()
                    ->relationship()
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->relationship('producto', 'descripcion', fn ($query) => $query->with(['marca', 'presentacion', 'escalas']))
                            ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, null, null))
                            ->allowHtml()
                            ->searchable(['id'])
                            ->getSearchResultsUsing(function (string $search): array {
                                return ProductoController::searchProductos($search, null, 1);
                            })
                            ->optionsLimit(12)
                            ->required(),
                        Select::make('tipo')
                            ->label('Tipo')
                            ->options(['principal' => 'Principal', 'adicional' => 'Adicional'])
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo'),
                Tables\Columns\TextColumn::make('cantidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonificacion')
                    ->numeric()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('cantidad_requerida')
                    ->numeric()
                    ->sortable(), */
                Tables\Columns\TextColumn::make('descuento')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('presentacion.presentacion')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('marca.marca')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePromocions::route('/'),
        ];
    }

    public static function getNavigationItems(): array //  AÑADE ESTE MÉTODO
    {
        return [
            parent::getNavigationItems()[0] // Obtiene el elemento de navegación por defecto
                ->visible(false), //  Aplica ->visible(false) para ocultarlo SIEMPRE
        ];
    }
}
