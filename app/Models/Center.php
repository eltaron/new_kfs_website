<?php

// Moved to \Modules\Content\Models\Center (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Center') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Center::class, false)) {
    class_alias(\Modules\Content\Models\Center::class, Center::class);
}
