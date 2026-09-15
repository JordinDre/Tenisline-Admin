<?php

namespace App\Filament\Concerns;

use Carbon\Carbon;

trait CalculaRangoFechas
{
    /**
     * Calcula el rango [inicio, fin] para un año/mes/día, sargable con índice en created_at
     * (a diferencia de whereYear()/whereMonth(), que envuelven la columna en una función SQL
     * y fuerzan un escaneo completo de la tabla).
     */
    protected static function rangoFechas(int $year, int $month, ?int $day): array
    {
        if ($day) {
            $inicio = Carbon::create($year, $month, $day)->startOfDay();
            $fin = $inicio->copy()->endOfDay();
        } else {
            $inicio = Carbon::create($year, $month, 1)->startOfMonth();
            $fin = $inicio->copy()->endOfMonth();
        }

        return [$inicio, $fin];
    }
}
