<?php

use App\Http\Controllers\SwitchController;
use Illuminate\Support\Facades\Route;

// FreeSWITCH call-control endpoints. Registered in bootstrap/app.php under the
// SwitchAuth middleware (loopback + shared secret); NOT in the web group, so no
// session and no CSRF. All handlers use parameterised queries only.
Route::post('/switch/dialplan', [SwitchController::class, 'dialplan'])->name('switch.dialplan');
Route::post('/switch/route',    [SwitchController::class, 'route'])->name('switch.route');
Route::post('/switch/directory', [SwitchController::class, 'directory'])->name('switch.directory');
Route::post('/switch/event',    [SwitchController::class, 'event'])->name('switch.event');
Route::post('/switch/cdr',      [SwitchController::class, 'cdr'])->name('switch.cdr');
