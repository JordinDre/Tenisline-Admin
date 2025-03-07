<?php

namespace App\Filament\Ventas\Resources\PagoResource\Pages;

use App\Filament\Ventas\Resources\PagoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPago extends ViewRecord
{
    protected static string $resource = PagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /* Actions\EditAction::make(), */
        ];
    }
}
