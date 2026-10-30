<?php

// Moved to \Modules\Content\Http\Controllers\CommentController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\CommentController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(CommentController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\CommentController::class, CommentController::class);
}
