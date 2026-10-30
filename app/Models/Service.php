<?php

// Moved to \Modules\Citizen\Models\Service (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Service') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Service::class, false)) {
    class_alias(\Modules\Citizen\Models\Service::class, Service::class);
}
