<?php

namespace App\Filament\Ventas\Widgets;

use App\Models\Bodega;
use App\Models\Meta;
use App\Models\VentaDetalle;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class VentasTiendas extends Widget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Tienda';

    protected static ?int $sort = 2;

    protected static string $view = 'filament.ventas.widgets.ventas-tiendas-table';

    public static function canView(): bool
    {
        return false;
    }

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];

    protected function getViewData(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;
        $bodegaFilter = $this->filters['bodega'] ?? '';

        $user = Auth::user();
        $isAdmin = $user->hasRole('administrador');
        $bodegasUsuarioIds = $user->bodegas->pluck('id')->all();

        $ventasQuery = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->selectRaw('
                bodegas.id as bodega_id,
                bodegas.bodega as bodega_nombre,
                SUM(venta_detalles.precio) as total,
                SUM(venta_detalles.cantidad) as unidades_vendidas
            ')
            ->groupBy('bodegas.id', 'bodegas.bodega');

        if ($isAdmin) {
            $ventasQuery->when(
                $bodegaFilter,
                fn ($query, $bodega) => $query->where('bodegas.bodega', $bodega)
            );
        } else {
            if (! empty($bodegasUsuarioIds)) {
                $ventasQuery->whereIn('bodegas.id', $bodegasUsuarioIds);
                $ventasQuery->when(
                    $bodegaFilter,
                    fn ($query, $bodega) => $query->where('bodegas.bodega', $bodega)
                );
            } else {
                $ventasQuery->whereRaw('1 = 0');
            }
        }

        $ventasData = $ventasQuery->get();

        // Metas por bodega
        $metas = Meta::where('mes', $month)
            ->where('anio', $year)
            ->whereNotNull('bodega_id')
            ->pluck('meta', 'bodega_id')
            ->toArray();

        // Días transcurridos y total de días del mes
        $hoy = now();
        $totalDiasMes = $hoy->setYear((int) $year)->setMonth((int) $month)->daysInMonth;

        // Calcular días transcurridos
        if ($day) {
            // Si seleccionan un día específico, usar ese día
            $diasTranscurridos = (int) $day;
        } elseif ($year == $hoy->year && $month == $hoy->month) {
            // Si es el mes y año actual, usar los días transcurridos reales
            $diasTranscurridos = $hoy->day;
        } else {
            // Para meses pasados o futuros, usar todos los días del mes
            $diasTranscurridos = $totalDiasMes;
        }

        // Preparar datos para la gráfica
        $data = $ventasData->map(function ($item) use ($metas, $diasTranscurridos, $totalDiasMes) {
            $total = $item->total;
            $meta = $metas[$item->bodega_id] ?? 0;
            $alcance = $meta > 0 ? round(($total * 100) / $meta, 2) : 0;
            $proyeccion = $diasTranscurridos > 0 ? ($total / $diasTranscurridos) * $totalDiasMes : 0;
            $rendimiento = $meta > 0 ? round(($total * 100) / $meta, 2) : 0;
            $uni_proyectadas = (($item->unidades_vendidas / $diasTranscurridos) * $totalDiasMes);

            return [
                'bodega' => $item->bodega_nombre,
                'total' => $total,
                'meta' => $meta,
                'alcance' => $alcance,
                'proyeccion' => $proyeccion,
                'unidades_vendidas' => $item->unidades_vendidas,
                'unidades_proyectadas' => $uni_proyectadas,
                'rendimiento' => $rendimiento,
            ];
        })->sortByDesc('proyeccion'); // Ordenar por proyección de mayor a menor

        // Calcular totales generales
        $totalVentas = $data->sum('total');
        $totalMeta = $data->sum('meta');
        $totalProyeccion = $data->sum('proyeccion');
        $totalUnidadesVendidas = $data->sum('unidades_vendidas');
        $totalUnidadesProyectadas = $data->sum('unidades_proyectadas');
        $rendimientoPromedio = $data->avg('rendimiento');

        return [
            'data' => $data,
            'totalVentas' => $totalVentas,
            'totalMeta' => $totalMeta,
            'totalProyeccion' => $totalProyeccion,
            'totalUnidadesVendidas' => $totalUnidadesVendidas,
            'totalUnidadesProyectadas' => $totalUnidadesProyectadas,
            'rendimientoPromedio' => $rendimientoPromedio,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'bodegaFilter' => $bodegaFilter,
        ];
    }
}
