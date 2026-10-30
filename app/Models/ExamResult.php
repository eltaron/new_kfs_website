<?php

// Moved to \Modules\Content\Models\ExamResult (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\ExamResult') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(ExamResult::class, false)) {
    class_alias(\Modules\Content\Models\ExamResult::class, ExamResult::class);
}
