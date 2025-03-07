<?php

namespace App\Filament\Inventario\Resources\MarcaResource\Pages;

use App\Filament\Inventario\Resources\MarcaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMarcas extends ManageRecords
{
    protected static string $resource = MarcaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
