<?php

// Moved to \Modules\Content\Models\DirectoryEntry (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\DirectoryEntry') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(DirectoryEntry::class, false)) {
    class_alias(\Modules\Content\Models\DirectoryEntry::class, DirectoryEntry::class);
}
