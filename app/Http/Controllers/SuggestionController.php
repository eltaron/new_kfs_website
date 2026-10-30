<?php

// Moved to \Modules\Citizen\Http\Controllers\SuggestionController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\SuggestionController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(SuggestionController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\SuggestionController::class, SuggestionController::class);
}
