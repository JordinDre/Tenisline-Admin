<?php

namespace App\Filament\Ventas\Widgets;

use App\Models\VentaDetalle;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class VentasBodega extends Widget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;

    protected static ?string $heading = 'Ventas por Empleado';

    protected static ?int $sort = 1;

    protected static string $view = 'filament.widgets.ventas.ventas-bodega-table';

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 2,
        'xl' => 2,
    ];

   /*  public static function canView(): bool
    {
        return Auth::check() && Auth::user()->can('widget_VentasGeneral');
    } */

    protected function getViewData(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;
        $bodegaFilter = $this->filters['bodega'] ?? '';
        $generoFilter = $this->filters['genero'] ?? '';

        $user = Auth::user();

        $bodegaIds = [];
        if ($user && !$user->hasAnyRole(['administrador', 'super_admin'])) {
            $bodegaIds = $user->bodegas()->pluck('bodegas.id')->toArray();
        }

        // Obtener datos agrupados por asesor para mostrar totales (agregado en SQL para no cargar todas las filas en memoria)
        $dataAgrupada = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->join('bodegas', 'ventas.bodega_id', '=', 'bodegas.id')
            ->join('users', 'ventas.asesor_id', '=', 'users.id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->when($bodegaFilter, fn ($query, $bodega) => $query->where('bodegas.bodega', $bodega))
            ->when($generoFilter, fn ($query, $genero) => $query->where('productos.genero', $genero))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->when(
                $user && !$user->hasAnyRole(['administrador', 'super_admin']),
                fn ($query) => $query->whereIn('ventas.bodega_id', $bodegaIds)
            )
            ->selectRaw('
                ventas.asesor_id as asesor_id,
                users.name as asesor,
                SUM(venta_detalles.precio) as total,
                COUNT(*) as cantidad,
                SUM(venta_detalles.cantidad * COALESCE(productos.precio_costo, 0)) as costo,
                COUNT(DISTINCT ventas.cliente_id) as clientes
            ')
            ->groupBy('ventas.asesor_id', 'users.name')
            ->get()
            ->map(function ($fila) {
                $total = (float) $fila->total;
                $costo = (float) $fila->costo;
                $clientes = (int) $fila->clientes;
                $rentabilidad = $costo > 0 && $total > 0 ? round(($total - $costo) / $total, 4) : 0;

                return [
                    'asesor' => $fila->asesor ?? 'Sin Asesor',
                    'total' => $total,
                    'cantidad' => (int) $fila->cantidad,
                    'costo' => $costo,
                    'rentabilidad' => $rentabilidad,
                    'clientes' => $clientes,
                    'ticket_promedio' => $clientes > 0 ? round($total / $clientes, 2) : 0,
                ];
            })
            ->sortByDesc('total'); // Ordenar por total de ventas de mayor a menor

        // Calcular totales generales
        $totalVentas = $dataAgrupada->sum('total');
        $totalCantidad = $dataAgrupada->sum('cantidad');
        $totalClientes = $dataAgrupada->sum('clientes');
        $totalCosto = $dataAgrupada->sum('costo');
        $rentabilidadPromedio = $dataAgrupada->avg('rentabilidad') * 100;
        $ticketPromedio = $totalClientes > 0 ? $totalVentas / $totalClientes : 0;

        return [
            'data' => $dataAgrupada,
            'totalVentas' => $totalVentas,
            'totalCantidad' => $totalCantidad,
            'totalClientes' => $totalClientes,
            'totalCosto' => $totalCosto,
            'rentabilidadPromedio' => $rentabilidadPromedio,
            'ticketPromedio' => $ticketPromedio,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'bodegaFilter' => $bodegaFilter,
            'generoFilter' => $generoFilter,
        ];
    }
}
