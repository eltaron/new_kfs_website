<?php

// Moved to \Modules\Tourism\Models\Location (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Location') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Location::class, false)) {
    class_alias(\Modules\Tourism\Models\Location::class, Location::class);
}
