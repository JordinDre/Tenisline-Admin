<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentasPorMarca extends Widget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?string $heading = 'Ventas por Marca - Todas las Bodegas y Géneros';

    protected static ?int $sort = 4;

    protected static string $view = 'filament.widgets.ventas-por-marca-table';

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
        $day = $this->filters['dia'] ?? '';
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $generoFilter = $this->filters['genero'] ?? '';

        // Título dinámico según filtros
        $titulo = 'Ventas por Marca';
        $titulo .= $bodegaFilter ? " - {$bodegaFilter}" : ' - Todas las Bodegas';
        $titulo .= $generoFilter ? " - {$generoFilter}" : ' - Todos los Géneros';
        static::$heading = $titulo;

        // Obtener datos agrupados
        $data = DB::table('venta_detalles')
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('marcas', 'marcas.id', '=', 'productos.marca_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day !== '', fn ($q) => $q->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter !== '', fn ($q) => $q->where('bodegas.bodega', $bodegaFilter))
            ->when($generoFilter !== '', fn ($q) => $q->where('productos.genero', $generoFilter))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->whereNotNull('marcas.marca')
            ->where('marcas.marca', '!=', '')
            ->selectRaw('
                marcas.marca as marca,
                SUM(venta_detalles.cantidad) as cantidad,
                SUM(venta_detalles.precio * venta_detalles.cantidad) as total,
                COUNT(DISTINCT ventas.cliente_id) as clientes,
                AVG(venta_detalles.precio) as precio_promedio,
                SUM(venta_detalles.cantidad * COALESCE(productos.precio_costo, 0)) as costo_total
            ')
            ->groupBy('marcas.marca')
            ->orderBy('total', 'desc')
            ->get();

        return [
            'data' => $data,
            'totalVentas' => $data->sum('total'),
            'totalCantidad' => $data->sum('cantidad'),
            'totalClientes' => $data->sum('clientes'),
            'totalMarcas' => $data->count(),
            'rentabilidadPromedio' => $data->avg(function ($item) {
                return $item->costo_total > 0 ? (($item->total - $item->costo_total) / $item->total) * 100 : 0;
            }),
        ];
    }
}
