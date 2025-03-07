<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use App\Filament\Inventario\Resources\TrasladoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTraslado extends ViewRecord
{
    protected static string $resource = TrasladoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn ($record) => $record->estado->value == 'creado'),
        ];
    }
}
