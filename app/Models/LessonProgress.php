<?php

// Moved to \Modules\Estidama\Models\LessonProgress (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\LessonProgress') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(LessonProgress::class, false)) {
    class_alias(\Modules\Estidama\Models\LessonProgress::class, LessonProgress::class);
}
