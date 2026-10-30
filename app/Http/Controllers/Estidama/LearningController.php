<?php

// Moved to \Modules\Estidama\Http\Controllers\Estidama\LearningController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\Estidama\LearningController') working.
// Do not add code here.

namespace App\Http\Controllers\Estidama;

if (! class_exists(LearningController::class, false)) {
    class_alias(\Modules\Estidama\Http\Controllers\Estidama\LearningController::class, LearningController::class);
}
