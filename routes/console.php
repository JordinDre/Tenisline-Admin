<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('23:35');
Schedule::command('backup:run --only-db')->daily()->at('23:35');
// --force: en producción el comando pide confirmación y, sin ella, se cancela sin borrar nada
Schedule::command('activitylog:clean --force')->daily()->at('23:45');

// Deja listos los tamaños de las fotos que subieron los asesores durante el día (así el primer cliente no las genera)
Schedule::command('imagenes:preparar')->dailyAt('02:30')->withoutOverlapping();
