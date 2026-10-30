<?php

// Moved to \Modules\Content\Models\OfficialRole (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\OfficialRole') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(OfficialRole::class, false)) {
    class_alias(\Modules\Content\Models\OfficialRole::class, OfficialRole::class);
}
