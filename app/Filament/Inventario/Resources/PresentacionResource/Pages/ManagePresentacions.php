<?php

namespace App\Filament\Inventario\Resources\PresentacionResource\Pages;

use App\Filament\Inventario\Resources\PresentacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePresentacions extends ManageRecords
{
    protected static string $resource = PresentacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
