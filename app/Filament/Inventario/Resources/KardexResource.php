<?php

namespace App\Filament\Inventario\Resources;

use App\Filament\Inventario\Resources\KardexResource\Pages;
use App\Models\Bodega;
use App\Models\Kardex;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class KardexResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Kardex::class;

    protected static ?string $slug = 'kardex';

    protected static ?string $modelLabel = 'Kardex';

    protected static ?string $pluralModelLabel = 'Kardex';

    protected static ?string $navigationLabel = 'Kardex';

    protected static ?string $navigationIcon = 'tabler-report-search';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make()->withFilename('Kardex '.date('d-m-Y'))->fromTable(),
                ])->label('Exportar')->color('success'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kardexable_type')
                    ->label('Modelo')
                    ->formatStateUsing(function ($record) {
                        return class_basename($record->kardexable_type).' #'.$record->kardexable_id;
                    })
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('existencia_inicial')
                    ->label('Ex.Inicial')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('existencia_final')
                    ->label('Ex.Final')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('evento')
                    ->badge()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción'),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.codigo')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.descripcion')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.color')
                    ->label('Color')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('producto.marca.marca')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('producto.presentacion.presentacion')
                    ->searchable()
                    ->copyable()
                    ->sortable(), */
            ])
            ->filters([
                SelectFilter::make('bodega_id')
                    ->label('Bodega')
                    ->multiple()
                    ->options(fn () => Bodega::pluck('bodega', 'id')),
                SelectFilter::make('evento')
                    ->label('Evento')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                    ]),
                SelectFilter::make('kardexable_type')
                    ->label('Modelo')
                    ->multiple()
                    ->options([
                        'App\Models\Compra' => 'Compra',
                        'App\Models\Inventario' => 'Inventario',
                        /* 'App\Models\Orden' => 'Orden',
                        'App\Models\Traslado' => 'Traslado', */
                        'App\Models\Venta' => 'Venta',
                    ]),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('fecha_inicial')
                            ->label('Fecha Inicial Creación')
                            ->placeholder('Seleccione una fecha'),
                        DatePicker::make('fecha_final')
                            ->label('Fecha Final Creación')
                            ->placeholder('Seleccione una fecha'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['fecha_inicial'] && $data['fecha_final']) {
                            $query->whereBetween('created_at', [
                                Carbon::parse($data['fecha_inicial'])->startOfDay(),
                                Carbon::parse($data['fecha_final'])->endOfDay(),
                            ]);
                        }

                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContent)->poll('10s');
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
            'index' => Pages\ListKardexes::route('/'),
        ];
    }
}
