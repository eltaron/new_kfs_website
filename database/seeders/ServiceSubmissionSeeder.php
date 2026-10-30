<?php

namespace Database\Seeders;

use Modules\Payments\Models\FulfillmentFee;
use Modules\Citizen\Models\Service;
use Modules\Citizen\Models\ServiceSubmission;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * طلبات خدمات تجريبية من المواطنين في كل الحالات:
 * pending / requires_action / in_progress / completed / rejected × pending / paid / failed، مع حالات استيفاء.
 *
 * (النسخة السابقة كانت تفريغاً لصفوف حقيقية تشير لمستخدمين غير موجودين — استُبدلت ببيانات تجريبية متسقة.)
 */
class ServiceSubmissionSeeder extends Seeder
{
    public const COUNT = 40;

    public function run(): void
    {
        DemoData::seed(6006);
        $citizens = User::where('email', 'like', 'citizen%@demo.kfs.invalid')->get();
        $staff = User::where('email', DemoData::email('tech.center'))->first();
        $existing = ServiceSubmission::whereIn('user_id', $citizens->pluck('id'))->count();
        if ($citizens->isEmpty() || $existing >= self::COUNT) {
            return;
        }

        // الخدمات الفرعية فقط (التي لها أب) — الخدمة الرئيسية لا يُقدَّم عليها طلب
        $services = Service::whereNotNull('parent_id')->get();
        if ($services->isEmpty()) {
            return;
        }

        $scenarios = [
            ['pending', 'pending', 'none'], ['pending', 'paid', 'none'], ['in_progress', 'paid', 'none'], ['in_progress', 'paid', 'requested'],
            ['requires_action', 'paid', 'requested'], ['completed', 'paid', 'none'], ['completed', 'paid', 'completed'], ['rejected', 'paid', 'none'],
            ['pending', 'failed', 'none'], ['completed', 'paid', 'none'],
        ];
        $notes = [
            'requires_action' => 'برجاء رفع صورة واضحة من سند الملكية.',
            'rejected' => 'المستندات المرفقة غير مطابقة للاشتراطات.',
            'completed' => 'تم إنجاز الطلب ويمكن استلام المستند من المركز التكنولوجي.',
            'in_progress' => 'جاري المراجعة الفنية والمعاينة.',
        ];

        for ($i = $existing + 1; $i <= self::COUNT; $i++) {
            [$status, $pay, $fulfil] = $scenarios[($i - 1) % count($scenarios)];
            $service = $services[$i % $services->count()];
            $user = $citizens[($i * 3) % $citizens->count()];
            $area = mt_rand(2, 40);
            $category = DemoData::pick(['حي شرق', 'حي غرب', 'باقي المراكز', 'القري']);
            $data = [];
            foreach ((array) $service->form_fields as $field) {
                $name = $field['name'] ?? null;
                if (! $name) {
                    continue;
                }
                $data[$name] = match ($field['type'] ?? 'text') {
                    'file' => DemoData::image('service-attachments', $name . '-' . ($i % 6), 800, 500, 'DOCUMENT (DEMO)'),
                    'select' => $category,
                    default => in_array($name, ['area', 'days', 'length'], true) ? (string) $area : 'بيان تجريبي — ' . ($field['label'] ?? $name),
                };
            }
            $total = round((float) $service->calculateTotal($area, $category), 2);
            $created = DemoData::pastDate(120, 1);

            $submission = ServiceSubmission::create([
                'service_id' => $service->id,
                'user_id' => $user->id,
                'submitted_data' => $data,
                'status' => $status,
                'admin_notes' => $notes[$status] ?? null,
                'fulfillment_status' => $fulfil,
                'fulfillment_action' => $fulfil !== 'none' ? 'fee' : null,
                'fulfillment_reason' => $fulfil !== 'none' ? 'رسوم إضافية لفرق المساحة بعد المعاينة' : null,
                'fulfillment_fee_amount' => $fulfil !== 'none' ? 120 : null,
                'fulfillment_requested_by' => $fulfil !== 'none' ? $staff?->id : null,
                'fulfillment_requested_at' => $fulfil !== 'none' ? $created->copy()->addDays(2) : null,
                'fulfillment_completed_at' => $fulfil === 'completed' ? $created->copy()->addDays(4) : null,
                'total_amount' => $total,
                'payment_status' => $pay,
                'paid_at' => $pay === 'paid' ? $created->copy()->addHour() : null,
                'created_at' => $created,
                'updated_at' => $created->copy()->addDays(mt_rand(0, 5)),
            ]);

            if ($fulfil !== 'none') {
                FulfillmentFee::create([
                    'feeable_type' => 'service_submission',
                    'feeable_id' => (string) $submission->id,
                    'user_id' => $user->id,
                    'amount' => 120,
                    'reason' => 'رسوم استيفاء',
                    'status' => $fulfil === 'completed' ? FulfillmentFee::STATUS_PAID : FulfillmentFee::STATUS_PENDING,
                    'requested_by' => $staff?->id,
                    'paid_at' => $fulfil === 'completed' ? $created->copy()->addDays(4) : null,
                ]);
            }
        }
    }
}
