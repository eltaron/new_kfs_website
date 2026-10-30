<?php

// Moved to \Modules\Content\Http\Controllers\ExamResultController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ExamResultController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ExamResultController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\ExamResultController::class, ExamResultController::class);
}
