<?php

// Moved to \Modules\Tourism\Http\Controllers\BookingController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\BookingController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(BookingController::class, false)) {
    class_alias(\Modules\Tourism\Http\Controllers\BookingController::class, BookingController::class);
}
