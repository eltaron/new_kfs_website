<?php

// Moved to \Modules\Content\Models\Comment (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Comment') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Comment::class, false)) {
    class_alias(\Modules\Content\Models\Comment::class, Comment::class);
}
