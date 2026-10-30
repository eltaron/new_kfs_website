<?php

// Moved to \Modules\Investment\Models\Project (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Project') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Project::class, false)) {
    class_alias(\Modules\Investment\Models\Project::class, Project::class);
}
