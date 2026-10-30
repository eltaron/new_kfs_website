<?php

// Moved to \Modules\Content\Models\Partner (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Partner') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Partner::class, false)) {
    class_alias(\Modules\Content\Models\Partner::class, Partner::class);
}
