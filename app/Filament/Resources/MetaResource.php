<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MetaResource\Pages;
use App\Http\Controllers\Utils\Functions;
use App\Models\Meta;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MetaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Meta::class;

    protected static ?string $modelLabel = 'Meta';

    protected static ?string $pluralModelLabel = 'Metas';

    protected static ?string $recordTitleAttribute = 'meta';

    protected static ?string $navigationIcon = 'tabler-file-text';

    protected static ?string $navigationLabel = 'Metas';

    protected static ?int $navigationSort = 4;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'restore',
            'delete',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name', fn (Builder $query) => $query->role(User::VENTA_ROLES))
                    ->searchable()
                    ->optionsLimit(20)
                    ->reactive()
                    ->rules([
                        fn (Get $get, string $operation): Closure => function (string $attribute, $value, Closure $fail) use ($get, $operation) {
                            if ($operation == 'create') {
                                $exists = Meta::where('user_id', $value)
                                    ->where('mes', $get('mes'))
                                    ->where('anio', $get('anio'))
                                    ->exists();
                                if ($exists) {
                                    $fail('Ya existe una meta para el asesor seleccionado en el mes y año indicados.');
                                }
                            }
                        },
                    ])
                    ->required(),
                Forms\Components\Select::make('bodega_id')
                    ->label('Bodega')
                    ->relationship(
                        name: 'bodega',
                        titleAttribute: 'bodega',
                        modifyQueryUsing: function (Builder $query, Get $get) {
                            if ($get('user_id')) {
                                $query->whereHas('user', function (Builder $q) use ($get) {
                                    $q->where('users.id', $get('user_id'))
                                    ->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                                    ->where('bodega', 'not like', '%bodega%');
                                });
                            }
                        }
                    )
                    ->optionsLimit(20)
                    ->reactive() 
                    ->required(),
                Forms\Components\Select::make('mes')
                    ->options(Functions::obtenerMeses())
                    ->default(now()->month)
                    ->required(),
                Forms\Components\Select::make('anio')
                    ->label('Año')
                    ->options(array_combine(Functions::obtenerAnios(), Functions::obtenerAnios()))
                    ->default(now()->year)
                    ->required(),
                Forms\Components\TextInput::make('meta')
                    ->rules([
                        fn (Get $get, string $operation): Closure => function (string $attribute, $value, Closure $fail) use ($get, $operation) {
                            if ($operation == 'edit') {
                                $meta = Meta::where('user_id', $get('user_id'))
                                    ->where('mes', $get('mes'))
                                    ->where('anio', $get('anio'))
                                    ->first();
                                if ($meta->exists && $meta->meta == $value) {
                                    $fail('Ya existe una meta para el asesor seleccionado en el mes y año indicados.');
                                }
                            }
                        },
                    ])
                    ->required()
                    ->inputMode('decimal')
                    ->rule('numeric'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->numeric()
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mes')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return Functions::nombreMes($record->mes);
                    })
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('meta')
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
            ->filters([])
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMetas::route('/'),
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
