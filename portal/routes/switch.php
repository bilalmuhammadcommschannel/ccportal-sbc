<?php

use App\Http\Controllers\SwitchController;
use Illuminate\Support\Facades\Route;

// Switch-facing call-control endpoints. Registered in bootstrap/app.php under the
// SwitchAuth middleware (loopback + shared secret); NOT in the web group, so no
// session and no CSRF. All handlers use parameterised queries only.
//
// The Asterisk chan_sip media anchor drives routing + billing through the AGI
// (server/var/lib/asterisk/agi-bin/cc-route.py -> /switch/route, cc-cdr.py ->
// /switch/cdr) with GET requests, so those two accept GET as well as POST.
// dialplan/directory/event are the legacy FreeSWITCH mod_xml_curl hooks, kept for
// compatibility but unused by the Asterisk stack.
Route::post('/switch/dialplan',  [SwitchController::class, 'dialplan'])->name('switch.dialplan');
Route::match(['get', 'post'], '/switch/route', [SwitchController::class, 'route'])->name('switch.route');
Route::post('/switch/directory', [SwitchController::class, 'directory'])->name('switch.directory');
Route::post('/switch/event',     [SwitchController::class, 'event'])->name('switch.event');
Route::match(['get', 'post'], '/switch/cdr',   [SwitchController::class, 'cdr'])->name('switch.cdr');
