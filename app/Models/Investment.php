<?php

// Moved to \Modules\Investment\Models\Investment (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Investment') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Investment::class, false)) {
    class_alias(\Modules\Investment\Models\Investment::class, Investment::class);
}
