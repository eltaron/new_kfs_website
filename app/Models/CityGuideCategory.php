<?php

// Moved to \Modules\Tourism\Models\CityGuideCategory (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\CityGuideCategory') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(CityGuideCategory::class, false)) {
    class_alias(\Modules\Tourism\Models\CityGuideCategory::class, CityGuideCategory::class);
}
