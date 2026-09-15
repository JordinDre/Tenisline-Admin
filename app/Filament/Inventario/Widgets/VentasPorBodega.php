<?php

namespace App\Filament\Inventario\Widgets;

use App\Filament\Concerns\CalculaRangoFechas;
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
    use CalculaRangoFechas;

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

        [$inicio, $fin] = static::rangoFechas($year, $month, $day);

        // Una sola consulta agrupada por bodega en vez de 3 queries por bodega (N+1)
        $porBodega = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereBetween('ventas.created_at', [$inicio, $fin])
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->selectRaw('
                ventas.bodega_id as bodega_id,
                SUM(venta_detalles.cantidad) as cantidad_vendida,
                SUM(venta_detalles.precio) as total_ventas,
                COUNT(DISTINCT venta_detalles.producto_id) as productos_vendidos
            ')
            ->groupBy('ventas.bodega_id')
            ->get()
            ->keyBy('bodega_id');

        // Obtener solo las bodegas que efectivamente tuvieron ventas en el período
        $bodegas = Bodega::with(['municipio', 'departamento'])
            ->whereIn('id', $porBodega->keys())
            ->get()
            ->keyBy('id');

        $stats = [];

        foreach ($porBodega as $bodegaId => $fila) {
            $bodega = $bodegas->get($bodegaId);
            if (! $bodega) {
                continue;
            }

            $ubicacion = $bodega->municipio ? $bodega->municipio->municipio : 'N/A';
            if ($bodega->departamento) {
                $ubicacion .= ', '.$bodega->departamento->departamento;
            }

            $stats[] = Stat::make($bodega->bodega, number_format((int) $fila->cantidad_vendida).' pares')
                ->description('Q'.number_format((float) $fila->total_ventas, 2)." - {$fila->productos_vendidos} productos")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'data-tooltip' => "Ubicación: {$ubicacion}",
                ]);
        }

        // Agregar estadística total (a partir de los mismos datos ya agregados, sin nueva consulta completa)
        $totalCantidadVendida = $porBodega->sum('cantidad_vendida');
        $totalVentas = $porBodega->sum('total_ventas');
        $totalProductosVendidos = VentaDetalle::join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereBetween('ventas.created_at', [$inicio, $fin])
            ->whereIn('ventas.estado', ['creada', 'liquidada', 'parcialmente_devuelta'])
            ->where('venta_detalles.devuelto', 0)
            ->distinct('venta_detalles.producto_id')
            ->count('venta_detalles.producto_id');

        $stats[] = Stat::make('Total Vendido', number_format((int) $totalCantidadVendida).' pares')
            ->description('Q'.number_format((float) $totalVentas, 2)." - {$totalProductosVendidos} productos")
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color('warning')
            ->chart([7, 2, 10, 3, 15, 4, 17]);

        return $stats;
    }
}
