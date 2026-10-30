<?php

// Moved to \Modules\Estidama\Models\ProgramLesson (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\ProgramLesson') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(ProgramLesson::class, false)) {
    class_alias(\Modules\Estidama\Models\ProgramLesson::class, ProgramLesson::class);
}
