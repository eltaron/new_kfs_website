<?php

// Moved to \Modules\Estidama\Models\Enrollment (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Enrollment') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Enrollment::class, false)) {
    class_alias(\Modules\Estidama\Models\Enrollment::class, Enrollment::class);
}
