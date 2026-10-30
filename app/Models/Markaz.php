<?php

// Moved to \Modules\Content\Models\Markaz (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Markaz') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Markaz::class, false)) {
    class_alias(\Modules\Content\Models\Markaz::class, Markaz::class);
}
