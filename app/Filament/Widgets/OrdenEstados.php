<?php

namespace App\Filament\Widgets;

use App\Models\Guia;
use App\Models\Orden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;

class OrdenEstados extends BaseWidget
{
    protected static ?string $heading = 'Conteo de Órdenes por Estado';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];

    public static function canView(): bool
    {
        if (! Schema::hasTable('ordens')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
        }

        return auth()->user()->can('widget_OrdenEstados');
    }

    public function table(Table $table): Table
    {
        $query = Schema::hasTable('ordens')
 ? Orden::query() // Si la tabla 'ordens' EXISTE, usar la consulta normal
     ->when(
         Schema::hasTable('ordenes'), // Condición (redundante aquí, pero se deja por claridad, podrías simplificar)
         fn ($q) => $q
             ->whereRaw('1=1') //  <- PLACEHOLDER, REEMPLAZA CON LA CONSULTA REAL DE ORDENESTADOS!!!
     )
 : Guia::query()->whereRaw('1=0');

        return $table
            ->query($query
            )
            ->columns([
                TextColumn::make('estado')
                    ->label('Estado')
                    ->sortable(),
                TextColumn::make('cantidad')
                    ->label('Cantidad de Órdenes')
                    ->sortable()
                    ->numeric(),
                TextColumn::make('total')
                    ->label('Total de Órdenes')
                    ->sortable()
                    ->money('GTQ'),
            ])->paginated(false);
    }
}
