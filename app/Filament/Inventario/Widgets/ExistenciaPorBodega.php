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

        // Una sola consulta agregada por bodega (evita 3 queries por bodega + 2 al final)
        $porBodega = Inventario::query()
            ->join('productos', 'productos.id', '=', 'inventarios.producto_id')
            ->selectRaw('
                inventarios.bodega_id as bodega_id,
                SUM(CASE WHEN productos.deleted_at IS NULL THEN inventarios.existencia ELSE 0 END) as existencia_activos,
                SUM(CASE WHEN productos.deleted_at IS NOT NULL THEN inventarios.existencia ELSE 0 END) as existencia_anulados,
                COUNT(CASE WHEN productos.deleted_at IS NULL AND inventarios.existencia > 0 THEN 1 END) as productos_unicos
            ')
            ->groupBy('inventarios.bodega_id')
            ->get()
            ->filter(fn ($fila) => ((int) $fila->existencia_activos + (int) $fila->existencia_anulados) > 0)
            ->keyBy('bodega_id');

        // Obtener solo las bodegas con existencia (activa o anulada) registrada
        $bodegas = Bodega::with(['municipio', 'departamento'])
            ->whereIn('id', $porBodega->keys())
            ->get()
            ->keyBy('id');

        // Orden personalizado de bodegas: 1, 5, 6, 7, 8, 9, 2, 3
        $ordenBodegas = [1, 5, 6, 7, 8, 9, 2, 3];

        $bodegasOrdenadas = $bodegas->sortBy(function ($bodega) use ($ordenBodegas) {
            $posicion = array_search($bodega->id, $ordenBodegas);

            return $posicion !== false ? $posicion : 999; // Las bodegas no en la lista van al final
        });

        $stats = [];

        foreach ($bodegasOrdenadas as $bodega) {
            $fila = $porBodega->get($bodega->id);
            if (! $fila) {
                continue;
            }

            $existenciaActivos = (int) $fila->existencia_activos;
            $existenciaAnulados = (int) $fila->existencia_anulados;
            $productosUnicos = (int) $fila->productos_unicos;

            $ubicacion = $bodega->municipio ? $bodega->municipio->municipio : 'N/A';
            if ($bodega->departamento) {
                $ubicacion .= ', '.$bodega->departamento->departamento;
            }

            $existenciaTotal = $existenciaActivos + $existenciaAnulados;

            $stats[] = Stat::make($bodega->bodega, number_format($existenciaTotal).' pares')
                ->description('✅ Activos: '.number_format($existenciaActivos).' | ❌ Anulados: '.number_format($existenciaAnulados))
                ->descriptionIcon('heroicon-m-cube-transparent')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'data-tooltip' => "Productos únicos activos: {$productosUnicos} | Ubicación: {$ubicacion}",
                ]);
        }

        // Agregar estadística total (a partir de los mismos datos ya agregados)
        $totalExistenciaActivos = $porBodega->sum('existencia_activos');
        $totalExistenciaAnulados = $porBodega->sum('existencia_anulados');
        $totalProductos = $porBodega->sum('productos_unicos');
        $totalGeneral = $totalExistenciaActivos + $totalExistenciaAnulados;

        $stats[] = Stat::make('Total Existencia', number_format($totalGeneral).' pares')
            ->description('✅ Activos: '.number_format($totalExistenciaActivos).' | ❌ Anulados: '.number_format($totalExistenciaAnulados))
            ->descriptionIcon('heroicon-m-cube')
            ->color('success')
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->extraAttributes([
                'data-tooltip' => "Productos únicos activos: {$totalProductos}",
            ]);

        return $stats;
    }
}
