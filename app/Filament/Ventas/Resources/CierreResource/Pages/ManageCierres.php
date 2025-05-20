<?php

namespace App\Filament\Ventas\Resources\CierreResource\Pages;

use App\Filament\Ventas\Resources\CierreResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCierres extends ManageRecords
{
    protected static string $resource = CierreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
