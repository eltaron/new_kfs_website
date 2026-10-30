<?php

// Moved to \Modules\Estidama\Models\PartnerLogo (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\PartnerLogo') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(PartnerLogo::class, false)) {
    class_alias(\Modules\Estidama\Models\PartnerLogo::class, PartnerLogo::class);
}
