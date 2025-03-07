<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use App\Filament\Inventario\Resources\TrasladoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class EditTraslado extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = TrasladoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            /* Actions\DeleteAction::make()->label('Desactivar'),

            Actions\RestoreAction::make(), */
        ];
    }
}
