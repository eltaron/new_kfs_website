<?php

// Moved to \Modules\Estidama\Models\TrainingApplication (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\TrainingApplication') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(TrainingApplication::class, false)) {
    class_alias(\Modules\Estidama\Models\TrainingApplication::class, TrainingApplication::class);
}
