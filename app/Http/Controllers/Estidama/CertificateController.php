<?php

// Moved to \Modules\Estidama\Http\Controllers\Estidama\CertificateController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\Estidama\CertificateController') working.
// Do not add code here.

namespace App\Http\Controllers\Estidama;

if (! class_exists(CertificateController::class, false)) {
    class_alias(\Modules\Estidama\Http\Controllers\Estidama\CertificateController::class, CertificateController::class);
}
