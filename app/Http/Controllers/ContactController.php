<?php

// Moved to \Modules\Citizen\Http\Controllers\ContactController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ContactController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ContactController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\ContactController::class, ContactController::class);
}
