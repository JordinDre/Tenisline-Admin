<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\CaidosResource\Pages;
use App\Models\Venta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CaidosResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $modelLabel = 'Usuario Caido';

    protected static ?string $pluralModelLabel = 'Usuarios Caidos';

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = Venta::query()
            ->select('ventas.*')
            ->join(
                DB::raw('(SELECT cliente_id, MAX(created_at) as ultima_fecha FROM ventas GROUP BY cliente_id) v2'),
                function ($join) {
                    $join->on('ventas.cliente_id', '=', 'v2.cliente_id')
                        ->on('ventas.created_at', '=', 'v2.ultima_fecha');
                }
            )
            ->leftJoin(
                DB::raw('(SELECT seguimientable_id, MIN(created_at) as primer_seguimiento FROM seguimientos WHERE seguimientable_type = ? AND tipo = ? GROUP BY seguimientable_id) s'),
                's.seguimientable_id',
                '=',
                'ventas.cliente_id'
            )
            ->addBinding(\App\Models\User::class, 'join')
            ->addBinding('seguimiento', 'join')
            ->selectRaw('ventas.*, s.primer_seguimiento')
            ->with(['cliente', 'asesor']);

        // Filtrar por bodega del vendedor si es vendedor
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('vendedor')) {
                $bodegaIds = $user->bodegas()->pluck('bodegas.id')->toArray();
                if (!empty($bodegaIds)) {
                    $query->whereIn('ventas.bodega_id', $bodegaIds);
                }
            }
        }

        return $query
            ->orderByRaw('CASE WHEN s.primer_seguimiento IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN s.primer_seguimiento IS NULL THEN ventas.created_at ELSE s.primer_seguimiento END');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.id')
                    ->label('Cliente ID')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.telefono')
                    ->label('Telefono')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.name')
                    ->label('Nombre')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.razon_social')
                    ->label('Razón Social')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nit')
                    ->label('NIT')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->searchable()
                    ->label('Vendedor')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->label('Bodega')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Zacapa' => 'success',
                        'Chiquimula' => 'info',
                        'Esquipulas' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('id')
                    ->label('Venta ID')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de la venta')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ultimo_seguimiento')
                    ->label('Último seguimiento')
                    ->getStateUsing(fn ($record) => $record->cliente?->ultimoSeguimiento()?->seguimiento ?? '—')
                    ->description(fn ($record) => $record->cliente?->ultimoSeguimiento()?->created_at?->format('d/m/Y H:i') ?? ''),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bodega_id')
                    ->label('Bodega')
                    ->relationship('bodega', 'bodega')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->visible(function () {
                        if (!Auth::check()) return false;
                        $user = Auth::user();
                        return method_exists($user, 'hasRole') && $user->hasRole(['admin', 'super_admin']);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('seguimiento')
                    ->label('Seguimiento')
                    ->icon('heroicon-o-phone')
                    ->form([
                        Forms\Components\Textarea::make('seguimiento')
                            ->label('Nota de seguimiento')
                            ->required(),
                    ])
                    ->action(function (Venta $record, array $data): void {
                        \App\Models\Seguimiento::create([
                            'seguimiento' => $data['seguimiento'],
                            'user_id' => Auth::id() ?: 1,
                            'seguimientable_id' => $record->cliente_id,
                            'seguimientable_type' => \App\Models\User::class,
                            'tipo' => 'seguimiento',
                        ]);

                        Notification::make()
                            ->title('Seguimiento registrado')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Nuevo seguimiento')
                    ->modalSubmitActionLabel('Guardar'),

                Tables\Actions\Action::make('historial')
                    ->icon('heroicon-o-document-text')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalContent(fn ($record): View => view(
                        'filament.pages.actions.historial-ventas',
                        [
                            'ventas' => DB::select('
                                                select
                                                ventas.id as venta_id,
                                                users.name,
                                                ventas.created_at as fecha_venta,
                                                productos.codigo,
                                                productos.descripcion,
                                                marcas.marca,
                                                productos.talla,
                                                productos.genero,
                                                venta_detalles.cantidad,
                                                venta_detalles.subtotal,
                                                (
                                                    select
                                                        u.name
                                                    from
                                                        users u
                                                    where
                                                        u.id = ventas.asesor_id
                                                ) as asesor
                                            from
                                                ventas
                                                inner join users on users.id = ventas.cliente_id
                                                inner join venta_detalles on venta_detalles.venta_id = ventas.id
                                                inner join productos on venta_detalles.producto_id = productos.id
                                                inner join marcas on productos.marca_id = marcas.id
                                            WHERE
                                                ventas.cliente_id = ?
                                            ORDER BY
                                                ventas.created_at DESC
                                        ', [
                                $record->cliente_id,
                            ]),
                        ],
                    ))
                    ->label('Historial'),

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
            'index' => Pages\ListCaidos::route('/'),
            'create' => Pages\CreateCaidos::route('/create'),
            'edit' => Pages\EditCaidos::route('/{record}/edit'),
        ];
    }
}
