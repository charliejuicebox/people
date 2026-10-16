<?php

use Illuminate\Support\Facades\Schedule;

// Perth and Bali are both UTC+8, so one schedule covers the group. Weekdays, business hours only.
Schedule::command('people:send-review-reminders')
    ->weekdays()->at('09:00')->timezone(config('people.timezone'));

Schedule::command('people:prune-records')
    ->monthlyOn(1, '03:00')->timezone(config('people.timezone'));
