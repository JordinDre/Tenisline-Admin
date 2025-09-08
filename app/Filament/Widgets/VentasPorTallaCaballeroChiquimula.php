<?php

namespace App\Filament\Widgets;

use App\Models\VentaDetalle;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class VentasPorTallaCaballeroChiquimula extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Talla CABALLERO - Chiquimula';

    protected static ?int $sort = 8;

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

        $data = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->where('ventas.bodega_id', 6) // Chiquimula
            ->where('productos.genero', 'CABALLERO')
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->whereNotNull('productos.talla')
            ->where('productos.talla', '!=', '')
            ->selectRaw('productos.talla, SUM(venta_detalles.cantidad) as total_cantidad, SUM(venta_detalles.precio) as total_precio')
            ->groupBy('productos.talla')
            ->get()
            ->sortBy(function ($item) {
                return $this->getTallaNumericValue($item->talla);
            });

        $labels = $data->pluck('talla')->map(function ($talla) {
            return "Talla {$talla}";
        })->toArray();

        $cantidades = $data->pluck('total_cantidad')->toArray();

        // Calcular total en dinero
        $totalDinero = $data->sum('total_precio');

        // Actualizar el heading con el total
        static::$heading = 'Ventas por Talla CABALLERO - Chiquimula (Total: Q'.number_format($totalDinero, 2).')';

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

    private function getTallaNumericValue(string $talla): float
    {
        // Limpiar la talla y convertir a número
        $talla = trim($talla);

        // Si es un número directo, devolverlo
        if (is_numeric($talla)) {
            return (float) $talla;
        }

        // Si contiene fracciones como "1/2", "1/3", etc.
        if (strpos($talla, '/') !== false) {
            $parts = explode('/', $talla);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }

        // Si contiene números con letras como "XS", "S", "M", "L", "XL", "XXL"
        $talla = strtoupper($talla);
        $tallaMap = [
            'XS' => 0.5,
            'S' => 1,
            'M' => 2,
            'L' => 3,
            'XL' => 4,
            'XXL' => 5,
            'XXXL' => 6,
        ];

        if (isset($tallaMap[$talla])) {
            return $tallaMap[$talla];
        }

        // Si contiene números con letras, extraer el número
        if (preg_match('/(\d+(?:\.\d+)?)/', $talla, $matches)) {
            return (float) $matches[1];
        }

        // Si no se puede convertir, devolver un valor alto para que aparezca al final
        return 999;
    }

    private function generateColors(int $count): array
    {
        $backgroundColors = [];
        $borderColors = [];

        $baseColors = [
            ['bg' => '#3B82F6', 'border' => '#1D4ED8'], // blue
            ['bg' => '#60A5FA', 'border' => '#3B82F6'], // blue-400
            ['bg' => '#93C5FD', 'border' => '#60A5FA'], // blue-300
            ['bg' => '#BFDBFE', 'border' => '#93C5FD'], // blue-200
            ['bg' => '#DBEAFE', 'border' => '#BFDBFE'], // blue-100
            ['bg' => '#EFF6FF', 'border' => '#DBEAFE'], // blue-50
            ['bg' => '#1E40AF', 'border' => '#1E3A8A'], // blue-800
            ['bg' => '#1D4ED8', 'border' => '#1E40AF'], // blue-700
            ['bg' => '#2563EB', 'border' => '#1D4ED8'], // blue-600
            ['bg' => '#3B82F6', 'border' => '#2563EB'], // blue-500
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
