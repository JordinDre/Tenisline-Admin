<?php

namespace App\Filament\Inventario\Widgets;

use App\Models\Bodega;
use App\Models\VentaDetalle;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class VentasPorBodega extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Ventas por Bodega';

    public static function canView(): bool
    {
        if (! Schema::hasTable('venta_detalles')) {
            return false;
        }

        return Auth::user()?->hasAnyRole(['super_admin', 'administrador']) ?? false;
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('venta_detalles')) {
            return [];
        }

        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        // Obtener todas las bodegas que han tenido ventas
        $bodegas = Bodega::with(['municipio', 'departamento'])
            ->whereHas('ventas', function ($query) use ($year, $month, $day) {
                $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->when($day, fn ($q, $day) => $q->whereDay('created_at', $day))
                    ->whereIn('estado', ['creada', 'liquidada', 'parcialmente_devuelta']);
            })
            ->get();

        $stats = [];

        foreach ($bodegas as $bodega) {
            // Calcular ventas del período
            $ventasQuery = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                ->where('ventas.bodega_id', $bodega->id)
                ->whereYear('ventas.created_at', $year)
                ->whereMonth('ventas.created_at', $month)
                ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
                ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
                ->where('venta_detalles.devuelto', 0);

            $cantidadVendida = $ventasQuery->sum('cantidad');
            $totalVentas = $ventasQuery->sum('precio');
            $productosVendidos = $ventasQuery->distinct('producto_id')->count();

            $ubicacion = $bodega->municipio ? $bodega->municipio->municipio : 'N/A';
            if ($bodega->departamento) {
                $ubicacion .= ', '.$bodega->departamento->departamento;
            }

            $stats[] = Stat::make($bodega->bodega, number_format($cantidadVendida).' pares')
                ->description('Q'.number_format($totalVentas, 2)." - {$productosVendidos} productos")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'data-tooltip' => "Ubicación: {$ubicacion}",
                ]);
        }

        // Agregar estadística total
        $totalVentasQuery = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereYear('ventas.created_at', $year)
            ->whereMonth('ventas.created_at', $month)
            ->when($day, fn ($query, $day) => $query->whereDay('ventas.created_at', $day))
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0);

        $totalCantidadVendida = $totalVentasQuery->sum('cantidad');
        $totalVentas = $totalVentasQuery->sum('precio');
        $totalProductosVendidos = $totalVentasQuery->distinct('producto_id')->count();

        $stats[] = Stat::make('Total Vendido', number_format($totalCantidadVendida).' pares')
            ->description('Q'.number_format($totalVentas, 2)." - {$totalProductosVendidos} productos")
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color('warning')
            ->chart([7, 2, 10, 3, 15, 4, 17]);

        return $stats;
    }
}
