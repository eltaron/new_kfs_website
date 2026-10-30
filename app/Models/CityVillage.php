<?php

// Moved to \Modules\Tourism\Models\CityVillage (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\CityVillage') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(CityVillage::class, false)) {
    class_alias(\Modules\Tourism\Models\CityVillage::class, CityVillage::class);
}
