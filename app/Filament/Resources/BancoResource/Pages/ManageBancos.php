<?php

namespace App\Filament\Resources\BancoResource\Pages;

use App\Filament\Resources\BancoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBancos extends ManageRecords
{
    protected static string $resource = BancoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
