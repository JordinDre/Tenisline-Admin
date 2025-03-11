<?php

namespace App\Filament\Inventario\Widgets;

use App\Models\Guia;
use Filament\Tables;
use App\Models\Orden;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrdenesRecolectadas extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ordenes Recolectadas';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        if (!Schema::hasTable('ordens')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
             }
             
        return auth()->user()->can('widget_OrdenesRecolectadas');
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
        
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        return $table
            ->query($query
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Recolector')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Q'.number_format($state, 2)),
            ])->paginated(false);
    }
}
