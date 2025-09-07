<?php

namespace App\Filament\Ventas\Resources\CaidosResource\Pages;

use App\Filament\Ventas\Resources\CaidosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCaidos extends ListRecords
{
    protected static string $resource = CaidosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
