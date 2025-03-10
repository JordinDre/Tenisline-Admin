<?php

namespace App\Filament\Ventas\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

class Caidos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-c-user-minus';

    protected static string $view = 'filament.ventas.pages.caidos';

    protected static ?string $title = 'Caídos';

    protected static ?string $navigationLabel = 'Caídos';

    public static function canAccess(): bool
    {
        if (!Schema::hasTable('ordens')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO permitir el acceso a la página
           }
           
        return auth()->user()->can('view_any_caidos');
    }
}
