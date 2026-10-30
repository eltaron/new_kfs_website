<?php

// Moved to \Modules\Citizen\Http\Controllers\Api\VoiceComplaintController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\Api\VoiceComplaintController') working.
// Do not add code here.

namespace App\Http\Controllers\Api;

if (! class_exists(VoiceComplaintController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\Api\VoiceComplaintController::class, VoiceComplaintController::class);
}
