<?php

namespace Database\Seeders;

use Modules\Tourism\Models\Booking;
use Modules\Tourism\Models\Landmark;
use Modules\Tourism\Models\TourBooking;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/** حجوزات زيارة المعالم + حجوزات الرحلات السياحية لشركات السياحة، في كل الحالات وحالات الدفع. */
class TourismBookingSeeder extends Seeder
{
    public function run(): void
    {
        DemoData::seed(8008);
        $citizens = User::where('email', 'like', 'citizen%@demo.kfs.invalid')->get();
        $landmarks = Landmark::all();
        if ($citizens->isEmpty() || $landmarks->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'confirmed', 'confirmed', 'completed', 'cancelled'];

        if (Booking::count() < 25) {
            for ($i = Booking::count() + 1; $i <= 25; $i++) {
                $u = $citizens[($i * 11) % $citizens->count()];
                $status = $statuses[$i % count($statuses)];
                $people = mt_rand(1, 12);
                $price = in_array($status, ['confirmed', 'completed'], true) ? $people * 50 : null;
                $paid = $price && $status === 'completed' || ($price && $i % 3 === 0);
                Booking::create([
                    'landmark_id' => $landmarks[$i % $landmarks->count()]->id,
                    'user_id' => $u->id,
                    'customer_name' => $u->name,
                    'customer_email' => $u->email,
                    'customer_phone' => $u->phone,
                    'number_of_people' => $people,
                    'visit_date' => now()->addDays(mt_rand(-40, 40))->toDateString(),
                    'visit_time' => sprintf('%02d:00', mt_rand(9, 15)),
                    'special_requests' => DemoData::chance(30) ? 'نرجو توفير مرشد سياحي.' : null,
                    'status' => $status,
                    'total_price' => $price,
                    'payment_status' => $price ? ($paid ? 'paid' : 'pending') : 'unpaid',
                    'paid_at' => $paid ? now()->subDays(mt_rand(1, 20)) : null,
                    'created_at' => DemoData::pastDate(60),
                ]);
            }
        }

        if (TourBooking::count() < 12) {
            $companies = ['شركة النيل للسياحة', 'شركة الدلتا ترافيل', 'شركة البرلس للرحلات', 'شركة مصر للسياحة الداخلية'];
            $tours = ['رحلة مسار العائلة المقدسة', 'يوم في بلطيم', 'رحلة آثار فوه الإسلامية', 'جولة بحيرة البرلس'];
            for ($i = TourBooking::count() + 1; $i <= 12; $i++) {
                $u = $citizens[($i * 13) % $citizens->count()];
                $status = $statuses[$i % count($statuses)];
                $people = mt_rand(15, 50);
                $cost = in_array($status, ['confirmed', 'completed'], true) ? $people * 120 : null;
                $paid = $cost && ($status === 'completed' || $i % 2 === 0);
                TourBooking::create([
                    'user_id' => $u->id,
                    'company_name' => $companies[$i % count($companies)],
                    'tour_name' => $tours[$i % count($tours)],
                    'trip_date' => now()->addDays(mt_rand(-30, 45))->toDateString(),
                    'trip_types' => array_values(array_unique([DemoData::pick(['أثرى', 'تاريخي']), DemoData::pick(['شاطئ', 'ترفيهي'])])),
                    'people_count' => $people,
                    'meals_booking' => DemoData::pick(['غداء', 'إفطار وغداء', 'بدون']),
                    'desouk_to_fouh_details' => 'انتقال بأتوبيس سياحي من دسوق إلى فوه صباحاً.',
                    'edfina_to_desouk_details' => DemoData::chance(50) ? 'رحلة نيلية من إدفينا إلى دسوق.' : null,
                    'total_cost' => $cost,
                    'status' => $status,
                    'admin_notes' => $status === 'cancelled' ? 'ألغيت بناءً على طلب الشركة.' : null,
                    'payment_status' => $cost ? ($paid ? 'paid' : 'pending') : 'unpaid',
                    'paid_at' => $paid ? now()->subDays(mt_rand(1, 15)) : null,
                    'created_at' => DemoData::pastDate(60),
                ]);
            }
        }
    }
}
