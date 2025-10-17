<?php

namespace App\Filament\Widgets;

use App\Models\Bodega;
use App\Models\Meta;
use App\Models\VentaDetalle;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class MetasBodega extends Widget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Metas por Bodega';

    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.metas-bodega-table';

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 2,
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->can('widget_VentasGeneral');
    }

    protected function getViewData(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;
        $bodegaFilter = $this->filters['bodega'] ?? '';

        // Obtener datos de ventas por bodega
        $ventasData = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter, fn ($query, $bodega) => $query->where('bodegas.bodega', $bodega))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->selectRaw('
                bodegas.id as bodega_id,
                bodegas.bodega as bodega_nombre,
                SUM(venta_detalles.precio) as total,
                COUNT(DISTINCT ventas.cliente_id) as clientes,
                SUM(venta_detalles.cantidad) as cantidad_productos
            ')
            ->groupBy('bodegas.id', 'bodegas.bodega')
            ->get();

        // Obtener metas por bodega para el mes y año actual
        $metas = Meta::where('mes', $month)
            ->where('anio', $year)
            ->whereNotNull('bodega_id')
            ->pluck('meta', 'bodega_id')
            ->toArray();

        // Calcular días transcurridos y total de días del mes
        $diasTranscurridos = $day ? (int) $day : now()->day;
        $totalDiasMes = now()->setYear((int) $year)->setMonth((int) $month)->daysInMonth;

        $data = $ventasData->map(function ($item) use ($metas, $diasTranscurridos, $totalDiasMes) {
            $total = $item->total;
            $meta = $metas[$item->bodega_id] ?? 0;
            $alcance = $meta > 0 ? round(($total / $meta) * 100, 2) : 0;
            $proyeccion = $diasTranscurridos > 0 ? ($total / $diasTranscurridos) * $totalDiasMes : 0;
            $diferencia = $total - $meta;
            $eficiencia = ($diasTranscurridos > 0 && $meta > 0) ? ($total / $meta) * ($totalDiasMes / $diasTranscurridos) : 0;
            $proyeccion_porcentaje = $meta > 0 ? round(($proyeccion / $meta) * 100, 2) : 0;

            return [
                'bodega_id' => $item->bodega_id,
                'bodega' => $item->bodega_nombre,
                'total' => $total,
                'meta' => $meta,
                'alcance' => $alcance,
                'proyeccion' => $proyeccion,
                'proyeccion_porcentaje' => $proyeccion_porcentaje,
                'diferencia' => $diferencia,
                'eficiencia' => $eficiencia,
                'clientes' => $item->clientes,
                'cantidad_productos' => $item->cantidad_productos,
            ];
        })->sortByDesc('proyeccion_porcentaje'); // Ordenar por proyección en porcentaje de mayor a menor

        // Crear título dinámico basado en filtros
        $titulo = 'Metas por Bodega';
        if ($bodegaFilter) {
            $titulo .= " - {$bodegaFilter}";
        } else {
            $titulo .= ' - Todas las Bodegas';
        }

        static::$heading = $titulo;

        return [
            'data' => $data,
            'totalVentas' => $data->sum('total'),
            'totalMeta' => $data->sum('meta'),
            'totalAlcance' => $data->avg('alcance'),
            'totalProyeccion' => $data->sum('proyeccion'),
            'proyeccionPorcentajePromedio' => $data->avg('proyeccion_porcentaje'),
            'totalClientes' => $data->sum('clientes'),
            'totalProductos' => $data->sum('cantidad_productos'),
        ];
    }
}
