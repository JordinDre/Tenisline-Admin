<?php

namespace App\Filament\Inventario\Resources\TrasladoResource\Pages;

use App\Filament\Inventario\Resources\TrasladoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTraslado extends CreateRecord
{
    protected static string $resource = TrasladoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['emisor_id'] = auth()->user()->id;

        return $data;
    }
}
