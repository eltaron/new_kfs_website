<?php

// Moved to \Modules\Citizen\Models\ContactMessage (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\ContactMessage') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(ContactMessage::class, false)) {
    class_alias(\Modules\Citizen\Models\ContactMessage::class, ContactMessage::class);
}
