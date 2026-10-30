<?php

// Moved to \Modules\Payments\Models\Transaction (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\Transaction') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(Transaction::class, false)) {
    class_alias(\Modules\Payments\Models\Transaction::class, Transaction::class);
}
