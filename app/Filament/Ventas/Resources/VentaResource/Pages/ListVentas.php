<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Enums\EstadoVentaStatus;
use App\Filament\Ventas\Resources\VentaResource;
use App\Models\Venta;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    /* public function getTabs(): array
    {
        return collect(EstadoVentaStatus::cases())->mapWithKeys(function (EstadoVentaStatus $estado) {
            return [
                $estado->getLabel() => Tab::make()
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', $estado->value))
                    ->badge(Venta::query()->where('estado', $estado->value)->count())
                    ->badgeColor($estado->getColor()),
            ];
        })->toArray();
    } */

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
