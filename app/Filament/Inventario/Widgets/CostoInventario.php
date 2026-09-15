<?php

namespace App\Filament\Inventario\Widgets;

use App\Http\Controllers\Utils\Functions;
use App\Models\Inventario;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class CostoInventario extends BaseWidget
{
    protected static ?int $sort = 3;

    /* protected int | string | array $columnSpan = 1; */

    protected ?string $heading = 'Costo Inventario';

    public static function canView(): bool
    {
        if (! Schema::hasTable('labors')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
        }

        return auth()->user()->can('widget_CostoInventario');
    }

    protected function getStats(): array
    {
        $bodegas = [1 => 'Zacapa', 2 => 'Capital', 3 => 'Mal Estado', 4 => 'Traslado', 5 => 'Abura'];

        // Agregación en SQL (SUM/GROUP BY) en vez de traer todas las filas a PHP para sumarlas ahí
        $porBodega = Inventario::query()
            ->join('productos', 'productos.id', '=', 'inventarios.producto_id')
            ->whereIn('inventarios.bodega_id', array_keys($bodegas))
            ->where('inventarios.existencia', '>', 0)
            ->selectRaw('
                inventarios.bodega_id as bodega_id,
                SUM(CASE WHEN productos.deleted_at IS NULL THEN productos.precio_compra * inventarios.existencia ELSE 0 END) as costo_activo,
                SUM(CASE WHEN productos.deleted_at IS NOT NULL THEN productos.precio_compra * inventarios.existencia ELSE 0 END) as costo_anulado
            ')
            ->groupBy('inventarios.bodega_id')
            ->get()
            ->keyBy('bodega_id');

        $costosActivos = $porBodega->map(fn ($fila) => (float) $fila->costo_activo);
        $costosAnulados = $porBodega->map(fn ($fila) => (float) $fila->costo_anulado);

        // Calcular el total
        $totalActivos = $costosActivos->sum();
        $totalAnulados = $costosAnulados->sum();
        $totalGeneral = $totalActivos + $totalAnulados;

        // Crear el array de estadísticas
        $stats = collect($bodegas)->map(function ($nombre, $id) use ($costosActivos, $costosAnulados) {
            $costoActivo = $costosActivos[$id] ?? 0;
            $costoAnulado = $costosAnulados[$id] ?? 0;
            $costoTotal = $costoActivo + $costoAnulado;

            return Stat::make("Costo $nombre", Functions::money($costoTotal))
                ->description('✅ Activos: '.Functions::money($costoActivo).' | ❌ Anulados: '.Functions::money($costoAnulado))
                ->descriptionIcon('heroicon-m-currency-dollar');
        })->values();

        // Agregar el total
        $stats->push(
            Stat::make('Costo Total', Functions::money($totalGeneral))
                ->description('✅ Activos: '.Functions::money($totalActivos).' | ❌ Anulados: '.Functions::money($totalAnulados))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
        );

        return $stats->all();
    }
}
