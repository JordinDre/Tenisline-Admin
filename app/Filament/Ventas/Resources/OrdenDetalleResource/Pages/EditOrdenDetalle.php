<?php

namespace App\Filament\Ventas\Resources\OrdenDetalleResource\Pages;

use App\Filament\Ventas\Resources\OrdenDetalleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenDetalle extends EditRecord
{
    protected static string $resource = OrdenDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Desactivar'),
        ];
    }
}
