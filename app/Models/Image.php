<?php

// Moved to \Modules\Content\Models\Image (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Image') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Image::class, false)) {
    class_alias(\Modules\Content\Models\Image::class, Image::class);
}
