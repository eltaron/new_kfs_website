<?php

// Moved to \Modules\Estidama\Models\TrainingCenter (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\TrainingCenter') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(TrainingCenter::class, false)) {
    class_alias(\Modules\Estidama\Models\TrainingCenter::class, TrainingCenter::class);
}
