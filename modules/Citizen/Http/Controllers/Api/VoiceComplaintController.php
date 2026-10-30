<?php

namespace Modules\Citizen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Citizen\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // To log incoming data

class VoiceComplaintController extends Controller
{
    public function store(Request $request)
    {
        // Never write the citizen's complaint text / personal data to the log files.
        Log::info('Received voice complaint', ['fields' => array_slice(array_map('strval', array_keys($request->all())), 0, 20)]);

        // Basic validation for the received data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            // We assume email and phone can be fetched from the logged-in user if available
            // and national_id might be required if the user is logged in
        ]);

        try {
            Complaint::create([
                'name' => $validated['name'],
                'email' => auth()->user()?->email ?? 'voice.submission@placeholder.com',
                'phone' => auth()->user()?->phone ?? '00000000000',
                'national_id' => auth()->user()?->national_id ?? '00000000000000',
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'status' => 'pending',
            ]);

            return response()->json(['message' => 'تم استلام شكواك بنجاح.']);
        } catch (\Exception $e) {
            Log::error('Failed to save voice complaint:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'حدث خطأ أثناء حفظ الشكوى. يرجى المحاولة مرة أخرى.'], 500);
        }
    }
}
