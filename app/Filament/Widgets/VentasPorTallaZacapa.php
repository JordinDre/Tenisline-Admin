<?php

namespace App\Filament\Widgets;

use App\Models\VentaDetalle;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class VentasPorTallaZacapa extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Talla - Zacapa';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->can('widget_VentasGeneral');
    }

    protected function getData(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        $data = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->where('ventas.bodega_id', 1) // Zacapa
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->whereNotNull('productos.talla')
            ->where('productos.talla', '!=', '')
            ->selectRaw('productos.talla, SUM(venta_detalles.cantidad) as total_cantidad, SUM(venta_detalles.precio) as total_precio')
            ->groupBy('productos.talla')
            ->orderBy('productos.talla')
            ->get();

        $labels = $data->pluck('talla')->map(function ($talla) {
            return "Talla {$talla}";
        })->toArray();

        $cantidades = $data->pluck('total_cantidad')->toArray();

        // Calcular total en dinero
        $totalDinero = $data->sum('total_precio');

        // Actualizar el heading con el total
        static::$heading = 'Ventas por Talla - Zacapa (Total: Q'.number_format($totalDinero, 2).')';

        // Generar colores dinámicamente
        $colors = $this->generateColors(count($labels));

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad Vendida',
                    'data' => $cantidades,
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'borderWidth' => 2,
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
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
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

    private function generateColors(int $count): array
    {
        $backgroundColors = [];
        $borderColors = [];

        $baseColors = [
            ['bg' => '#3B82F6', 'border' => '#1D4ED8'], // blue
            ['bg' => '#EF4444', 'border' => '#DC2626'], // red
            ['bg' => '#10B981', 'border' => '#059669'], // emerald
            ['bg' => '#F59E0B', 'border' => '#D97706'], // amber
            ['bg' => '#8B5CF6', 'border' => '#7C3AED'], // violet
            ['bg' => '#EC4899', 'border' => '#DB2777'], // pink
            ['bg' => '#06B6D4', 'border' => '#0891B2'], // cyan
            ['bg' => '#84CC16', 'border' => '#65A30D'], // lime
            ['bg' => '#F97316', 'border' => '#EA580C'], // orange
            ['bg' => '#6366F1', 'border' => '#4F46E5'], // indigo
        ];

        for ($i = 0; $i < $count; $i++) {
            $colorIndex = $i % count($baseColors);
            $backgroundColors[] = $baseColors[$colorIndex]['bg'];
            $borderColors[] = $baseColors[$colorIndex]['border'];
        }

        return [
            'background' => $backgroundColors,
            'border' => $borderColors,
        ];
    }
}
