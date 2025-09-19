<?php

namespace App\Livewire\Users;

use App\Filament\Resources\UserResource\RelationManagers\OrdenesRelationManager;
use App\Http\Controllers\UserController;
use App\Models\Orden;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ListCaidos extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = User::query()
            ->whereHas('ordenes', function ($query) {
                $query->whereRaw(
                    '
                (SELECT MAX(created_at) 
                 FROM ordens 
                 WHERE ordens.cliente_id = users.id) < ?',
                    [now()->subDays(60)]
                )->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS);
            })
            ->orderByRaw('
            CASE 
                WHEN (SELECT MAX(created_at) FROM seguimientos WHERE seguimientos.user_id = users.id) >= ? 
                THEN 1 
                ELSE 0 
            END ASC, 
            (SELECT MIN(created_at) FROM ordens WHERE ordens.cliente_id = users.id) ASC
        ', [now()->subDays(7)]);

        return $table
            ->query($query)
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->searchable()
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->summarize(
                        Count::make(),
                    )
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('nit')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('dpi')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('razon_social')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('telefono')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('whatsapp')->copyable(),
                Tables\Columns\TextColumn::make('email')->copyable(),
                Tables\Columns\TextColumn::make('ultimoSeguimiento.seguimiento')
                    ->label('Ultimo Seguimiento')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('ultimoSeguimiento.redactor.name')
                    ->label('Asesor Seguimiento')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('ultimoSeguimiento.created_at')
                    ->label('Fecha Seguimiento')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Filter::make('seguimiento')
                    ->form([
                        Select::make('tipo_seguimiento')
                            ->options([
                                'con_seguimiento' => 'Con Seguimiento',
                                'sin_seguimiento' => 'Sin Seguimiento',
                                'todos' => 'Todos',
                            ])
                            ->default('todos'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tipo_seguimiento'] === 'con_seguimiento',
                                fn (Builder $query) => $query->whereHas('seguimientos')
                            )
                            ->when(
                                $data['tipo_seguimiento'] === 'sin_seguimiento',
                                fn (Builder $query) => $query->whereDoesntHave('seguimientos')
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('asignar')
                    ->color('success')
                    ->action(function (User $record): void {
                        UserController::asignar($record);
                    })
                    ->visible(function (User $record): bool {
                        $user = auth()->user();

                        // Verificar si el usuario autenticado es un asesor telemarketing sin cliente asignado
                        if (! $user->hasRole('asesor telemarketing') || $user->asignado_id !== null) {
                            return false;
                        }

                        // Contar asesores telemarketing disponibles (sin cliente asignado)
                        $asesoresDisponibles = User::role('asesor telemarketing')
                            ->whereNull('asignado_id')
                            ->count();

                        if ($asesoresDisponibles === 0) {
                            return false;
                        }

                        // Obtener los primeros N clientes sin asignar (según asesores disponibles)
                        $clientesSinAsignar = User::whereNull('asignado_id')
                            ->whereHas('ordenes', function ($query) {
                                $query->whereRaw(
                                    '
                                    (SELECT MAX(created_at) 
                                     FROM ordens 
                                     WHERE ordens.cliente_id = users.id) < ?',
                                    [now()->subDays(60)]
                                )->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS);
                            })
                            ->orderByRaw('
                                CASE 
                                    WHEN (SELECT MAX(created_at) 
                                          FROM seguimientos 
                                          WHERE seguimientos.user_id = users.id) >= ? 
                                    THEN 1 
                                    ELSE 0 
                                END ASC, 
                                (SELECT MIN(created_at) 
                                 FROM ordens 
                                 WHERE ordens.cliente_id = users.id) ASC
                            ', [now()->subDays(7)])
                            ->limit($asesoresDisponibles)
                            ->pluck('id')
                            ->toArray();

                        // Verificar si el cliente actual es asignable
                        return in_array($record->id, $clientesSinAsignar);
                    })
                    ->icon('tabler-address-book'),
                RelationManagerAction::make('historial')
                    ->label('Historial Órdenes')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->icon('tabler-history')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->relationManager(OrdenesRelationManager::make()),
                Tables\Actions\Action::make('historial-ventas')
                    ->label('Historial Ventas')
                    ->icon('heroicon-o-document-text')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalContent(fn ($record): View => view(
                        'filament.pages.actions.historial-ventas',
                        [
                            'ventas' => DB::select("
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
                                            ", [
                                $record->id,
                            ]),
                        ],
                    )),
                Tables\Actions\Action::make('Seguimiento')
                    ->color('orange')
                    ->icon('heroicon-o-folder-arrow-down')
                    ->form([
                        Textarea::make('seguimiento')
                            ->label('Seguimiento')
                            ->minLength(15)
                            ->required(),
                    ])
                    ->visible(function (User $record): bool {
                        $user = auth()->user();
                        if ($user->hasRole('asesor telemarketing')) {
                            if ($record->id == $user->asignado_id) {
                                return true;
                            } else {
                                return $record->asesores->contains($user)
                                    && optional($record->ultimoSeguimiento)->created_at?->gte(now()->subDays(7))
                                    && optional($record->ultimoSeguimiento)->redactor_id == $user->id;
                            }
                        }

                        return true;
                    })
                    ->action(function (array $data, User $record): void {
                        UserController::seguimiento($data, $record);
                    })
                    ->modalContent(fn (User $record): View => view(
                        'filament.pages.actions.seguimientos',
                        ['record' => $record],
                    ))
                    ->label('Seguimiento'),
            ]);
    }

    public function render(): View
    {
        return view('livewire.users.list-caidos');
    }
}
