<?php

// Moved to \Modules\Gis\Models\GisReport (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisReport') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisReport::class, false)) {
    class_alias(\Modules\Gis\Models\GisReport::class, GisReport::class);
}
