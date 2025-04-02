<?php

namespace App\Filament\Ventas\Resources\TotalVentaResource\Pages;

use App\Filament\Ventas\Resources\TotalVentaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTotalVenta extends EditRecord
{
    protected static string $resource = TotalVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
