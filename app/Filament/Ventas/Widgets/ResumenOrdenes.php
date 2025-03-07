<?php

namespace App\Filament\Ventas\Widgets;

use App\Http\Controllers\Utils\Functions;
use App\Models\Meta;
use App\Models\Orden;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenOrdenes extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Resumen Órdenes';

    public static function canView(): bool
    {
        return auth()->user()->can('widget_ResumenOrdenes');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        $cantidadOrdenesMes = Orden::where('asesor_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('created_at', $day);
            })
            ->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS)
            ->count();

        $totalOrdenesMes = Orden::where('asesor_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('created_at', $day);
            })
            ->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS)
            ->sum('total');

        $clientesVendidosMes = Orden::where('asesor_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('created_at', $day);
            })
            ->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS)
            ->distinct('cliente_id')
            ->count('cliente_id');

        $meta = Meta::where('user_id', $user->id)
            ->where('anio', $year)
            ->where('mes', $month)
            ->first();

        return [
            Stat::make('Cantidad de Órdenes', $cantidadOrdenesMes)
                ->icon('heroicon-o-shopping-cart'),
            Stat::make('Total Órdenes', Functions::money($totalOrdenesMes))
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Clientes Órdenes', $clientesVendidosMes)
                ->icon('heroicon-o-user-group'),
            Stat::make('Meta', $meta ? Functions::money($meta->meta) : 0),
        ];
    }
}
