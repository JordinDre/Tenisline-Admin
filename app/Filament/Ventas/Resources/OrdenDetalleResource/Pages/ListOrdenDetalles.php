<?php

namespace App\Filament\Ventas\Resources\OrdenDetalleResource\Pages;

use App\Filament\Ventas\Resources\OrdenDetalleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdenDetalles extends ListRecords
{
    protected static string $resource = OrdenDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
