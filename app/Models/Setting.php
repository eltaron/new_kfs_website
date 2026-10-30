<?php

// Moved to \Modules\Content\Models\Setting (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Setting') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Setting::class, false)) {
    class_alias(\Modules\Content\Models\Setting::class, Setting::class);
}
