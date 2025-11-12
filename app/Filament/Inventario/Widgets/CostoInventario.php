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

        // Obtener inventarios con productos activos
        $inventariosActivos = Inventario::whereIn('bodega_id', array_keys($bodegas))
            ->where('existencia', '>', 0)
            ->whereHas('producto', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->with('producto')
            ->get();

        // Obtener inventarios con productos anulados
        $inventariosAnulados = Inventario::whereIn('bodega_id', array_keys($bodegas))
            ->where('existencia', '>', 0)
            ->whereHas('producto', function ($query) {
                $query->whereNotNull('deleted_at');
            })
            ->with('producto')
            ->get();

        // Agrupar por bodega y calcular los costos de productos activos
        $costosActivos = $inventariosActivos->groupBy('bodega_id')->map(
            fn ($items) => $items->sum(
                fn ($inventario) => ($inventario->producto->precio_compra + $inventario->producto->envase) * $inventario->existencia
            )
        );

        // Agrupar por bodega y calcular los costos de productos anulados
        $costosAnulados = $inventariosAnulados->groupBy('bodega_id')->map(
            fn ($items) => $items->sum(
                fn ($inventario) => ($inventario->producto->precio_compra + $inventario->producto->envase) * $inventario->existencia
            )
        );

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
