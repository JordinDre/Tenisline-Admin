<?php

namespace App\Filament\Ventas\Resources\CaidosResource\Pages;

use App\Filament\Ventas\Resources\CaidosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCaidos extends EditRecord
{
    protected static string $resource = CaidosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
