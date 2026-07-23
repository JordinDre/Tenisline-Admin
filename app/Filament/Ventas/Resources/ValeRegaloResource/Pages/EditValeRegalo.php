<?php

namespace App\Filament\Ventas\Resources\ValeRegaloResource\Pages;

use App\Filament\Ventas\Resources\ValeRegaloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValeRegalo extends EditRecord
{
    protected static string $resource = ValeRegaloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
