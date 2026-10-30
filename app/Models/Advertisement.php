<?php

// Moved to \Modules\Investment\Models\Advertisement (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Advertisement') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Advertisement::class, false)) {
    class_alias(\Modules\Investment\Models\Advertisement::class, Advertisement::class);
}
