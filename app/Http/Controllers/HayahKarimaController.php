<?php

// Moved to \Modules\Investment\Http\Controllers\HayahKarimaController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\HayahKarimaController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(HayahKarimaController::class, false)) {
    class_alias(\Modules\Investment\Http\Controllers\HayahKarimaController::class, HayahKarimaController::class);
}
