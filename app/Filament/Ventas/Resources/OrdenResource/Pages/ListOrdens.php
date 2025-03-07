<?php

namespace App\Filament\Ventas\Resources\OrdenResource\Pages;

use App\Enums\EstadoOrdenStatus;
use App\Filament\Ventas\Resources\OrdenResource;
use App\Http\Controllers\OrdenController;
use App\Models\Orden;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrdens extends ListRecords
{
    protected static string $resource = OrdenResource::class;

    /* public function getTabs(): array
    {
        $user = auth()->user();

        return collect(EstadoOrdenStatus::cases())->mapWithKeys(function (EstadoOrdenStatus $estado) use ($user) {
            return [
                $estado->getLabel() => Tab::make()
                    ->modifyQueryUsing(function (Builder $query) use ($estado, $user) {
                        $query->where('estado', $estado->value);
                        if (! $user->hasAnyRole(['administrador', 'super_admin', 'facturador', 'creditos', 'rrhh', 'gerente'])) {
                            if ($user->hasAnyRole(User::ORDEN_ROLES)) {
                                $query->where('asesor_id', $user->id);
                            }

                            if ($user->hasAnyRole(User::SUPERVISORES_ORDEN)) {
                                $supervisedIds = $user->asesoresSupervisados->pluck('id');
                                $query->whereIn('asesor_id', $supervisedIds);
                            }

                            if ($user->hasAnyRole(['recolector', 'empaquetador', 'bodeguero'])) {
                                $bodegaIds = $user->bodegas->pluck('id');
                                $query->whereIn('bodega_id', $bodegaIds);
                            }
                        }
                    })
                    ->badge(
                        Orden::query()
                            ->where('estado', $estado->value)
                            ->where(function (Builder $query) use ($user) {
                                if (! $user->hasAnyRole(['administrador', 'super_admin', 'facturador', 'creditos', 'rrhh', 'gerente'])) {
                                    if ($user->hasAnyRole(User::ORDEN_ROLES)) {
                                        $query->where('asesor_id', $user->id);
                                    }

                                    if ($user->hasAnyRole(User::SUPERVISORES_ORDEN)) {
                                        $supervisedIds = $user->asesoresSupervisados->pluck('id');
                                        $query->whereIn('asesor_id', $supervisedIds);
                                    }

                                    if ($user->hasAnyRole(['recolector', 'empaquetador', 'bodeguero'])) {
                                        $bodegaIds = $user->bodegas->pluck('id');
                                        $query->whereIn('bodega_id', $bodegaIds);
                                    }
                                }
                            })
                            ->count()
                    )
                    ->badgeColor($estado->getColor()),
            ];
        })->toArray();
    } */

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('liquidarOrdenes')
                ->label('Liquidar Órdenes')
                ->action(fn () => OrdenController::liquidarVarias())
                ->color('success')
                ->visible(auth()->user()->can('liquidate_orden'))
                ->requiresConfirmation(),
        ];
    }
}
