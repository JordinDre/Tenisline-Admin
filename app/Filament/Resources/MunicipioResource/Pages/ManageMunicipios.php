<?php

namespace App\Filament\Resources\MunicipioResource\Pages;

use App\Filament\Resources\MunicipioResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMunicipios extends ManageRecords
{
    protected static string $resource = MunicipioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
