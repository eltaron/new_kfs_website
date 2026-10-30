<?php

// Moved to \Modules\Content\Http\Controllers\DirectoryController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\DirectoryController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(DirectoryController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\DirectoryController::class, DirectoryController::class);
}
