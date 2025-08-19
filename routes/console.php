<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('00:20');
Schedule::command('backup:run')->daily()->at('00:20');
