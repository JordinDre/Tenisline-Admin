<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class MarchamosChart extends Widget
{

   use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.marchamos-chart-view';

    protected static ?string $heading = 'Totales por Marchamo';

    protected function getViewData(): array
    {
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $genero       = $this->filters['genero'] ?? '';

        $query = Producto::query()
            ->whereNotNull('marchamo');

        if ($genero !== '') {
            $query->where('genero', $genero);
        }

        if ($bodegaFilter !== '') {
            if (is_numeric($bodegaFilter)) {
                $query->whereHas('inventario', function ($q) use ($bodegaFilter) {
                    $q->where('bodega_id', (int) $bodegaFilter);
                });
            } else {
                $query->whereHas('inventario.bodega', function ($q) use ($bodegaFilter) {
                    $q->where('bodega', $bodegaFilter);
                });
            }
        }

        $result = $query
            ->selectRaw("marchamo, SUM(COALESCE(precio_costo, 0)) as total")
            ->groupBy('marchamo')
            ->pluck('total', 'marchamo')
            ->toArray();

        return [
            'rojo'    => (float) ($result['rojo'] ?? 0),
            'naranja' => (float) ($result['naranja'] ?? 0),
            'celeste' => (float) ($result['celeste'] ?? 0),
            'total'   => (float) array_sum($result),
        ];
    }
}
