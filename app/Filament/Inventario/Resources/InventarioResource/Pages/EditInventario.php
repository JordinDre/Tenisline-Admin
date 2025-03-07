<?php

namespace App\Filament\Inventario\Resources\InventarioResource\Pages;

use App\Filament\Inventario\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventario extends EditRecord
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
        /* Actions\DeleteAction::make()->label('Desactivar'), */];
    }
}
