<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('23:35');
Schedule::command('backup:run --only-db')->daily()->at('23:35');
// --force: en producción el comando pide confirmación y, sin ella, se cancela sin borrar nada
Schedule::command('activitylog:clean --force')->daily()->at('23:45');
