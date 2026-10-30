<?php

// Moved to \Modules\Tourism\Models\Booking (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Booking') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Booking::class, false)) {
    class_alias(\Modules\Tourism\Models\Booking::class, Booking::class);
}
