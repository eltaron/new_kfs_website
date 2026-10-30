<?php

// Moved to \Modules\Gis\Models\GisServiceType (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisServiceType') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisServiceType::class, false)) {
    class_alias(\Modules\Gis\Models\GisServiceType::class, GisServiceType::class);
}
