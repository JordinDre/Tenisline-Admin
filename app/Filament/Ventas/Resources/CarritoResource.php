<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\CarritoResource\Pages;
use App\Models\Carrito;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CarritoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Carrito::class;

    protected static ?string $modelLabel = 'Carrito';

    protected static ?string $pluralModelLabel = 'Carritos';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationIcon = 'tabler-shopping-cart-search';

    protected static ?string $navigationLabel = 'Carrito';

    protected static ?string $navigationGroup = 'Gestiones';

    protected static ?int $navigationSort = 4;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('producto_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('cantidad')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('precio')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('user.id')
                    ->label('Cliente ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.razan_social')
                    ->label('Razón Social')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nombre Comercial')
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.id')
                    ->label('Producto ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.codigo')
                    ->label('Código')
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.descripcion')
                    ->label('Descripción')
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.marca.marca')
                    ->label('Marca')
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.presentacion.presentacion')
                    ->label('Presentación')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio')
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
            'index' => Pages\ManageCarritos::route('/'),
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
