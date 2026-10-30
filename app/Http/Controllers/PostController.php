<?php

// Moved to \Modules\Content\Http\Controllers\PostController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\PostController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(PostController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\PostController::class, PostController::class);
}
