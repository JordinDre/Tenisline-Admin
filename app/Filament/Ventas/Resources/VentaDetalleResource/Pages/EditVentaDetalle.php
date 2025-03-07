<?php

namespace App\Filament\Ventas\Resources\VentaDetalleResource\Pages;

use App\Filament\Ventas\Resources\VentaDetalleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVentaDetalle extends EditRecord
{
    protected static string $resource = VentaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Desactivar'),
        ];
    }
}
