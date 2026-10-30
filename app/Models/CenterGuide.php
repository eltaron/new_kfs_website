<?php

// Moved to \Modules\Investment\Models\CenterGuide (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\CenterGuide') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(CenterGuide::class, false)) {
    class_alias(\Modules\Investment\Models\CenterGuide::class, CenterGuide::class);
}
