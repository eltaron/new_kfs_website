<?php

// Moved to \Modules\Payments\Models\FulfillmentFee (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\FulfillmentFee') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(FulfillmentFee::class, false)) {
    class_alias(\Modules\Payments\Models\FulfillmentFee::class, FulfillmentFee::class);
}
