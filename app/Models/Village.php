<?php

// Moved to \Modules\Content\Models\Village (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Village') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Village::class, false)) {
    class_alias(\Modules\Content\Models\Village::class, Village::class);
}
