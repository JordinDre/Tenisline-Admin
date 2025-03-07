<?php

namespace App\Filament\Ventas\Widgets;

use App\Models\Orden;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecoleccionEmpaquetado extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Recolección y Empaquetado';

    public static function canView(): bool
    {
        return auth()->user()->can('widget_RecoleccionEmpaquetado');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        $recolectorOrdenesMes = Orden::where('recolector_id', $user->id)
            ->whereYear('fecha_fin_recolectada', $year)
            ->whereMonth('fecha_fin_recolectada', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('fecha_fin_recolectada', $day);
            })
            ->count();

        $empaquetadorOrdenesMes = Orden::where('empaquetador_id', $user->id)
            ->whereYear('fecha_preparada', $year)
            ->whereMonth('fecha_preparada', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('fecha_preparada', $day);
            })
            ->count();

        $ordenEnProceso = Orden::where('recolector_id', $user->id)
            ->where('estado', 'confirmada')
            ->first();

        return [
            Stat::make('Órdenes Recolectadas', $recolectorOrdenesMes),
            Stat::make('Órdenes Empaquetadas', $empaquetadorOrdenesMes),
            Stat::make('Órden en Proceso Recolección', $ordenEnProceso ? '#'.$ordenEnProceso->id : 'Sin Asignar'),
        ];
    }
}
