<?php

namespace App\Filament\Widgets;

use App\Http\Controllers\Utils\Functions;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentasPorMarca extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Marca - Todas las Bodegas y Géneros';

    protected static ?int $sort = 4;

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
        $day = $this->filters['dia'] ?? '';
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $generoFilter = $this->filters['genero'] ?? '';

        // Agregación directa en SQL por marca
        $rows = DB::table('venta_detalles')
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('marcas', 'marcas.id', '=', 'productos.marca_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day !== '', fn($q) => $q->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter !== '', fn($q) => $q->where('bodegas.bodega', $bodegaFilter))
            ->when($generoFilter !== '', fn($q) => $q->where('productos.genero', $generoFilter))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->whereNotNull('marcas.marca')
            ->where('marcas.marca', '!=', '')
            ->selectRaw('
                marcas.marca as marca,
                SUM(venta_detalles.cantidad) as cantidad,
                SUM(venta_detalles.precio * venta_detalles.cantidad) as total,
                COUNT(DISTINCT ventas.cliente_id) as clientes
            ')
            ->groupBy('marcas.marca')
            ->orderBy('total', 'desc')
            ->get();

        // Título dinámico según filtros
        $titulo = 'Ventas por Marca';
        $titulo .= $bodegaFilter ? " - {$bodegaFilter}" : ' - Todas las Bodegas';
        $titulo .= $generoFilter ? " - {$generoFilter}" : ' - Todos los Géneros';
        static::$heading = $titulo;

        // Labels
        $labels = $rows->pluck('marca')->toArray();

        // Data arrays
        $cantidades = $rows->pluck('cantidad')->map(fn($v) => (int) $v)->toArray();
        $totales = $rows->pluck('total')->map(fn($v) => (float) $v)->toArray();
        $clientes = $rows->pluck('clientes')->map(fn($v) => (int) $v)->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad ' . number_format(array_sum($cantidades)),
                    'data' => $cantidades,
                    'backgroundColor' => '#8B5CF6', // violet
                    'borderWidth' => 0,
                ],
                [
                    'label' => 'Total ' . Functions::money(array_sum($totales)),
                    'data' => $totales,
                    'backgroundColor' => '#F59E0B', // amber
                    'borderWidth' => 0,
                ]
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
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false, // 👈 quita las líneas verticales
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false, // 👈 quita las líneas horizontales
                    ],
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
