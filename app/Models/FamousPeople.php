<?php

// Moved to \Modules\Content\Models\FamousPeople (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\FamousPeople') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(FamousPeople::class, false)) {
    class_alias(\Modules\Content\Models\FamousPeople::class, FamousPeople::class);
}
