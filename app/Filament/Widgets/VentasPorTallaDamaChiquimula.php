<?php

namespace App\Filament\Widgets;

use App\Models\VentaDetalle;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class VentasPorTallaDamaChiquimula extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Talla DAMA - Chiquimula';

    protected static ?int $sort = 7;

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
            ->where('ventas.bodega_id', 6) // Chiquimula
            ->where('productos.genero', 'DAMA')
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
        static::$heading = 'Ventas por Talla DAMA - Chiquimula (Total: Q'.number_format($totalDinero, 2).')';

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
            ['bg' => '#EC4899', 'border' => '#DB2777'], // pink
            ['bg' => '#F472B6', 'border' => '#EC4899'], // pink-400
            ['bg' => '#F9A8D4', 'border' => '#F472B6'], // pink-300
            ['bg' => '#FBCFE8', 'border' => '#F9A8D4'], // pink-200
            ['bg' => '#FCE7F3', 'border' => '#FBCFE8'], // pink-100
            ['bg' => '#FDF2F8', 'border' => '#FCE7F3'], // pink-50
            ['bg' => '#F43F5E', 'border' => '#E11D48'], // rose-500
            ['bg' => '#FB7185', 'border' => '#F43F5E'], // rose-400
            ['bg' => '#FDA4AF', 'border' => '#FB7185'], // rose-300
            ['bg' => '#FECDD3', 'border' => '#FDA4AF'], // rose-200
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
