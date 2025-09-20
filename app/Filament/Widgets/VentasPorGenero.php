<?php

namespace App\Filament\Widgets;

use App\Models\VentaDetalle;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class VentasPorGenero extends Widget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Género';

    protected static ?int $sort = 6;

    protected static string $view = 'filament.widgets.ventas-por-genero-table';

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
        $generoFilter = $this->filters['genero'] ?? '';

        // Obtener datos agrupados por género
        $dataAgrupada = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter, fn ($query, $bodega) => $query->where('bodegas.bodega', $bodega))
            ->when($generoFilter, fn ($query, $genero) => $query->where('productos.genero', $genero))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->whereNotNull('productos.genero')
            ->where('productos.genero', '!=', '')
            ->selectRaw('
                productos.genero as genero,
                SUM(venta_detalles.precio) as total,
                SUM(venta_detalles.cantidad) as cantidad,
                COUNT(DISTINCT ventas.cliente_id) as clientes,
                AVG(venta_detalles.precio) as precio_promedio,
                SUM(venta_detalles.cantidad * COALESCE(productos.precio_costo, 0)) as costo_total,
                COUNT(DISTINCT productos.id) as productos_unicos
            ')
            ->groupBy('productos.genero')
            ->orderByRaw('SUM(venta_detalles.precio) DESC')
            ->get();

        // Calcular totales generales
        $totalVentas = $dataAgrupada->sum('total');
        $totalCantidad = $dataAgrupada->sum('cantidad');
        $totalClientes = $dataAgrupada->sum('clientes');
        $totalCosto = $dataAgrupada->sum('costo_total');
        $rentabilidadPromedio = $totalVentas > 0 ? round((($totalVentas - $totalCosto) / $totalVentas) * 100, 2) : 0;
        $ticketPromedio = $totalClientes > 0 ? round($totalVentas / $totalClientes, 2) : 0;

        return [
            'data' => $dataAgrupada,
            'totalVentas' => $totalVentas,
            'totalCantidad' => $totalCantidad,
            'totalClientes' => $totalClientes,
            'totalCosto' => $totalCosto,
            'rentabilidadPromedio' => $rentabilidadPromedio,
            'ticketPromedio' => $ticketPromedio,
            'totalGeneros' => $dataAgrupada->count(),
            'totalProductosUnicos' => $dataAgrupada->sum('productos_unicos'),
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'bodegaFilter' => $bodegaFilter,
            'generoFilter' => $generoFilter,
        ];
    }
}
