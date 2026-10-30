<?php

// Moved to \Modules\Investment\Http\Controllers\InvestmentPlanController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\InvestmentPlanController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(InvestmentPlanController::class, false)) {
    class_alias(\Modules\Investment\Http\Controllers\InvestmentPlanController::class, InvestmentPlanController::class);
}
