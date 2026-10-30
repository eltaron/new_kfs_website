<?php

// Moved to \Modules\Tourism\Models\Landmark (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Landmark') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Landmark::class, false)) {
    class_alias(\Modules\Tourism\Models\Landmark::class, Landmark::class);
}
