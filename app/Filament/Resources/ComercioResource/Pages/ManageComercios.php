<?php

namespace App\Filament\Resources\ComercioResource\Pages;

use App\Filament\Resources\ComercioResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageComercios extends ManageRecords
{
    protected static string $resource = ComercioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
