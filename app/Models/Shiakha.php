<?php

// Moved to \Modules\Content\Models\Shiakha (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Shiakha') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Shiakha::class, false)) {
    class_alias(\Modules\Content\Models\Shiakha::class, Shiakha::class);
}
