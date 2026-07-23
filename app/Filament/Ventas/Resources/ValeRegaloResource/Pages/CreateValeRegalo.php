<?php

namespace App\Filament\Ventas\Resources\ValeRegaloResource\Pages;

use App\Filament\Ventas\Resources\ValeRegaloResource;
use Filament\Resources\Pages\CreateRecord;

class CreateValeRegalo extends CreateRecord
{
    protected static string $resource = ValeRegaloResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
