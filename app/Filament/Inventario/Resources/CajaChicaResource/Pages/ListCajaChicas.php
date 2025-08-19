<?php

namespace App\Filament\Inventario\Resources\CajaChicaResource\Pages;

use App\Enums\CajaChicaStatus;
use App\Filament\Inventario\Resources\CajaChicaResource;
use App\Models\CajaChica;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCajaChicas extends ListRecords
{
    protected static string $resource = CajaChicaResource::class;

    public function getTabs(): array
    {
        return collect(CajaChicaStatus::cases())->mapWithKeys(function (CajaChicaStatus $estado) {
            return [
                $estado->getLabel() => Tab::make()
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', $estado->value))
                    ->badge(CajaChica::query()->where('estado', $estado->value)->count())
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
