<?php

namespace App\Filament\Widgets;

use App\Http\Controllers\Utils\Functions;
use App\Models\Bodega;
use App\Models\Meta;
use App\Models\VentaDetalle;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class MetasBodega extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Metas por Bodega';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->can('widget_VentasGeneral');
    }

    protected function getData(): array
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
                SUM(venta_detalles.precio) as total
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
        $diasTranscurridos = $day ? (int)$day : now()->day;
        $totalDiasMes = now()->setYear((int)$year)->setMonth((int)$month)->daysInMonth;

        $data = $ventasData->map(function ($item) use ($metas, $diasTranscurridos, $totalDiasMes) {
            $total = $item->total;
            $meta = $metas[$item->bodega_id] ?? 0; // Si no hay meta, usar 0
            $alcance = $meta > 0 ? round(($total * 100) / $meta, 2) : 0;
            
            // Calcular proyección: (ventas actuales / días transcurridos) * total de días del mes
            $proyeccion = $diasTranscurridos > 0 ? ($total / $diasTranscurridos) * $totalDiasMes : 0;

            return [
                'bodega' => $item->bodega_nombre,
                'total' => $total,
                'meta' => $meta,
                'alcance' => $alcance,
                'proyeccion' => $proyeccion,
            ];
        });

        // Crear título dinámico basado en filtros
        $titulo = 'Metas por Bodega';
        if ($bodegaFilter) {
            $titulo .= " - {$bodegaFilter}";
        } else {
            $titulo .= ' - Todas las Bodegas';
        }

        static::$heading = $titulo;

        return [
            'labels' => $data->pluck('bodega')->toArray(),
            'datasets' => [
                [
                    'label' => 'Meta '.Functions::money($data->sum('meta')),
                    'data' => $data->pluck('meta')->toArray(),
                    'backgroundColor' => '#10B981', // emerald-500
                    'borderColor' => '#34D399', // emerald-400
                ],
                [
                    'label' => 'Proyección '.Functions::money($data->sum('proyeccion')),
                    'data' => $data->pluck('proyeccion')->toArray(),
                    'backgroundColor' => '#F59E0B', // amber-500
                    'borderColor' => '#D97706', // amber-600
                ],
                [
                    'label' => 'Ventas '.Functions::money($data->sum('total')),
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#3B82F6', // blue-500
                    'borderColor' => '#1D4ED8', // blue-700
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'x',
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'title' => 'function(context) {
                            return "🏪 " + context[0].label;
                        }',
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                if (label.includes("Meta") || label.includes("Ventas") || label.includes("Proyección")) {
                                    label += "Q" + new Intl.NumberFormat("es-GT", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(context.parsed.y);
                                } else {
                                    label += new Intl.NumberFormat("es-GT").format(context.parsed.y) + "%";
                                }
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => false,
                ],
                'y' => [
                    'stacked' => false,
                    'beginAtZero' => true,
                ],
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeOutQuart',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
