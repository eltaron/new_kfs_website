<?php

// Moved to \Modules\Content\Models\Post (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Post') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Post::class, false)) {
    class_alias(\Modules\Content\Models\Post::class, Post::class);
}
