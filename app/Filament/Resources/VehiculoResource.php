<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoResource\Pages;
use App\Models\Vehiculo;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiculoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Vehiculo::class;

    protected static ?string $modelLabel = 'Vehículo';

    protected static ?string $pluralModelLabel = 'Vehículos';

    protected static ?string $recordTitleAttribute = 'placa';

    protected static ?string $navigationLabel = 'Vehículos';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Gestiones';

    protected static ?int $navigationSort = 4;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'restore',
            /* 'delete', */
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('placa')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('color')
                    ->maxLength(25)
                    ->type('color')
                    ->required(),
                Forms\Components\TextInput::make('tipo_placa')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('marca')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('linea')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('año')
                    ->inputMode('decimal')
                    ->rule('numeric')
                    ->rules(['min:1900', 'max:'.date('Y')])
                    ->required(),
                Forms\Components\TextInput::make('ejes')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('toneladas')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('tanque')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('combustible')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('volumen_carga')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('motor')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('modelo')
                    ->required()
                    ->maxLength(25),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('placa')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('tipo_placa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca')
                    ->searchable(),
                Tables\Columns\TextColumn::make('linea')
                    ->searchable(),
                Tables\Columns\TextColumn::make('año'),
                Tables\Columns\TextColumn::make('ejes')
                    ->searchable(),
                Tables\Columns\TextColumn::make('toneladas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanque')
                    ->searchable(),
                Tables\Columns\TextColumn::make('combustible')
                    ->searchable(),
                Tables\Columns\TextColumn::make('volumen_carga')
                    ->searchable(),
                Tables\Columns\TextColumn::make('motor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->label('Desactivar'),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Desactivar'),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVehiculos::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationItems(): array //  AÑADE ESTE MÉTODO
    {
        return [
            parent::getNavigationItems()[0] // Obtiene el elemento de navegación por defecto
                ->visible(false), //  Aplica ->visible(false) para ocultarlo SIEMPRE
        ];
    }
}
