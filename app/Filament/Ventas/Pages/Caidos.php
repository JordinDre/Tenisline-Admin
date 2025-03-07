<?php

namespace App\Filament\Ventas\Pages;

use Filament\Pages\Page;

class Caidos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-c-user-minus';

    protected static string $view = 'filament.ventas.pages.caidos';

    protected static ?string $title = 'Caídos';

    protected static ?string $navigationLabel = 'Caídos';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_caidos');
    }
}
