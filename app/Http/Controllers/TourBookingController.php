<?php

// Moved to \Modules\Tourism\Http\Controllers\TourBookingController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\TourBookingController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(TourBookingController::class, false)) {
    class_alias(\Modules\Tourism\Http\Controllers\TourBookingController::class, TourBookingController::class);
}
