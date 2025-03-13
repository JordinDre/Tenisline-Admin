<?php

namespace App\Filament\Ventas\Resources;

use App\Enums\EstadoVentaStatus;
use App\Filament\Ventas\Resources\VentaDetalleResource\Pages;
use App\Models\Escala;
use App\Models\User;
use App\Models\VentaDetalle;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class VentaDetalleResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = VentaDetalle::class;

    protected static ?string $modelLabel = 'Venta Detalle';

    protected static ?string $pluralModelLabel = 'Ventas Detalles';

    protected static ?string $recordTitleAttribute = 'venta_id';

    protected static ?string $navigationIcon = 'tabler-list-letters';

    protected static ?string $navigationLabel = 'Ventas Detalles';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 2;

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
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make()->withFilename('Ventas Detalles '.date('d-m-Y'))->fromTable(),
                ])->label('Exportar')->color('success'),
            ])
            ->columns([
                /* Tables\Columns\TextColumn::make('orden.asesor.id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(), */
                Tables\Columns\TextColumn::make('venta.asesor.name')
                    ->label('Vendedor')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('comision')
                    ->numeric()
                    ->copyable()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ganancia')
                    ->summarize(Sum::make())
                    ->copyable()
                    ->sortable(), */
                Tables\Columns\TextColumn::make('escala.dia')
                    ->label('Escala Dia')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('escala.porcentaje')
                    ->label('Escala Porcentaje')    
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->summarize(Sum::make())
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('devuelto')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.codigo')
                    ->label('COD')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.descripcion')
                    ->searchable()
                    ->label('Producto')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('producto.marca.marca')
                    ->searchable()
                    ->label('Marca')
                    ->copyable()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('producto.presentacion.presentacion')
                    ->searchable()
                    ->label('Presentacion')
                    ->copyable()
                    ->sortable(), */
                Tables\Columns\TextColumn::make('venta.id')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('venta.estado')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('venta.created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:m:s')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('venta.fecha_liquidada')
                    ->label('Liquidada')
                    ->dateTime('d/m/Y')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orden.pagos.tipoPago.tipo_pago')
                    ->label('Pago con')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
            ])
            ->filters([
                Filter::make('estado')
                    ->form([
                        Select::make('estado')
                            ->multiple()
                            ->placeholder('Seleccione un estado')
                            ->options(EstadoVentaStatus::class),
                    ])
                    ->query(
                        fn (Builder $query, array $data) => filled($data['estado'])
                            ? $query->whereHas('venta', fn (Builder $q) => $q->whereIn('estado', $data['estado']))
                            : $query
                    ),
                /* Filter::make('escala')
                    ->form([
                        Select::make('escala')
                            ->multiple()
                            ->placeholder('Seleccione una escala')
                            ->options(
                                Escala::distinct()->pluck('escala', 'escala')
                            ),
                    ])
                    ->query(
                        fn (Builder $query, array $data) => filled($data['escala'])
                            ? $query->whereHas('escala', fn (Builder $q) => $q->whereIn('escala', $data['escala']))
                            : $query
                    ), */
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
                            $query->whereHas('venta', function (Builder $q) use ($data) {
                                $q->whereBetween('created_at', [
                                    Carbon::parse($data['fecha_inicial'])->startOfDay(),
                                    Carbon::parse($data['fecha_final'])->endOfDay(),
                                ]);
                            });
                        }

                        return $query;
                    }),
                Filter::make('fecha_liquidada')
                    ->form([
                        DatePicker::make('fecha_inicial')
                            ->label('Fecha Inicial Liquidación')
                            ->placeholder('Seleccione una fecha'),
                        DatePicker::make('fecha_final')
                            ->label('Fecha Final Liquidación')
                            ->placeholder('Seleccione una fecha'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['fecha_inicial'] && $data['fecha_final']) {
                            $query->whereHas('venta', function (Builder $q) use ($data) {
                                $q->whereBetween('fecha_liquidada', [
                                    Carbon::parse($data['fecha_inicial'])->startOfDay(),
                                    Carbon::parse($data['fecha_final'])->endOfDay(),
                                ]);
                            });
                        }

                        return $query;
                    }),
                Filter::make('asesor')
                    ->visible(auth()->user()->can('select_asesor'))
                    ->form([
                        Select::make('asesor')
                            ->multiple()
                            ->placeholder('Seleccione un vendedor')
                            ->searchable()
                            ->options(function () {
                                $user = auth()->user();
                                if ($user->hasAnyRole(['administrador', 'super_admin', 'vendedor'])) {
                                    return User::role(User::VENTA_ROLES)->pluck('name', 'id');
                                }
                                if ($user->hasAnyRole(User::SUPERVISORES_VENTA)) {
                                    return $user->asesoresSupervisados->pluck('name', 'id');
                                }

                                return [];
                            }),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['asesor'])) {
                            $query->whereHas('venta', function (Builder $q) use ($data) {
                                $q->whereIn('asesor_id', $data['asesor']);
                            });
                        }

                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContent)->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        $query->whereHas('venta', function ($q) {
            $q->where('tipo_pago_id', '!=', 2);
        });

        if ($user->hasAnyRole(['administrador', 'super_admin', 'facturador', 'creditos', 'rrhh', 'gerente'])) {
            return $query;
        }

        if ($user->hasAnyRole(User::VENTA_ROLES)) {
            return $query->whereHas('venta', function (Builder $q) use ($user) {
                $q->where('asesor_id', $user->id);
            });
        }
        if ($user->hasAnyRole(User::SUPERVISORES_VENTA)) {
            $supervisedIds = $user->asesoresSupervisados->pluck('id');

            return $query->whereHas('venta', function (Builder $q) use ($supervisedIds) {
                $q->whereIn('asesor_id', $supervisedIds);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentaDetalles::route('/'),
            'create' => Pages\CreateVentaDetalle::route('/create'),
            'edit' => Pages\EditVentaDetalle::route('/{record}/edit'),
        ];
    }
}
