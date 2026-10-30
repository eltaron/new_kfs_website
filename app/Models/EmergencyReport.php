<?php

// Moved to \Modules\Citizen\Models\EmergencyReport (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\EmergencyReport') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(EmergencyReport::class, false)) {
    class_alias(\Modules\Citizen\Models\EmergencyReport::class, EmergencyReport::class);
}
