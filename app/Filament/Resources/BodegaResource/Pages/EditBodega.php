<?php

namespace App\Filament\Resources\BodegaResource\Pages;

use App\Filament\Resources\BodegaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBodega extends EditRecord
{
    protected static string $resource = BodegaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()->label('Desactivar'),

            Actions\RestoreAction::make(),
        ];
    }
}
