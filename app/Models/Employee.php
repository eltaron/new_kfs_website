<?php

// Moved to \Modules\Content\Models\Employee (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Employee') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Employee::class, false)) {
    class_alias(\Modules\Content\Models\Employee::class, Employee::class);
}
