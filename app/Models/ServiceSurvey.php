<?php

// Moved to \Modules\Citizen\Models\ServiceSurvey (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\ServiceSurvey') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(ServiceSurvey::class, false)) {
    class_alias(\Modules\Citizen\Models\ServiceSurvey::class, ServiceSurvey::class);
}
