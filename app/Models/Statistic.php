<?php

// Moved to \Modules\Content\Models\Statistic (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Statistic') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Statistic::class, false)) {
    class_alias(\Modules\Content\Models\Statistic::class, Statistic::class);
}
