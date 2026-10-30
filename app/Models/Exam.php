<?php

// Moved to \Modules\Estidama\Models\Exam (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Exam') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Exam::class, false)) {
    class_alias(\Modules\Estidama\Models\Exam::class, Exam::class);
}
