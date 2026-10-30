<?php

// Moved to \Modules\Content\Models\GovernorateDetail (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GovernorateDetail') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GovernorateDetail::class, false)) {
    class_alias(\Modules\Content\Models\GovernorateDetail::class, GovernorateDetail::class);
}
