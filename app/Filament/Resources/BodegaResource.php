<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BodegaResource\Pages;
use App\Models\Bodega;
use App\Models\Departamento;
use App\Models\Municipio;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BodegaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Bodega::class;

    protected static ?string $modelLabel = 'Bodega';

    protected static ?string $pluralModelLabel = 'Bodegas';

    protected static ?string $recordTitleAttribute = 'bodega';

    protected static ?string $navigationLabel = 'Bodegas';

    protected static ?string $navigationIcon = 'tabler-building-warehouse';

    protected static ?string $navigationGroup = 'Gestiones';

    protected static ?int $navigationSort = 5;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            /* 'update',
            'restore',
            'delete', */
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('bodega')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                Forms\Components\TextInput::make('direccion')
                    ->required()
                    ->maxLength(100),

                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                ])->schema([
                    Select::make('pais_id')
                        ->relationship('pais', 'pais')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set) {
                            $set('departamento_id', null);
                            $set('municipio_id', null);
                        })
                        ->default(1)
                        ->searchable()
                        ->preload(),
                    Select::make('departamento_id')
                        ->label('Departamento')
                        ->options(fn (Get $get) => Departamento::where('pais_id', $get('pais_id'))->pluck('departamento', 'id'))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set) {
                            $set('municipio_id', null);
                        })
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('municipio_id')
                        ->label('Municipio')
                        ->options(fn (Get $get) => Municipio::where('departamento_id', $get('departamento_id'))->pluck('municipio', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                ]),
                Select::make('user_id')
                    ->label('Acceso a Bodega')
                    ->relationship('user', 'name')
                    ->multiple()
                    ->searchable()
                    ->optionsLimit(20)
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('bodega')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('direccion')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('municipio.municipio')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('departamento.departamento')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('pais.pais')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBodegas::route('/'),
            'create' => Pages\CreateBodega::route('/create'),
            'view' => Pages\ViewBodega::route('/{record}'),
            'edit' => Pages\EditBodega::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
