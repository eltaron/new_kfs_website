<?php

// Moved to \Modules\Gis\Models\GisSubService (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisSubService') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisSubService::class, false)) {
    class_alias(\Modules\Gis\Models\GisSubService::class, GisSubService::class);
}
