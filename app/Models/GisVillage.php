<?php

// Moved to \Modules\Gis\Models\GisVillage (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisVillage') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisVillage::class, false)) {
    class_alias(\Modules\Gis\Models\GisVillage::class, GisVillage::class);
}
