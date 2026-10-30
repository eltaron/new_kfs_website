<?php

namespace Modules\Estidama\Http\Controllers\Estidama;

use App\Http\Controllers\Controller;
use Modules\Estidama\Models\TrainingCertificate;
use App\Services\Lms\LmsAccess;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /** Printable (PDF-ready) certificate: holder or Estidama staff. */
    public function show(Request $request, string $serial)
    {
        $certificate = TrainingCertificate::with(['trainingProgram.trainingCenter', 'trainingProgram.instructor', 'user'])
            ->where('serial', $serial)->firstOrFail();

        $user = $request->user();
        abort_unless(
            (string) $certificate->user_id === (string) $user->getKey()
            || LmsAccess::canManageProgram($user, $certificate->training_program_id),
            403
        );

        return view('estidama.lms.certificate', ['certificate' => $certificate]);
    }

    /** Public verification page (QR target). */
    public function verify(Request $request, ?string $serial = null)
    {
        $serial = $serial ?: $request->query('serial');
        $serial = $serial ? strtoupper(trim((string) $serial)) : null;

        $certificate = null;
        if ($serial && preg_match('/^[A-Z0-9\-]{6,40}$/', $serial)) {
            $certificate = TrainingCertificate::with('trainingProgram.trainingCenter')->where('serial', $serial)->first();
        }

        return view('estidama.lms.verify', [
            'serial' => $serial,
            'certificate' => $certificate,
            'searched' => $serial !== null,
        ]);
    }
}
