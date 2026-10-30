<?php

// Moved to \Modules\Investment\Http\Controllers\InvestmentController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\InvestmentController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(InvestmentController::class, false)) {
    class_alias(\Modules\Investment\Http\Controllers\InvestmentController::class, InvestmentController::class);
}
