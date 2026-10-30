<?php

// Moved to \Modules\Gis\Models\GisStudyReason (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisStudyReason') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisStudyReason::class, false)) {
    class_alias(\Modules\Gis\Models\GisStudyReason::class, GisStudyReason::class);
}
