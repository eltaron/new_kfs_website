<?php

// Moved to \Modules\Estidama\Models\TrainingCertificate (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\TrainingCertificate') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(TrainingCertificate::class, false)) {
    class_alias(\Modules\Estidama\Models\TrainingCertificate::class, TrainingCertificate::class);
}
