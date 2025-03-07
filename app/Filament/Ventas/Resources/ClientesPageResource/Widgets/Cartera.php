<?php

namespace App\Filament\Ventas\Resources\ClientesPageResource\Widgets;

use App\Models\Orden;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Cartera extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Cartera Clientes';

    public static function canView(): bool
    {
        return auth()->user()->can('widget_Cartera');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Clientes asignados
        $asignados = $user->clientes()->count();

        // Clientes nuevos con premio
        $nuevosConPremio = $user->clientes()
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereHas('ordenes', function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->where('total', '>', 500)
                    ->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS)
                    ->orderBy('created_at', 'asc'); // Asegura que sea la primera orden
            })->count();

        // Clientes nuevos sin premio
        $nuevosSinPremio = $user->clientes()
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereDoesntHave('ordenes', function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->where('total', '>', 500)
                    ->whereNotIn('estado', Orden::ESTADOS_EXCLUIDOS);
            })->count();

        return [
            Stat::make('Asignados', $asignados)
                ->icon('heroicon-o-user-group')
                ->color('purple'),
            Stat::make('Nuevos con Premio', $nuevosConPremio)
                ->icon('heroicon-o-user-group')
                ->color('green'),
            Stat::make('Nuevos Sin Premio', $nuevosSinPremio)
                ->icon('heroicon-o-user-group')
                ->color('red'),
        ];
    }
}
