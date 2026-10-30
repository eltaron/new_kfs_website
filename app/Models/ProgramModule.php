<?php

// Moved to \Modules\Estidama\Models\ProgramModule (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\ProgramModule') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(ProgramModule::class, false)) {
    class_alias(\Modules\Estidama\Models\ProgramModule::class, ProgramModule::class);
}
