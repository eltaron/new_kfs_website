<?php

// Moved to \Modules\Content\Http\Controllers\SearchController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\SearchController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(SearchController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\SearchController::class, SearchController::class);
}
