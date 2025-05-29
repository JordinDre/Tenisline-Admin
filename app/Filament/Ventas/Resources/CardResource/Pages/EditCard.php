<?php

namespace App\Filament\Ventas\Resources\CardResource\Pages;

use App\Filament\Ventas\Resources\CardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCard extends EditRecord
{
    protected static string $resource = CardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
