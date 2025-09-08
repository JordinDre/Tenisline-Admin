<?php

namespace App\Filament\Widgets;

use App\Models\Meta;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;

class MetasTiendaWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 'full',
        'xl' => 'full',
    ];

    public static function canView(): bool
    {
        return true; // Todos pueden ver este widget
    }

    protected function getHeading(): string
    {
        return 'Metas de Tienda';
    }

    protected function getStats(): array
    {
        $year = $this->filters['year'] ?? now()->year;
        $month = $this->filters['mes'] ?? now()->month;

        // Obtener solo metas de bodega (no de asesores)
        $metas = Meta::whereNotNull('bodega_id')
            ->whereNull('user_id')
            ->where('anio', $year)
            ->where('mes', $month)
            ->with('bodega')
            ->get();

        if ($metas->isEmpty()) {
            return [
                Stat::make('Metas de Tienda', 'Sin datos')
                    ->description('No hay metas configuradas para este mes')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        $stats = [];

        foreach ($metas as $meta) {
            $bodegaNombre = $meta->bodega ? $meta->bodega->bodega : 'Bodega #' . $meta->bodega_id;
            
            try {
                $ventasReales = $meta->ventas_reales;
                $alcance = $meta->alcance;
                $cumplida = $meta->cumplida;
            } catch (\Exception $e) {
                // Si hay error calculando ventas, usar valores por defecto
                $ventasReales = 0;
                $alcance = 0;
                $cumplida = false;
            }

            $stats[] = Stat::make($bodegaNombre, 'Q' . number_format($meta->meta, 2, '.', ','))
                ->description("Ventas: Q" . number_format($ventasReales, 2, '.', ',') . " | Alcance: " . $alcance . "%")
                ->descriptionIcon($cumplida ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($cumplida ? 'success' : ($alcance >= 80 ? 'warning' : 'danger'))
                ->chart([$alcance]);
        }

        return $stats;
    }

    protected function getColumns(): int
    {
        return 3; // Mostrar 3 columnas por fila
    }
}
