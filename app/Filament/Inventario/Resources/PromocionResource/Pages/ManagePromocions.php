<?php

namespace App\Filament\Inventario\Resources\PromocionResource\Pages;

use App\Filament\Inventario\Resources\PromocionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePromocions extends ManageRecords
{
    protected static string $resource = PromocionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
