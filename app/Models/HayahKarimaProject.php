<?php

// Moved to \Modules\Investment\Models\HayahKarimaProject (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\HayahKarimaProject') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(HayahKarimaProject::class, false)) {
    class_alias(\Modules\Investment\Models\HayahKarimaProject::class, HayahKarimaProject::class);
}
