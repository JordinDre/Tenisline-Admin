<?php

namespace App\Filament\Inventario\Widgets;

use App\Models\Inventario;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Utils\Functions;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class CostoInventario extends BaseWidget
{
    protected static ?int $sort = 3;

    /* protected int | string | array $columnSpan = 1; */

    protected ?string $heading = 'Costo Inventario';

    public static function canView(): bool
    {
        if (!Schema::hasTable('labors')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
             }
             
        return auth()->user()->can('widget_CostoInventario');
    }

    protected function getStats(): array
    {
        $bodegas = [1 => 'Zacapa', 2 => 'Capital', 3 => 'Mal Estado', 4 => 'Traslado', 5 => 'Abura'];

        // Obtener solo los inventarios con existencia mayor a 0
        $inventarios = Inventario::whereIn('bodega_id', array_keys($bodegas))
            ->where('existencia', '>', 0) // Filtrar solo los que tienen existencia mayor a 0
            ->with('producto')
            ->get();

        // Agrupar por bodega y calcular los costos
        $costos = $inventarios->groupBy('bodega_id')->map(
            fn ($items) => $items->sum(
                fn ($inventario) => ($inventario->producto->precio_compra + $inventario->producto->envase) * $inventario->existencia
            )
        );

        // Calcular el total
        $total = $costos->sum();

        // Crear el array de estadísticas
        $stats = collect($bodegas)->map(
            fn ($nombre, $id) => Stat::make("Costo $nombre", Functions::money($costos[$id] ?? 0))
        )->values();

        // Agregar el total
        $stats->push(Stat::make('Costo Total', Functions::money($total)));

        return $stats->all();
    }
}
