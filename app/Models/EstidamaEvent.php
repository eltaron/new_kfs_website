<?php

// Moved to \Modules\Estidama\Models\EstidamaEvent (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\EstidamaEvent') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(EstidamaEvent::class, false)) {
    class_alias(\Modules\Estidama\Models\EstidamaEvent::class, EstidamaEvent::class);
}
