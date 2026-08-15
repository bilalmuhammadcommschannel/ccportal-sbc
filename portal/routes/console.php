<?php

use Illuminate\Support\Facades\Schedule;

// Release prepaid credit reservations left behind by calls that never produced a
// CDR. Runs often enough that a leaked hold cannot suppress a customer's credit
// for long, and is idempotent.
Schedule::command('cc:sweep-holds')->everyThirtyMinutes()->withoutOverlapping();
