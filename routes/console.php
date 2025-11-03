<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('filament:optimize')->everyMinute();
Schedule::command('backup:clean')->daily()->at('00:20');
Schedule::command('backup:run')->daily()->at('00:20');
