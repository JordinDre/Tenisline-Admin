<?php

namespace App\Filament\Ventas\Resources\TotalVentaResource\Pages;

use App\Filament\Ventas\Resources\TotalVentaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTotalVentas extends ListRecords
{
    protected static string $resource = TotalVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
