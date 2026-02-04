<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('23:35');
Schedule::command('backup:run')->daily()->at('23:35');
