<?php

namespace App\Filament\Resources\BodegaResource\Pages;

use App\Filament\Resources\BodegaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBodegas extends ListRecords
{
    protected static string $resource = BodegaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
