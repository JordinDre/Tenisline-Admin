<?php

namespace App\Filament\Ventas\Resources\OrdenResource\Pages;

use App\Filament\Ventas\Resources\OrdenResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;

class ViewOrden extends ViewRecord
{
    protected static string $resource = OrdenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('total')->label('Post title')
            ]);
    }
}
