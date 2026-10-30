<?php

namespace Database\Factories;

use Modules\Citizen\Models\Complaint;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Complaint> — شكاوى بمحتوى عربي واقعي (بدون Faker). */
class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public const SUBJECTS = [
        'تراكم القمامة بالشارع' => 'تتراكم القمامة أمام المنازل منذ أكثر من أسبوع دون رفع، برجاء التوجيه بسرعة الرفع.',
        'انقطاع مياه الشرب المتكرر' => 'تنقطع المياه يومياً من الساعة العاشرة صباحاً حتى المساء.',
        'كسر بماسورة صرف صحي' => 'يوجد كسر بماسورة الصرف الصحي أدى إلى طفح المياه بالشارع.',
        'إنارة الشارع معطلة' => 'أعمدة الإنارة بالشارع معطلة منذ شهر مما يعرض المارة للخطر.',
        'تأخر الرد على طلب التصالح' => 'تقدمت بطلب تصالح منذ ثلاثة أشهر ولم يتم الرد حتى الآن.',
        'إشغالات بالطريق العام' => 'المحلات تشغل الرصيف بالكامل مما يعيق حركة المشاة.',
        'سوء حالة الطريق' => 'الطريق المؤدي للقرية به حفر كثيرة ويحتاج إلى رصف.',
    ];

    public function definition(): array
    {
        $female = DemoData::chance(40);
        $subject = DemoData::pick(array_keys(self::SUBJECTS));
        $center = DemoData::pick(DemoData::centerNames());
        $status = DemoData::pick(['pending', 'pending', 'in_progress', 'resolved']);

        return [
            'name' => DemoData::name($female),
            'email' => DemoData::chance(60) ? 'citizen.mail' . mt_rand(100, 999) . '@example.com' : null,
            'phone' => DemoData::phone(),
            'national_id' => DemoData::nationalId($female),
            'subject' => $subject,
            'message' => self::SUBJECTS[$subject] . ' (مركز ' . $center . ' - ' . DemoData::pick(DemoData::VILLAGES[$center]) . ')',
            'attachment' => null,
            'status' => $status,
            'admin_reply' => $status === 'pending' ? null : 'تم التواصل مع الجهة المختصة وجاري حل المشكلة. شكراً لتواصلكم.',
            'replied_at' => $status === 'pending' ? null : now()->subDays(mt_rand(0, 20)),
            'created_at' => DemoData::pastDate(90),
        ];
    }
}
