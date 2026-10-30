<?php

// Moved to \Modules\Gis\Models\GisShiakha (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisShiakha') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisShiakha::class, false)) {
    class_alias(\Modules\Gis\Models\GisShiakha::class, GisShiakha::class);
}
