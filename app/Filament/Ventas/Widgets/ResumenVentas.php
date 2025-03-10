<?php

namespace App\Filament\Ventas\Widgets;

use App\Models\Meta;
use App\Models\Venta;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Utils\Functions;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ResumenVentas extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '10s';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Resumen Ventas';

    public static function canView(): bool
    {

        if (!Schema::hasTable('ordens')) { // Reemplaza 'ordens' con el nombre real de tu tabla
            return false; // Si la tabla 'ordens' NO existe, NO mostrar el widget
             }

        return auth()->user()->can('widget_ResumenVentas');
    }

    protected function getStats(): array
    {
        if (!Schema::hasTable('ordens')) { 
            return [
             'labels' => [], // Labels vacíos para el gráfico
              'datasets' => [], // Datasets vacíos para el gráfico
              ];
              }

              
        $user = auth()->user();
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;
        $day = $this->filters['dia'] ?? null;

        $cantidadOrdenesMes = Venta::where('asesor_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('created_at', $day);
            })
            ->whereNotIn('estado', Venta::ESTADOS_EXCLUIDOS)
            ->count();

        $totalOrdenesMes = Venta::where('asesor_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('created_at', $day);
            })
            ->whereNotIn('estado', Venta::ESTADOS_EXCLUIDOS)
            ->sum('total');

        $clientesVendidosMes = Venta::where('asesor_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->when($day, function ($query, $day) {
                return $query->whereDay('created_at', $day);
            })
            ->whereNotIn('estado', Venta::ESTADOS_EXCLUIDOS)
            ->distinct('cliente_id')
            ->count('cliente_id');

        $meta = Meta::where('user_id', $user->id)
            ->where('anio', $year)
            ->where('mes', $month)
            ->first();

        return [
            Stat::make('Cantidad de Ventas', $cantidadOrdenesMes)
                ->icon('heroicon-o-shopping-cart'),
            Stat::make('Total Venta', $totalOrdenesMes)
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Clientes Ventas', $clientesVendidosMes)
                ->icon('heroicon-o-user-group'),
            Stat::make('Meta', $meta ? Functions::money($meta->meta) : 0),
        ];
    }
}
