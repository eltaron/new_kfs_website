<?php

// Moved to \Modules\Citizen\Models\Complaint (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Complaint') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Complaint::class, false)) {
    class_alias(\Modules\Citizen\Models\Complaint::class, Complaint::class);
}
