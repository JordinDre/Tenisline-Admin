<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Forms;
use App\Models\Meta;
use Filament\Tables;
use App\Models\Bodega;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Http\Controllers\Utils\Functions;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MetaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class MetaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Meta::class;

    protected static ?string $modelLabel = 'Meta';

    protected static ?string $pluralModelLabel = 'Metas';

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
                Forms\Components\Select::make('bodega_id')
                    ->label('Bodega')
                    ->relationship(
                        name: 'bodega',
                        titleAttribute: 'bodega',
                        modifyQueryUsing: function (Builder $query) {
                            $query->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                                ->where('bodega', 'not like', '%bodega%');
                        }
                    )
                    ->optionsLimit(20)
                    ->reactive()
                    ->rules([
                        fn (Get $get, string $operation): Closure => function (string $attribute, $value, Closure $fail) use ($get, $operation) {
                            if ($operation == 'create') {
                                $exists = Meta::where('bodega_id', $value)
                                    ->where('mes', $get('mes'))
                                    ->where('anio', $get('anio'))
                                    ->exists();
                                if ($exists) {
                                    $fail('Ya existe una meta para esta bodega en el mes y año indicados.');
                                }
                            }
                        },
                    ])
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
                    ->label('Meta (Q)')
                    ->required()
                    ->inputMode('decimal')
                    ->rule('numeric')
                    ->minValue(0)
                    ->step(0.01),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Selecciona filtros para ver resultados')
            ->emptyStateDescription('Filtra por Bodega, Mes y Año para cargar solo los datos necesarios.')
            ->emptyStateIcon('heroicon-o-adjustments-horizontal')

            ->columns([
                Tables\Columns\TextColumn::make('mes_anio')
                    ->label('Mes y Año')
                    ->getStateUsing(fn ($record) => \App\Http\Controllers\Utils\Functions::nombreMes($record->mes).', '.$record->anio)
                    ->sortable(),

                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->label('Bodega')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('meta')
                    ->label('Meta (Q)')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable()
                    ->color(function ($record) {
                        $porcentaje = $record->meta > 0
                            ? ($record->proyeccion / $record->meta) * 100
                            : 0;

                        if ($porcentaje >= 100) return 'success';
                        if ($porcentaje >= 75)  return 'warning';
                        return 'danger';
                    }),

                Tables\Columns\TextColumn::make('proyeccion')
                    ->label('Proyección (Q)')
                    ->money('GTQ')
                    ->color(function ($record) {
                        $porcentaje = $record->meta > 0
                            ? ($record->proyeccion / $record->meta) * 100
                            : 0;

                        if ($porcentaje >= 100) return 'success';
                        if ($porcentaje >= 75)  return 'warning';
                        return 'danger';
                    }),

                Tables\Columns\TextColumn::make('proyeccion2')
                    ->label('Proyección (%)')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->copyable()
                    ->sortable()
                    ->color(function ($record) {
                        $porcentaje = $record->meta > 0
                            ? ($record->proyeccion / $record->meta) * 100
                            : 0;

                        if ($porcentaje >= 100) return 'success';
                        if ($porcentaje >= 75)  return 'warning';
                        return 'danger';
                    }),

                Tables\Columns\TextColumn::make('ventas_reales')
                    ->label('Alcance (Q)')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('alcance')
                    ->label('Alcance (%)')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->copyable()
                    ->sortable()
                    ->color(function ($record) {
                        $porcentaje = $record->meta > 0
                            ? ($record->proyeccion / $record->meta) * 100
                            : 0;

                        if ($porcentaje >= 100) return 'success';
                        if ($porcentaje >= 75)  return 'warning';
                        return 'danger';
                    }),

                Tables\Columns\TextColumn::make('rendimiento')
                    ->label('Rendimiento (%)')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('cumplida')
                    ->label('Cumplida')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

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
                Tables\Filters\Filter::make('segmento')
                    ->label('Filtros')
                    ->default(true) 
                    ->form([
                        Forms\Components\Select::make('bodega_id')
                            ->label('Bodega')
                            ->options(fn () => Bodega::query()
                                ->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                                ->where('bodega', 'not like', '%bodega%')
                                ->orderBy('bodega')
                                ->pluck('bodega', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('mes')
                            ->label('Mes')
                            ->options(\App\Http\Controllers\Utils\Functions::obtenerMeses()),

                        Forms\Components\Select::make('anio')
                            ->label('Año')
                            ->options(array_combine(
                                \App\Http\Controllers\Utils\Functions::obtenerAnios(),
                                \App\Http\Controllers\Utils\Functions::obtenerAnios()
                            )),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $noHayNada = blank($data['bodega_id'] ?? null)
                                && blank($data['mes'] ?? null)
                                && blank($data['anio'] ?? null);

                        if ($noHayNada) {
                            return $query->whereRaw('1 = 0');
                        }

                        if (filled($data['bodega_id'] ?? null)) {
                            $query->where('bodega_id', $data['bodega_id']);
                        }
                        if (filled($data['mes'] ?? null)) {
                            $query->where('mes', $data['mes']);
                        }
                        if (filled($data['anio'] ?? null)) {
                            $query->where('anio', $data['anio']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $chips = [];
                        if (filled($data['bodega_id'] ?? null)) {
                            $nombre = optional(Bodega::find($data['bodega_id']))?->bodega;
                            if ($nombre) $chips[] = "Bodega: {$nombre}";
                        }
                        if (filled($data['mes'] ?? null)) {
                            $chips[] = 'Mes: '.\App\Http\Controllers\Utils\Functions::nombreMes($data['mes']);
                        }
                        if (filled($data['anio'] ?? null)) {
                            $chips[] = "Año: {$data['anio']}";
                        }
                        return $chips;
                    }),
            ])

            ->filtersFormColumns(3)

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
