<?php

// Moved to \Modules\Tourism\Models\TourBooking (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\TourBooking') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(TourBooking::class, false)) {
    class_alias(\Modules\Tourism\Models\TourBooking::class, TourBooking::class);
}
