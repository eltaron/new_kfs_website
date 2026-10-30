<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Citizen\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'id' => '019bbb46-a0c4-7373-a25b-d7853ad3b16c',
                'parent_id' => null,
                'title' => 'الخدمات المكانية ',
                'title_line_1' => null,
                'title_line_2' => null,
                'description' => '<p dir=\"rtl\"><strong>بوابتك لتوثيق عقارك رقمياً؛ خدمات المكاني تضمن لك تحديد الموقف الدقيق لمنشأتك من الحيز العمراني واشتراطات البناء، باستخدام أحدث تقنيات الرفع الجغرافي لضمان حماية أصولك العقارية ودعم مسيرة التنمية المستدامة في مراكز ومدن المحافظة.</strong></p>',
                'form_fields' => '[]',
                'base_price' => 0.00,
                'has_vat' => true,
                'martyr_stamp_fee' => 5.00,
                'sms_fee' => 10.00,
                'pricing_type' => 'fixed',
                'category_pricing' => null,
                'insurance_fee' => 0.00,
                'insurance_percentage' => 0.00,
                'link' => 'https://kfs.gov.eg/wait',
                'icon' => 'fas fa-building',
                'is_highlighted' => true,
                'created_at' => '2026-01-14 06:52:06',
                'updated_at' => '2026-05-03 11:18:16',
            ],
            [
                'id' => '019dd3ed-4573-7255-b27f-16fe7d4cc744',
                'parent_id' => null,
                'title' => 'ترخيص الإعلانات <br> (علي أملاك خاصة )',
                'title_line_1' => null,
                'title_line_2' => null,
                'description' => '<p dir=\"rtl\">&nbsp;تعد خدمة \"تراخيص الإعلانات\" هي البوابة الرسمية لتنظيم كافة الأنشطة الإعلانية داخل النطاق الجغرافي للمحافظة. تهدف هذه الخدمة إلى الحفاظ على المظهر الحضاري والجمالي للمدن، وضمان التزام كافة اللافتات والشاشات الإعلانية بمعايير السلامة الإنشائية والضوابط القانونية المعمول بها.</p><p dir=\"rtl\">من خلال هذه المنصة، يمكن للمنشآت التجارية، الشركات، والأفراد التقدم بطلبات استخراج تراخيص جديدة، أو تجديد التراخيص القائمة، أو تعديل مواصفات الإعلانات، مع الاستفادة من نظام تسعير عادل يعتمد على الموقع الجغرافي ونوع النشاط، وذلك لضمان شفافية الإجراءات وسرعة الحصول على الموافقات.</p><h3 dir=\"rtl\"><strong>أبرز مزايا الخدمة:</strong></h3><ul dir=\"rtl\"><li><strong>التنظيم الحضري:</strong> حماية النسيج البصري للمدينة من التلوث البصري والإعلانات العشوائية.</li><li><strong>نظام تسعير شفاف:</strong> احتساب دقيق للرسوم بناءً على الفئات المكانية والضوابط المعتمدة.</li><li><strong>سهولة التتبع:</strong> متابعة حالة الطلب إلكترونياً من مرحلة المعاينة الميدانية وحتى إصدار الترخيص.</li><li><strong>الامتثال القانوني:</strong> ضمان وضع الإعلانات في أماكن آمنة لا تعيق حركة المرور أو تؤثر على سلامة المنشآت.</li></ul><p>&nbsp;</p>',
                'form_fields' => '[]',
                'base_price' => 0.00,
                'has_vat' => true,
                'martyr_stamp_fee' => 5.00,
                'sms_fee' => 10.00,
                'pricing_type' => 'fixed',
                'category_pricing' => '[]',
                'insurance_fee' => 0.00,
                'insurance_percentage' => 0.00,
                'link' => null,
                'icon' => 'fas fa-ad',
                'is_highlighted' => false,
                'created_at' => '2026-04-28 11:50:35',
                'updated_at' => '2026-05-03 12:04:04',
            ],
            [
                'id' => '019dd3ed-4573-7255-b27f-16fe7d4cc745',
                'parent_id' => null,
                'title' => 'استدامة للتدريب والتطوير',
                'title_line_1' => null,
                'title_line_2' => null,
                'description' => '<p dir=\"rtl\">دورات تدريبية للطلبة وشباب الخريجين</p>',
                'form_fields' => null,
                'base_price' => 0.00,
                'has_vat' => true,
                'martyr_stamp_fee' => 5.00,
                'sms_fee' => 10.00,
                'pricing_type' => 'fixed',
                'category_pricing' => null,
                'insurance_fee' => 0.00,
                'insurance_percentage' => 0.00,
                'link' => 'https://kfs.gov.eg/wait',
                'icon' => 'fas fa-book',
                'is_highlighted' => false,
                'created_at' => '2026-02-03 13:42:12',
                'updated_at' => '2026-04-15 13:22:19',
            ],
            [
                'id' => '019ddd9e-5e14-71fb-915a-fe97b40028a4',
                'parent_id' => '019dd3ed-4573-7255-b27f-16fe7d4cc744',
                'title' => 'إصدار ترخيص إعلان جديد',
                'title_line_1' => null,
                'title_line_2' => null,
                'description' => '<p dir=\"rtl\">&nbsp;تتيح هذه الخدمة للمنشآت التجارية والأفراد التقدم بطلب للحصول على ترخيص جديد لوضع إعلان (لافتة، شاشة، أو إعلان تجاري) وفقاً للضوابط الهندسية والمعايير الجمالية المعتمدة. تهدف الخدمة إلى تنظيم المشهد البصري في المحافظة وضمان سلامة الإعلانات المثبتة.</p>',
                'form_fields' => '[{\"name\":\"id_image\",\"label\":\"\\u0635\\u0648\\u0631\\u0629 \\u0627\\u0644\\u0628\\u0637\\u0627\\u0642\\u0629\",\"type\":\"file\"},{\"name\":\"ad_image\",\"label\":\"\\u0635\\u0648\\u0631\\u0629 \\u0627\\u0644\\u0627\\u0639\\u0644\\u0627\\u0646\",\"type\":\"file\"},{\"name\":\"map\",\"label\":\"\\u0627\\u0644\\u0639\\u0646\\u0648\\u0627\\u0646 \\u0628\\u0627\\u0644\\u062a\\u0641\\u0635\\u064a\\u0644\",\"type\":\"text\"},{\"name\":\"type\",\"label\":\"\\u0646\\u0648\\u0639 \\u0627\\u0644\\u0646\\u0634\\u0627\\u0637\",\"type\":\"text\"},{\"name\":\"category\",\"label\":\"\\u0627\\u062e\\u062a\\u0627\\u0631 \\u0627\\u0644\\u0645\\u0631\\u0643\\u0632 \\/ \\u0627\\u0644\\u0642\\u0631\\u064a\\u0629\",\"type\":\"select\",\"options\":[{\"label\":\"\\u062d\\u064a \\u0634\\u0631\\u0642 \\u0643\\u0641\\u0631 \\u0627\\u0644\\u0634\\u064a\\u062e\",\"value\":\"\\u062d\\u064a \\u0634\\u0631\\u0642\"},{\"label\":\"\\u062d\\u064a \\u063a\\u0631\\u0628 \\u0643\\u0641\\u0631 \\u0627\\u0644\\u0634\\u064a\\u062e\",\"value\":\"\\u062d\\u064a \\u063a\\u0631\\u0628\"},{\"label\":\"\\u0627\\u0644\\u0642\\u0631\\u064a\",\"value\":\"\\u0627\\u0644\\u0642\\u0631\\u064a\"},{\"label\":\"\\u062f\\u0633\\u0648\\u0642\",\"value\":\"\\u062f\\u0633\\u0648\\u0642\"},{\"label\":\"\\u0633\\u064a\\u062f\\u064a \\u0633\\u0627\\u0644\\u0645\",\"value\":\"\\u0633\\u064a\\u062f\\u064a \\u0633\\u0627\\u0644\\u0645\"},{\"label\":\"\\u0627\\u0644\\u0631\\u064a\\u0627\\u0636\",\"value\":\"\\u0627\\u0644\\u0631\\u064a\\u0627\\u0636\"},{\"label\":\"\\u0642\\u0644\\u064a\\u0646\",\"value\":\"\\u0642\\u0644\\u064a\\u0646\"},{\"label\":\"\\u0628\\u0644\\u0637\\u064a\\u0645\",\"value\":\"\\u0628\\u0644\\u0637\\u064a\\u0645\"},{\"label\":\"\\u062d\\u0627\\u0645\\u0648\\u0644\",\"value\":\"\\u062d\\u0627\\u0645\\u0648\\u0644\"},{\"label\":\"\\u0628\\u064a\\u0644\\u0627\",\"value\":\"\\u0628\\u064a\\u0644\\u0627\"},{\"label\":\"\\u0641\\u0648\\u0629\",\"value\":\"\\u0641\\u0648\\u0629\"},{\"label\":\"\\u0645\\u0637\\u0648\\u0628\\u0633\",\"value\":\"\\u0645\\u0637\\u0648\\u0628\\u0633\"},{\"label\":\"\\u0633\\u064a\\u062f\\u064a \\u063a\\u0627\\u0632\\u064a\",\"value\":\"\\u0633\\u064a\\u062f\\u064a \\u063a\\u0627\\u0632\\u064a\"},{\"label\":\"\\u0645\\u0633\\u064a\\u0631\",\"value\":\"\\u0645\\u0633\\u064a\\u0631\"},{\"label\":\"\\u0628\\u0631\\u062c \\u0627\\u0644\\u0628\\u0631\\u0644\\u0633\",\"value\":\"\\u0628\\u0631\\u062c \\u0627\\u0644\\u0628\\u0631\\u0644\\u0633\"},{\"label\":\"\\u0627\\u0644\\u0645\\u0635\\u064a\\u0641\",\"value\":\"\\u0627\\u0644\\u0645\\u0635\\u064a\\u0641\"}]},{\"name\":\"area\",\"label\":\"\\u0645\\u0633\\u0627\\u062d\\u0629 \\u0627\\u0644\\u0623\\u0639\\u0644\\u0627\\u0646 (\\u06452)\",\"type\":\"text\"}]',
                'base_price' => 0.00,
                'has_vat' => true,
                'martyr_stamp_fee' => 5.00,
                'sms_fee' => 10.00,
                'pricing_type' => 'category',
                'category_pricing' => '[{\"name\": \"حي شرق\", \"price_multiplier\": \"600\"}, {\"name\": \"حي غرب\", \"price_multiplier\": \"550\"}, {\"name\": \"باقي المراكز\", \"price_multiplier\": \"500\"}, {\"name\": \"القري\", \"price_multiplier\": \"450\"}]',
                'insurance_fee' => 0.00,
                'insurance_percentage' => 10.00,
                'link' => null,
                'icon' => 'fas fa-plus-circle',
                'is_highlighted' => false,
                'created_at' => '2026-04-30 09:00:36',
                'updated_at' => '2026-05-10 09:06:57',
            ],
            [
                'id' => '019ddda1-54aa-70f8-ab87-f2596ed0a183',
                'parent_id' => '019dd3ed-4573-7255-b27f-16fe7d4cc744',
                'title' => 'تجديد ترخيص إعلان قائم',
                'title_line_1' => null,
                'title_line_2' => null,
                'description' => '<p>&nbsp;</p><p dir=\"rtl\">تُخصص هذه الخدمة لأصحاب التراخيص السارية التي أوشكت على الانتهاء أو انتهت بالفعل، وذلك لتمديد فترة صلاحية الإعلان في موقعه الحالي. يرجى التأكد من مطابقة حالة الإعلان الفعلية للترخيص الأصلي لضمان قبول الطلب.</p><p><br>&nbsp;</p>',
                'form_fields' => '[{\"name\":\"id_image\",\"label\":\"\\u0635\\u0648\\u0631\\u0629 \\u0627\\u0644\\u0628\\u0637\\u0627\\u0642\\u0629\",\"type\":\"file\"},{\"name\":\"ad_image\",\"label\":\"\\u0635\\u0648\\u0631\\u0629 \\u0627\\u0644\\u0627\\u0639\\u0644\\u0627\\u0646\",\"type\":\"file\"},{\"name\":\"old_image\",\"label\":\"\\u0635\\u0648\\u0631\\u0629 \\u0627\\u0644\\u0631\\u062e\\u0635\\u0629\",\"type\":\"file\"},{\"name\":\"map\",\"label\":\"\\u0627\\u0644\\u0639\\u0646\\u0648\\u0627\\u0646 \\u0628\\u0627\\u0644\\u062a\\u0641\\u0635\\u064a\\u0644\",\"type\":\"text\"},{\"name\":\"type\",\"label\":\"\\u0646\\u0648\\u0639 \\u0627\\u0644\\u0646\\u0634\\u0627\\u0637\",\"type\":\"text\"},{\"name\":\"category\",\"label\":\"\\u0627\\u062e\\u062a\\u0627\\u0631 \\u0627\\u0644\\u0645\\u0631\\u0643\\u0632 \\/ \\u0627\\u0644\\u0642\\u0631\\u064a\\u0629\",\"type\":\"select\",\"options\":[{\"label\":\"\\u062d\\u064a \\u0634\\u0631\\u0642 \\u0643\\u0641\\u0631 \\u0627\\u0644\\u0634\\u064a\\u062e\",\"value\":\"\\u062d\\u064a \\u0634\\u0631\\u0642\"},{\"label\":\"\\u062d\\u064a \\u063a\\u0631\\u0628 \\u0643\\u0641\\u0631 \\u0627\\u0644\\u0634\\u064a\\u062e\",\"value\":\"\\u062d\\u064a \\u063a\\u0631\\u0628\"},{\"label\":\"\\u0627\\u0644\\u0642\\u0631\\u064a\",\"value\":\"\\u0627\\u0644\\u0642\\u0631\\u064a\"},{\"label\":\"\\u062f\\u0633\\u0648\\u0642\",\"value\":\"\\u062f\\u0633\\u0648\\u0642\"},{\"label\":\"\\u0633\\u064a\\u062f\\u064a \\u0633\\u0627\\u0644\\u0645\",\"value\":\"\\u0633\\u064a\\u062f\\u064a \\u0633\\u0627\\u0644\\u0645\"},{\"label\":\"\\u0627\\u0644\\u0631\\u064a\\u0627\\u0636\",\"value\":\"\\u0627\\u0644\\u0631\\u064a\\u0627\\u0636\"},{\"label\":\"\\u0642\\u0644\\u064a\\u0646\",\"value\":\"\\u0642\\u0644\\u064a\\u0646\"},{\"label\":\"\\u0628\\u0644\\u0637\\u064a\\u0645\",\"value\":\"\\u0628\\u0644\\u0637\\u064a\\u0645\"},{\"label\":\"\\u062d\\u0627\\u0645\\u0648\\u0644\",\"value\":\"\\u062d\\u0627\\u0645\\u0648\\u0644\"},{\"label\":\"\\u0628\\u064a\\u0644\\u0627\",\"value\":\"\\u0628\\u064a\\u0644\\u0627\"},{\"label\":\"\\u0641\\u0648\\u0629\",\"value\":\"\\u0641\\u0648\\u0629\"},{\"label\":\"\\u0645\\u0637\\u0648\\u0628\\u0633\",\"value\":\"\\u0645\\u0637\\u0648\\u0628\\u0633\"},{\"label\":\"\\u0633\\u064a\\u062f\\u064a \\u063a\\u0627\\u0632\\u064a\",\"value\":\"\\u0633\\u064a\\u062f\\u064a \\u063a\\u0627\\u0632\\u064a\"},{\"label\":\"\\u0645\\u0633\\u064a\\u0631\",\"value\":\"\\u0645\\u0633\\u064a\\u0631\"},{\"label\":\"\\u0628\\u0631\\u062c \\u0627\\u0644\\u0628\\u0631\\u0644\\u0633\",\"value\":\"\\u0628\\u0631\\u062c \\u0627\\u0644\\u0628\\u0631\\u0644\\u0633\"},{\"label\":\"\\u0627\\u0644\\u0645\\u0635\\u064a\\u0641\",\"value\":\"\\u0627\\u0644\\u0645\\u0635\\u064a\\u0641\"}]},{\"name\":\"area\",\"label\":\"\\u0645\\u0633\\u0627\\u062d\\u0629 \\u0627\\u0644\\u0623\\u0639\\u0644\\u0627\\u0646 (\\u06452)\",\"type\":\"text\"}]',
                'base_price' => 0.00,
                'has_vat' => true,
                'martyr_stamp_fee' => 5.00,
                'sms_fee' => 10.00,
                'pricing_type' => 'category',
                'category_pricing' => '[{\"name\": \"حي شرق\", \"price_multiplier\": \"600\"}, {\"name\": \"حي غرب\", \"price_multiplier\": \"550\"}, {\"name\": \"باقي المراكز\", \"price_multiplier\": \"500\"}, {\"name\": \"القري\", \"price_multiplier\": \"450\"}]',
                'insurance_fee' => 0.00,
                'insurance_percentage' => 3.00,
                'link' => null,
                'icon' => 'fas fa-sync-alt',
                'is_highlighted' => false,
                'created_at' => '2026-04-30 09:03:50',
                'updated_at' => '2026-05-10 09:07:01',
            ],
            [
                'id' => '019e3cd3-c75e-72f8-9bf1-c26b84d8cb5c',
                'parent_id' => null,
                'title' => 'طلب تعديل مخطط تفصيلي ',
                'title_line_1' => null,
                'title_line_2' => null,
                'description' => '<p dir=\"rtl\">&nbsp;خدمة تتيح للعميل تقديم طلب لتعديل أو تطوير المخطط التفصيلي للعقار&nbsp; بما يتناسب مع احتياجاته والمتطلبات الهندسية والمعمارية، مع مراجعة التعديلات المقترحة وتنفيذها بدقة لضمان أفضل استغلال للمساحات وتحسين التصميم بشكل احترافي.&nbsp;</p>',
                'form_fields' => '[]',
                'base_price' => 0.00,
                'has_vat' => true,
                'martyr_stamp_fee' => 5.00,
                'sms_fee' => 10.00,
                'pricing_type' => 'fixed',
                'category_pricing' => null,
                'insurance_fee' => 0.00,
                'insurance_percentage' => 0.00,
                'link' => null,
                'icon' => 'fas fa-pen-ruler',
                'is_highlighted' => false,
                'created_at' => '2026-05-18 20:42:52',
                'updated_at' => '2026-05-18 20:42:52',
            ],
            [
                'id' => '019e3cdc-6434-7156-a302-27b2ff652ada',
                'parent_id' => '019e3cd3-c75e-72f8-9bf1-c26b84d8cb5c',
                'title' => 'طلب تعديل مخطط تفصيلي ',
                'title_line_1' => null,
                'title_line_2' => null,
                'description' => '<p dir=\"rtl\">&nbsp; خدمة تتيح للعميل تقديم طلب لتعديل أو تطوير المخطط التفصيلي للعقار&nbsp; بما يتناسب مع احتياجاته والمتطلبات الهندسية والمعمارية، مع مراجعة التعديلات المقترحة وتنفيذها بدقة لضمان أفضل استغلال للمساحات وتحسين التصميم بشكل احترافي.&nbsp;</p>',
                'form_fields' => '[{\"name\":\"id_photo\",\"label\":\"\\u0635\\u0648\\u0631\\u0629 \\u0627\\u0644\\u0628\\u0637\\u0627\\u0642\\u0629 \\u0627\\u0644\\u0634\\u062e\\u0635\\u064a\\u0629\",\"type\":\"file\"},{\"name\":\"Royaldeed\",\"label\":\"\\u0633\\u0646\\u062f \\u0627\\u0644\\u0645\\u0644\\u0643\\u064a\\u0629\",\"type\":\"file\"},{\"name\":\"Approvaloftheconcernedauthorities\",\"label\":\"\\u0645\\u0648\\u0627\\u0641\\u0642\\u0629 \\u0627\\u0644\\u062c\\u0647\\u0627\\u062a \\u0627\\u0644\\u0645\\u0639\\u0646\\u064a\\u0629 (\\u0627\\u0645\\u0644\\u0627\\u0643 \\u062f\\u0648\\u0644\\u0629 - \\u0632\\u0631\\u0627\\u0639\\u0629 ......)\",\"type\":\"file\"},{\"name\":\"copyofthepowerofattorney\",\"label\":\"\\u0635\\u0648\\u0631\\u0629 \\u0627\\u0644\\u062a\\u0648\\u0643\\u064a\\u0644 \\u0627\\u0644\\u0631\\u0633\\u0645\\u064a \\u0641\\u064a \\u062d\\u0627\\u0644\\u0629 \\u0627\\u0644\\u0648\\u0643\\u0627\\u0644\\u0629 \",\"type\":\"file\"},{\"name\":\"Approvaloftheamendmentbythoseresponsible\",\"label\":\"\\u0645\\u0648\\u0627\\u0641\\u0642\\u0629 \\u0627\\u0644\\u0642\\u0627\\u0626\\u0645\\u064a\\u0646 \\u0639\\u0644\\u064a \\u0627\\u0644\\u062a\\u0639\\u062f\\u064a\\u0644 \\u0645\\u0639\\u062a\\u0645\\u062f\\u0629 \\u0628\\u0627\\u0644\\u0648\\u062d\\u062f\\u0629 \\u0627\\u0644\\u0645\\u062d\\u0644\\u064a\\u0629 \\u0648\\u0630\\u0644\\u0643 \\u0628\\u0642\\u0631\\u0627\\u0631 \\u0645\\u0648\\u062b\\u0642 \\u0645\\u0646 \\u0627\\u0644\\u0634\\u0647\\u0631 \\u0627\\u0644\\u0639\\u0642\\u0627\\u0631\\u064a \\u0628\\u0645\\u0648\\u0627\\u0641\\u0642\\u0629 \\u0627\\u0644\\u062c\\u064a\\u0631\\u0627\\u0646 \\u0639\\u0644\\u064a \\u0627\\u0644\\u062a\\u0639\\u062f\\u064a\\u0644 \\u0627\\u0648 \\u0645\\u0648\\u0627\\u0641\\u0642\\u0629 \\u0627\\u0644\\u062c\\u064a\\u0631\\u0627\\u0646 \\u0639\\u0644\\u064a \\u0627\\u0644\\u062a\\u0639\\u062f\\u064a\\u0644 \\u0645\\u0648\\u0642\\u0639 \\u0627\\u0645\\u0627\\u0645 \\u0645\\u0633\\u0626\\u0648\\u0644\\u064a \\u0627\\u0644\\u0648\\u062d\\u062f\\u0629 \\u0627\\u0644\\u0645\\u062d\\u0644\\u064a\\u0629 \\u0645\\u0639\\u062a\\u0645\\u062f \",\"type\":\"file\"}]',
                'base_price' => 1000.00,
                'has_vat' => true,
                'martyr_stamp_fee' => 5.00,
                'sms_fee' => 10.00,
                'pricing_type' => 'fixed',
                'category_pricing' => null,
                'insurance_fee' => 0.00,
                'insurance_percentage' => 0.00,
                'link' => null,
                'icon' => 'fas fa-pen-ruler',
                'is_highlighted' => false,
                'created_at' => '2026-05-18 20:52:17',
                'updated_at' => '2026-05-18 20:55:24',
            ],
        ];

        $services = array_merge($services, self::demoServices());

        foreach ($services as $data) {
            // البيانات منسوخة من تفريغ قاعدة البيانات: علامات \" حرفية وحقول JSON كنصوص.
            // نحولها لقيم صحيحة حتى لا تُرمَّز مرتين بواسطة cast "array" في الموديل.
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = str_replace('\\"', '"', $value);
                }
            }
            foreach (['form_fields', 'category_pricing'] as $jsonField) {
                if (is_string($data[$jsonField] ?? null)) {
                    $data[$jsonField] = json_decode($data[$jsonField], true) ?? [];
                }
            }
            // العمودان title_line_1/2 غير قابلين لـ NULL في المخطط الحالي
            $data['title_line_1'] ??= trim(strip_tags(str_replace('<br>', ' ', $data['title'])));
            $data['title_line_2'] ??= '';
            Service::updateOrCreate(['id' => $data['id']], $data);
        }
    }
    /** خدمات تجريبية إضافية (شجرة: خدمة رئيسية ← خدمات فرعية مسعّرة). */
    private static function demoServices(): array
    {
        $parent = '01a2c0de-0000-7000-8000-000000000001';
        $parent2 = '01a2c0de-0000-7000-8000-000000000002';
        $common = ['title_line_1' => null, 'title_line_2' => null, 'has_vat' => true, 'martyr_stamp_fee' => 5.00, 'sms_fee' => 10.00,
            'insurance_fee' => 0.00, 'insurance_percentage' => 0.00, 'link' => null, 'category_pricing' => null];
        $idField = ['name' => 'id_image', 'label' => 'صورة البطاقة', 'type' => 'file'];
        $centers = array_map(fn ($c) => ['label' => $c, 'value' => $c], ['حي شرق', 'حي غرب', 'باقي المراكز', 'القري']);

        return [
            ['id' => $parent, 'parent_id' => null, 'title' => 'خدمات الوحدات المحلية', 'description' => '<p dir="rtl">مجموعة من الخدمات التي تقدمها الوحدات المحلية بالمراكز والمدن إلكترونياً.</p>',
                'form_fields' => [], 'base_price' => 0, 'pricing_type' => 'fixed', 'icon' => 'fas fa-city', 'is_highlighted' => true] + $common,
            ['id' => '01a2c0de-0000-7000-8000-000000000011', 'parent_id' => $parent, 'title' => 'طلب ترخيص محل تجاري', 'description' => '<p dir="rtl">استخراج ترخيص مزاولة نشاط تجاري أو خدمي وفق قانون المحال العامة.</p>',
                'form_fields' => [$idField, ['name' => 'activity', 'label' => 'نوع النشاط', 'type' => 'text'], ['name' => 'area', 'label' => 'مساحة المحل (م2)', 'type' => 'text'], ['name' => 'category', 'label' => 'المنطقة', 'type' => 'select', 'options' => $centers]],
                'base_price' => 20, 'pricing_type' => 'category', 'category_pricing' => [['name' => 'حي شرق', 'price_multiplier' => '3'], ['name' => 'حي غرب', 'price_multiplier' => '2.5'], ['name' => 'باقي المراكز', 'price_multiplier' => '2'], ['name' => 'القري', 'price_multiplier' => '1.5']],
                'icon' => 'fas fa-store', 'is_highlighted' => true] + $common,
            ['id' => '01a2c0de-0000-7000-8000-000000000012', 'parent_id' => $parent, 'title' => 'طلب توصيل مرافق (مياه / كهرباء)', 'description' => '<p dir="rtl">طلب الموافقة على توصيل المرافق للعقارات المرخصة أو المتصالح عليها.</p>',
                'form_fields' => [$idField, ['name' => 'utility', 'label' => 'نوع المرفق', 'type' => 'text'], ['name' => 'license_no', 'label' => 'رقم الترخيص / التصالح', 'type' => 'text']],
                'base_price' => 150, 'pricing_type' => 'fixed', 'icon' => 'fas fa-plug', 'is_highlighted' => false] + $common,
            ['id' => '01a2c0de-0000-7000-8000-000000000013', 'parent_id' => $parent, 'title' => 'شهادة بيانات عقار', 'description' => '<p dir="rtl">شهادة بالبيانات المسجلة للعقار لدى الوحدة المحلية.</p>',
                'form_fields' => [$idField, ['name' => 'address', 'label' => 'عنوان العقار', 'type' => 'text']],
                'base_price' => 75, 'pricing_type' => 'fixed', 'icon' => 'fas fa-file-alt', 'is_highlighted' => false] + $common,
            ['id' => $parent2, 'parent_id' => null, 'title' => 'خدمات الطرق والإشغالات', 'description' => '<p dir="rtl">تصاريح الإشغال المؤقت للطريق العام وأعمال الحفر.</p>',
                'form_fields' => [], 'base_price' => 0, 'pricing_type' => 'fixed', 'icon' => 'fas fa-road', 'is_highlighted' => false] + $common,
            ['id' => '01a2c0de-0000-7000-8000-000000000021', 'parent_id' => $parent2, 'title' => 'تصريح إشغال طريق مؤقت', 'description' => '<p dir="rtl">تصريح بإشغال جزء من الطريق العام لفترة محددة (مواد بناء / سرادق).</p>',
                'form_fields' => [$idField, ['name' => 'days', 'label' => 'عدد الأيام', 'type' => 'text'], ['name' => 'area', 'label' => 'المساحة (م2)', 'type' => 'text']],
                'base_price' => 40, 'pricing_type' => 'fixed', 'insurance_fee' => 500.00, 'icon' => 'fas fa-traffic-light', 'is_highlighted' => false] + $common,
            ['id' => '01a2c0de-0000-7000-8000-000000000022', 'parent_id' => $parent2, 'title' => 'تصريح حفر لتوصيل مرافق', 'description' => '<p dir="rtl">تصريح أعمال الحفر بالطريق العام لتوصيل المرافق مع تأمين لإعادة الشيء لأصله.</p>',
                'form_fields' => [$idField, ['name' => 'length', 'label' => 'طول الحفر (م)', 'type' => 'text']],
                'base_price' => 300, 'pricing_type' => 'fixed', 'insurance_fee' => 1000.00, 'icon' => 'fas fa-digging', 'is_highlighted' => false] + $common,
        ];
    }
}
