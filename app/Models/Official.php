<?php

// Moved to \Modules\Content\Models\Official (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Official') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Official::class, false)) {
    class_alias(\Modules\Content\Models\Official::class, Official::class);
}
