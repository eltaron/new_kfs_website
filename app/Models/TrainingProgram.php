<?php

// Moved to \Modules\Estidama\Models\TrainingProgram (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\TrainingProgram') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(TrainingProgram::class, false)) {
    class_alias(\Modules\Estidama\Models\TrainingProgram::class, TrainingProgram::class);
}
