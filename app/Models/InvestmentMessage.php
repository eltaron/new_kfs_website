<?php

// Moved to \Modules\Investment\Models\InvestmentMessage (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\InvestmentMessage') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(InvestmentMessage::class, false)) {
    class_alias(\Modules\Investment\Models\InvestmentMessage::class, InvestmentMessage::class);
}
