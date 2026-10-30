<?php

// Moved to \Modules\RemovalGovernance\Models\RemovalOrder (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\RemovalOrder') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(RemovalOrder::class, false)) {
    class_alias(\Modules\RemovalGovernance\Models\RemovalOrder::class, RemovalOrder::class);
}
