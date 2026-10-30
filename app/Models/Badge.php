<?php

// Moved to \Modules\Estidama\Models\Badge (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Badge') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Badge::class, false)) {
    class_alias(\Modules\Estidama\Models\Badge::class, Badge::class);
}
