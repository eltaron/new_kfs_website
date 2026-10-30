<?php

namespace Modules\Tourism\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tourism\Models\TourBooking;
use Illuminate\Http\Request;

class TourBookingController extends Controller
{
    public function create()
    {
        return view('tour-bookings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tour_name' => 'required|string|max:255',
            'trip_date' => 'required|date',
            'trip_types' => 'nullable|array',
            'trip_types.*' => 'string|max:50',
            'people_count' => 'nullable|integer|min:1|max:10000',
            'meals_booking' => 'nullable|string|max:255',
            'desouk_to_fouh_details' => 'nullable|string|max:2000',
            'edfina_to_desouk_details' => 'nullable|string|max:2000',
        ]);

        $validated['trip_types'] = $validated['trip_types'] ?? [];

        // The cost is never taken from the client: the administration sets
        // total_cost after reviewing the request, then the company pays
        // through the signed follow-up link below.
        $booking = TourBooking::create($validated + [
            'status' => 'pending',
            'user_id' => $request->user()?->id,
        ]);

        return redirect()->back()
            ->with('success', 'تم إرسال طلب الحجز بنجاح! سيتم التواصل معكم قريباً.')
            ->with('booking_followup_url', $booking->checkoutUrl());
    }
}
