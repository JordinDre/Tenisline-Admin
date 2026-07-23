<?php

namespace App\Filament\Ventas\Resources\ValeRegaloResource\Pages;

use App\Filament\Ventas\Resources\ValeRegaloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValesRegalo extends ListRecords
{
    protected static string $resource = ValeRegaloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
