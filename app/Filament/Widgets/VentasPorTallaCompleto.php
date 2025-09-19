<?php

namespace App\Filament\Widgets;

use App\Http\Controllers\Utils\Functions;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentasPorTallaCompleto extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';
    protected static ?string $heading = 'Ventas por Talla - Todas las Bodegas y Géneros';
    protected static ?int $sort = 5;

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
        $year         = $this->filters['year']   ?? now()->year;
        $month        = $this->filters['mes']    ?? now()->month;
        $day          = $this->filters['dia']    ?? '';      // '' = todos
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $generoFilter = $this->filters['genero'] ?? '';

        // Agregación directa en SQL por talla
        $rows = DB::table('venta_detalles')
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day !== '', fn($q) => $q->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter !== '', fn($q) => $q->where('bodegas.bodega', $bodegaFilter))
            ->when($generoFilter !== '', fn($q) => $q->where('productos.genero', $generoFilter))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->whereNotNull('productos.talla')
            ->where('productos.talla', '!=', '')
            ->groupBy('productos.talla')
            ->selectRaw("
                productos.talla                                   as talla,
                SUM(venta_detalles.cantidad)                      as cantidad,
                SUM(venta_detalles.precio * venta_detalles.cantidad) as total,
                SUM(venta_detalles.cantidad * COALESCE(productos.precio_costo,0)) as costo
            ")
            ->get();

        // Ordena por valor numérico de talla
        $sorted = collect($rows)->sortBy(function ($r) {
            return $this->getTallaNumericValue($r->talla);
        })->values();

        // Título dinámico según filtros
        $titulo = 'Ventas por Talla';
        $titulo .= $bodegaFilter ? " - {$bodegaFilter}" : ' - Todas las Bodegas';
        $titulo .= $generoFilter ? " - {$generoFilter}" : ' - Todos los Géneros';
        static::$heading = $titulo;

        // Labels
        $labels = $sorted->pluck('talla')->map(fn($t) => "Talla {$t}")->toArray();

        // Data arrays
        $cantidades = $sorted->pluck('cantidad')->map(fn($v) => (int)$v)->toArray();
        $totales    = $sorted->pluck('total')->map(fn($v) => (float)$v)->toArray();
        $clientes   = $sorted->pluck('clientes')->map(fn($v) => (int)$v)->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad ' . number_format(array_sum($cantidades)),
                    'data' => $cantidades,
                    'backgroundColor' => '#3B82F6',
                    'borderWidth' => 0, 
                ],
                [
                    'label' => 'Total ' . Functions::money(array_sum($totales)),
                    'data' => $totales,
                    'backgroundColor' => '#10B981',
                    'borderWidth' => 0,
                ],
            ],
        ];
    }

    protected function getOptions(): array
{
    return [
        'indexAxis' => 'x',
        'plugins' => [
            'legend' => ['position' => 'top'],
            'tooltip' => [
                'enabled' => true,
                'mode' => 'index',
                'intersect' => false,
                // ❌ No JS aquí
            ],
        ],
        'scales' => [
            'x' => [
                'stacked' => true,
                'grid' => ['display' => false],
            ],
            'y' => [
                'stacked' => true,
                'grid' => ['display' => false],
            ],
        ],
        'animation' => ['duration' => 1000, 'easing' => 'easeOutQuart'],
    ];
}


    protected function getType(): string
    {
        return 'bar';
    }

    private function getTallaNumericValue(string $talla): float
    {
        $talla = trim($talla);
        if (is_numeric($talla)) return (float) $talla;

        if (strpos($talla, '/') !== false) {
            $p = explode('/', $talla);
            if (count($p) === 2 && is_numeric($p[0]) && is_numeric($p[1])) {
                return (float) $p[0] / (float) $p[1];
            }
        }

        $map = ['XS' => 0.5, 'S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5, 'XXXL' => 6];
        $up = strtoupper($talla);
        if (isset($map[$up])) return $map[$up];

        if (preg_match('/(\d+(?:\.\d+)?)/', $talla, $m)) return (float)$m[1];

        return 999;
    }

    protected function getExtraAttributes(): array
{
    return [
        'x-data' => '{}',
        'x-init' => <<<JS
            (el) => {
                const ctx = el.querySelector('canvas').getContext('2d');
                if (ctx && el.__chart) { // __chart es la referencia al chart de Filament
                    el.__chart.options.plugins.tooltip.callbacks = {
                        title: function(context) {
                            return "👟 " + context[0].label;
                        },
                        label: function(context) {
                            let label = context.dataset.label || "";
                            if (label) label += ": ";
                            if (context.parsed.y !== null) {
                                if (label.includes("Total")) {
                                    label += "Q" + new Intl.NumberFormat("es-GT", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(context.parsed.y);
                                } else {
                                    label += new Intl.NumberFormat("es-GT").format(context.parsed.y);
                                }
                            }
                            return label;
                        }
                    };
                    el.__chart.update();
                }
            }
        JS,
    ];
}

}
