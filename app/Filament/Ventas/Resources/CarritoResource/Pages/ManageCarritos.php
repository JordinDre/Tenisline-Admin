<?php

namespace App\Filament\Ventas\Resources\CarritoResource\Pages;

use App\Filament\Ventas\Resources\CarritoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCarritos extends ManageRecords
{
    protected static string $resource = CarritoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
