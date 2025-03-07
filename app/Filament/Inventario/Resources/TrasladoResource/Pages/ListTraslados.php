<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use App\Enums\EstadoTrasladoStatus;
use App\Filament\Inventario\Resources\TrasladoResource;
use App\Models\Traslado;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTraslados extends ListRecords
{
    protected static string $resource = TrasladoResource::class;

    public function getTabs(): array
    {
        $user = auth()->user();
        $bodegasIds = $user->bodegas->pluck('id');

        return collect(EstadoTrasladoStatus::cases())->mapWithKeys(function (EstadoTrasladoStatus $estado) use ($bodegasIds) {
            return [
                $estado->getLabel() => Tab::make()
                    ->modifyQueryUsing(function (Builder $query) use ($estado, $bodegasIds) {
                        $query->where('estado', $estado->value)
                            ->where(function ($q) use ($bodegasIds) {
                                $q->whereIn('entrada_id', $bodegasIds)
                                    ->orWhereIn('salida_id', $bodegasIds);
                            });
                    })
                    ->badge(Traslado::query()
                        ->where('estado', $estado->value)
                        ->where(function ($q) use ($bodegasIds) {
                            $q->whereIn('entrada_id', $bodegasIds)
                                ->orWhereIn('salida_id', $bodegasIds);
                        })
                        ->count()
                    )
                    ->badgeColor($estado->getColor()),
            ];
        })->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
