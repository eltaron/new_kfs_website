<?php

namespace Modules\Tourism\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tourism\Models\Booking;
use Modules\Tourism\Models\Landmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'landmark_id' => 'required|exists:landmarks,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'number_of_people' => 'required|integer|min:1',
            'visit_date' => 'required|date|after_or_equal:today',
            'visit_time' => 'nullable|date_format:H:i',
            'special_requests' => 'nullable|string',
        ]);

        $landmark = Landmark::findOrFail($validated['landmark_id']);

        $booking = Booking::create([
            'landmark_id' => $landmark->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'number_of_people' => $validated['number_of_people'],
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'] ?? null,
            'special_requests' => $validated['special_requests'] ?? null,
            'status' => 'pending',
            'user_id' => $request->user()?->id,
            // total_price is set by the administration; payment then goes
            // through the unified gateway via $booking->checkoutUrl().
        ]);

        // TODO: إرسال بريد تأكيد للعميل
        // Mail::to($booking->customer_email)->send(new BookingConfirmationMail($booking));

        // TODO: إرسال إشعار للإدارة
        // Mail::to('admin@example.com')->send(new NewBookingNotification($booking));

        return redirect()->back()
            ->with('success', 'تم إرسال طلب الحجز بنجاح! سنتواصل معك قريباً لتأكيد الحجز.')
            ->with('booking_followup_url', $booking->checkoutUrl());
    }
}