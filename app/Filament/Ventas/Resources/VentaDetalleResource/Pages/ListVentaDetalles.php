<?php

namespace App\Filament\Ventas\Resources\VentaDetalleResource\Pages;

use App\Filament\Ventas\Resources\VentaDetalleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVentaDetalles extends ListRecords
{
    protected static string $resource = VentaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
