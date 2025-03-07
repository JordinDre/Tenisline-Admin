<?php

use App\Models\Orden;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::call(function () {
    Orden::where('estado', 'confirmada')->where('prefechado', '>', now())->update(['estado' => 'completada']);
    Orden::where('estado', 'completada')->where('prefechado', '<=', now())->update(['estado' => 'confirmada']);

    $dateNow = Carbon::now()->subDays(15);
    Orden::where('estado', 'cotizacion')
        ->where('created_at', '<=', $dateNow)
        ->update(['estado' => 'anulacion', 'motivo' => 'COTIZACIÓN VENCIDA']);
})->dailyAt('03:00');
