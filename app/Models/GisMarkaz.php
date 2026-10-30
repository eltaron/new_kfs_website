<?php

// Moved to \Modules\Gis\Models\GisMarkaz (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisMarkaz') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisMarkaz::class, false)) {
    class_alias(\Modules\Gis\Models\GisMarkaz::class, GisMarkaz::class);
}
