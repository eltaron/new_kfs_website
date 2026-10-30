<?php

// Moved to \Modules\Estidama\Models\ExamSubmission (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\ExamSubmission') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(ExamSubmission::class, false)) {
    class_alias(\Modules\Estidama\Models\ExamSubmission::class, ExamSubmission::class);
}
