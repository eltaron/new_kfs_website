<?php

// Moved to \Modules\Investment\Http\Controllers\InvestmentContactController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\InvestmentContactController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(InvestmentContactController::class, false)) {
    class_alias(\Modules\Investment\Http\Controllers\InvestmentContactController::class, InvestmentContactController::class);
}
