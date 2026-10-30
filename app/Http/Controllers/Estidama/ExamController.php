<?php

// Moved to \Modules\Estidama\Http\Controllers\Estidama\ExamController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\Estidama\ExamController') working.
// Do not add code here.

namespace App\Http\Controllers\Estidama;

if (! class_exists(ExamController::class, false)) {
    class_alias(\Modules\Estidama\Http\Controllers\Estidama\ExamController::class, ExamController::class);
}
