<?php

namespace App\Filament\Inventario\Widgets;

use App\Models\Bodega;
use App\Models\Inventario;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ExistenciaPorBodega extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Existencia por Bodega';

    public static function canView(): bool
    {
        if (! Schema::hasTable('inventarios')) {
            return false;
        }

        return Auth::user()?->hasAnyRole(['super_admin', 'administrador']) ?? false;
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('inventarios')) {
            return [];
        }

        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        // Obtener todas las bodegas con existencia
        $bodegas = Bodega::with(['municipio', 'departamento'])
            ->whereHas('inventario', function ($query) {
                $query->where('existencia', '>', 0);
            })
            ->get();

        // Orden personalizado de bodegas: 1, 5, 6, 7, 8, 9, 2, 3
        $ordenBodegas = [1, 5, 6, 7, 8, 9, 2, 3];

        // Ordenar las bodegas según el orden personalizado
        $bodegasOrdenadas = $bodegas->sortBy(function ($bodega) use ($ordenBodegas) {
            $posicion = array_search($bodega->id, $ordenBodegas);

            return $posicion !== false ? $posicion : 999; // Las bodegas no en la lista van al final
        });

        $stats = [];

        foreach ($bodegasOrdenadas as $bodega) {
            $existencia = Inventario::where('bodega_id', $bodega->id)
                ->whereHas('producto', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->sum('existencia');

            $productosUnicos = Inventario::where('bodega_id', $bodega->id)
                ->where('existencia', '>', 0)
                ->whereHas('producto', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->count();

            $ubicacion = $bodega->municipio ? $bodega->municipio->municipio : 'N/A';
            if ($bodega->departamento) {
                $ubicacion .= ', '.$bodega->departamento->departamento;
            }

            $stats[] = Stat::make($bodega->bodega, number_format($existencia).' pares')
                ->description($ubicacion)
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'data-tooltip' => "Productos únicos: {$productosUnicos}",
                ]);
        }

        // Agregar estadística total
        $totalExistencia = Inventario::whereHas('producto', function ($query) {
            $query->whereNull('deleted_at');
        })->sum('existencia');
        $totalProductos = Inventario::where('existencia', '>', 0)
            ->whereHas('producto', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->count();

        $stats[] = Stat::make('Total Existencia', number_format($totalExistencia).' pares')
            ->description("En {$totalProductos} productos únicos")
            ->descriptionIcon('heroicon-m-cube')
            ->color('success')
            ->chart([7, 2, 10, 3, 15, 4, 17]);

        return $stats;
    }
}
