<?php

namespace Database\Factories;

use Modules\Citizen\Models\ContactMessage;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactMessage> */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        $subject = DemoData::pick(['استفسار عن مواعيد العمل', 'الاستعلام عن طلب مقدم', 'طلب مقابلة', 'مشكلة في الدفع الإلكتروني', 'استفسار عن الخدمات المكانية']);
        $read = DemoData::chance(60);

        return [
            'name' => DemoData::name(),
            'email' => 'contact' . mt_rand(100, 999) . '@example.com',
            'phone' => DemoData::phone(),
            'subject' => $subject,
            'message' => 'السلام عليكم، ' . $subject . '. برجاء الإفادة وشكراً.',
            'is_read' => $read,
            'admin_reply' => $read && DemoData::chance(60) ? 'تم الرد على استفساركم هاتفياً. شكراً لتواصلكم.' : null,
            'replied_at' => $read ? now()->subDays(mt_rand(0, 10)) : null,
            'created_at' => DemoData::pastDate(60),
        ];
    }
}
