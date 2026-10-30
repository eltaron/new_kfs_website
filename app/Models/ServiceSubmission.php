<?php

// Moved to \Modules\Citizen\Models\ServiceSubmission (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\ServiceSubmission') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(ServiceSubmission::class, false)) {
    class_alias(\Modules\Citizen\Models\ServiceSubmission::class, ServiceSubmission::class);
}
