<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Schedule::command('signup-bonus:assign')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('check-bonuses-validity')->everyFiveMinutes()->withoutOverlapping();
