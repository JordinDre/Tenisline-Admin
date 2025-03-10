<?php

namespace App\Filament\Widgets;

use App\Models\Guia;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Filament\Tables\Columns\TextColumn;
use App\Http\Controllers\Utils\Functions;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class InformacionEnvios extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Información de Envíos';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        if (!Schema::hasTable('ordens')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
             }
             
        return auth()->user()->can('widget_InformacionEnvios');
    }

    public function table(Table $table): Table
    {

        if (!Schema::hasTable('ordens')) {
            // Si la tabla 'ordens' NO existe, retorna un Table VACÍO PERO VÁLIDO
            return $table
                ->query(Guia::query()->whereRaw('1=0')) // Query que NO retorna NADA (siempre falso)
                ->columns([]); // Sin columnas (tabla vacía visualmente);
        }


        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        return $table
            ->extremePaginationLinks()
            ->query(
                Guia::query()
                    ->selectRaw('
                    MIN(guias.id) as id, 
                    guias.tipo, 
                    ordens.tipo_envio, 
                    COUNT(*) as total_guias, 
                    SUM(ordens.total) as total
                ')
                    ->join('ordens', 'ordens.id', '=', 'guias.guiable_id')  // Suponiendo que guias.guiable_id corresponde a ordens.id
                    ->whereYear('guias.created_at', $year)
                    ->whereMonth('guias.created_at', $month)
                    ->when($day, fn ($query) => $query->whereDay('guias.created_at', $day))
                    ->groupBy('guias.tipo', 'ordens.tipo_envio')  // Agrupar por tipo de guía y tipo de envío
            )
            ->columns([
                TextColumn::make('tipo')
                    ->label('Tipo de Guía')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('tipo_envio')
                    ->label('Tipo de Envío')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('total_guias')
                    ->label('Cantidad de Guías')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->money('GTQ')
                    ->formatStateUsing(fn ($state) => Functions::money($state)),

            ])
            ->paginated(false);
    }
}
