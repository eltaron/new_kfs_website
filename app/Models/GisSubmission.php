<?php

// Moved to \Modules\Gis\Models\GisSubmission (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\GisSubmission') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(GisSubmission::class, false)) {
    class_alias(\Modules\Gis\Models\GisSubmission::class, GisSubmission::class);
}
