<?php

namespace Database\Factories;

use Modules\Citizen\Models\Suggestion;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Suggestion> */
class SuggestionFactory extends Factory
{
    protected $model = Suggestion::class;

    public const SUBJECTS = [
        'إنشاء حديقة عامة بالقرية' => 'نقترح استغلال قطعة الأرض الفضاء بجوار مركز الشباب في إنشاء حديقة عامة للأطفال.',
        'تطبيق موبايل لمتابعة الطلبات' => 'نقترح إتاحة إشعارات على الموبايل عند تغير حالة الطلب.',
        'زيادة عدد سيارات جمع القمامة' => 'نقترح زيادة عدد السيارات وتحديد مواعيد ثابتة للجمع.',
        'مواقف منظمة للتوك توك' => 'نقترح تخصيص مواقف منظمة للتوك توك بجوار المدارس.',
        'مكتبة عامة إلكترونية' => 'نقترح إنشاء مكتبة إلكترونية بقصر الثقافة متاحة للطلاب.',
    ];

    public function definition(): array
    {
        $female = DemoData::chance(40);
        $subject = DemoData::pick(array_keys(self::SUBJECTS));
        $status = DemoData::pick(['new', 'new', 'under_review', 'implemented']);

        return [
            'name' => DemoData::name($female),
            'email' => DemoData::chance(50) ? 'idea' . mt_rand(100, 999) . '@example.com' : null,
            'phone' => DemoData::phone(),
            'national_id' => DemoData::nationalId($female),
            'subject' => $subject,
            'message' => self::SUBJECTS[$subject],
            'status' => $status,
            'admin_reply' => $status === 'new' ? null : 'شكراً لمقترحكم، تمت إحالته للإدارة المختصة للدراسة.',
            'replied_at' => $status === 'new' ? null : now()->subDays(mt_rand(0, 15)),
            'created_at' => DemoData::pastDate(90),
        ];
    }
}
