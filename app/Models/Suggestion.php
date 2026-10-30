<?php

// Moved to \Modules\Citizen\Models\Suggestion (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Suggestion') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Suggestion::class, false)) {
    class_alias(\Modules\Citizen\Models\Suggestion::class, Suggestion::class);
}
