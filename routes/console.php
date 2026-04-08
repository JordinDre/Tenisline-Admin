<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('23:35');
Schedule::command('backup:run --only-db')->daily()->at('23:35');
