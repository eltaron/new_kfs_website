<?php

// Moved to \Modules\Investment\Models\InvestmentPlan (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\InvestmentPlan') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(InvestmentPlan::class, false)) {
    class_alias(\Modules\Investment\Models\InvestmentPlan::class, InvestmentPlan::class);
}
