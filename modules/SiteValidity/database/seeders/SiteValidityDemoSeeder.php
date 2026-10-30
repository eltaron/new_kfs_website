<?php

namespace Modules\SiteValidity\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\SiteValidity\Models\AttachmentType;
use Modules\SiteValidity\Models\Completion;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Models\Geo\Village;
use Modules\SiteValidity\Models\InspectionField;
use Modules\SiteValidity\Models\RequestEvent;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\Setting;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvSettings;
use Modules\SiteValidity\Support\SvStatus;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * بيانات تجريبية لخدمة «طلب صلاحية الموقع» — غير هدّامة وقابلة لإعادة التشغيل (idempotent):
 *   php artisan db:seed --class="Modules\SiteValidity\database\seeders\SiteValidityDemoSeeder"
 *
 * - الأدوار، الإعدادات (firstOrCreate — لا تمس تعديلات المسؤول)، أنواع الخدمة، بنود المعاينة، أنواع المرفقات.
 * - حسابات (@demo.kfs.invalid): مسؤول الخدمة، المركز الجيومكاني، مهندس التنظيم، مدير التنظيم، المركز التكنولوجي، مواطنان.
 * - 17 طلباً تغطي كل الحالات مع الخط الزمني وملفات تجريبية صغيرة على القرص الخاص.
 */
class SiteValidityDemoSeeder extends Seeder
{
    public const FALLBACK_PASSWORD = 'SvDemo@2026#Kfs';

    public const ACCOUNTS = [
        'admin' => ['sv.admin', 'مسؤول صلاحية الموقع — أ. هشام الشناوي', SvAccess::ADMIN, '28503151500231', '01001110001'],
        'geo' => ['sv.geo', 'المركز الجيومكاني — م. نادية البدوي', SvAccess::GEO, '28807221500342', '01001110002'],
        'engineer' => ['sv.engineer', 'مهندس التنظيم — م. كريم عبد العزيز', SvAccess::ENGINEER, '28611041500153', '01001110003'],
        'director' => ['sv.director', 'مدير التنظيم — م. سامح الدسوقي', SvAccess::DIRECTOR, '28202101500264', '01001110005'],
        'citizen1' => ['sv.citizen1', 'محمود إبراهيم السيد الجمل', null, '29004121500575', '01012345671'],
        'citizen2' => ['sv.citizen2', 'هبة مصطفى علي قنديل', null, '29309051500686', '01112345672'],
        'tech' => ['sv.tech', 'المركز التكنولوجي — كفر الشيخ', SvAccess::TECH, '28901011500457', '01001110004'],
    ];

    /** @var array<string, User> */
    protected array $users = [];

    public function run(): void
    {
        $this->roles();
        $this->settings();
        $types = $this->serviceTypes();
        $this->inspectionFields();
        $this->attachmentTypes();
        $this->call(SiteValidityScheduleSeeder::class);
        $this->accounts();
        $created = $this->requests($types);
        $this->demoLocations();
        $this->writeAccountsFile();

        $this->command?->info("صلاحية الموقع: أُضيف {$created} طلب تجريبي (إجمالي " . SiteValidityRequest::query()->where('number', 'like', 'SV-2026-9%')->count() . ' طلباً تجريبياً).');
        $this->command?->info('كلمة مرور حسابات صلاحية الموقع: ' . (env('DEMO_PASSWORD') ? 'قيمة DEMO_PASSWORD' : self::FALLBACK_PASSWORD));
    }

    public static function password(): string
    {
        return (string) (env('DEMO_PASSWORD') ?: self::FALLBACK_PASSWORD);
    }

    protected function roles(): void
    {
        foreach (SvAccess::serviceRoles() as $role) {
            Role::findOrCreate($role, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function settings(): void
    {
        foreach (SvSettings::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
        SvSettings::forget();
    }

    /** @return array<string, ServiceType> */
    protected function serviceTypes(): array
    {
        $rows = [
            ['building-permit', 'صلاحية موقع لترخيص بناء', 'شهادة صلاحية الموقع اللازمة لاستخراج ترخيص بناء جديد أو تعلية.', 500, 1],
            ['utilities', 'صلاحية موقع لتوصيل المرافق', 'لتوصيل الكهرباء أو المياه أو الصرف الصحي لعقار قائم.', 300, 2],
            ['reconciliation', 'صلاحية موقع لملف تصالح', 'معاينة ورفع مساحي ضمن ملفات التصالح في مخالفات البناء.', 750, 3],
            ['investment', 'صلاحية موقع لمشروع استثماري', 'للمشروعات الصناعية والتجارية والخدمية داخل الحيز العمراني.', 1500, 4],
        ];
        $out = [];
        foreach ($rows as [$slug, $name, $desc, $fee, $sort]) {
            $out[$slug] = ServiceType::query()->firstOrCreate(['slug' => $slug], [
                'name' => $name, 'description' => $desc, 'fee' => $fee, 'sort_order' => $sort, 'is_active' => true,
            ]);
        }

        return $out;
    }

    protected function inspectionFields(): void
    {
        $rows = [
            ['has_boundaries', 'هل يوجد حدود واضحة للموقع؟', 'yes_no', null, false, 1, 'أسوار أو علامات حدود مثبتة على الطبيعة'],
            ['has_projection', 'هل يوجد بروز / تعدٍّ على خط التنظيم؟', 'yes_no', null, false, 2, null],
            ['has_setback', 'هل يوجد ارتداد مطابق لخط التنظيم؟', 'yes_no', null, false, 3, null],
            ['street_width', 'عرض الشارع (متر)', 'number', null, false, 4, 'عرض الشارع الرئيسي المطل عليه الموقع'],
            ['land_type', 'نوع الأرض', 'select', ['سكني', 'زراعي داخل الحيز', 'تجاري', 'صناعي', 'أملاك دولة'], false, 5, null],
            ['area_sqm', 'المساحة التقريبية (م²)', 'number', null, false, 6, null],
            ['utilities_status', 'حالة المرافق', 'select', ['متوفرة بالكامل', 'متوفرة جزئياً', 'غير متوفرة'], false, 7, null],
            ['neighbor_notes', 'وصف الجيران والحدود', 'text', null, false, 8, null],
            ['notes', 'ملاحظات أخرى عن الموقع', 'textarea', null, false, 9, null],
        ];
        foreach ($rows as [$key, $label, $type, $options, $required, $sort, $help]) {
            InspectionField::query()->firstOrCreate(['key' => $key], [
                'label' => $label, 'type' => $type, 'options' => $options, 'is_required' => $required,
                'sort_order' => $sort, 'help_text' => $help, 'is_active' => true,
            ]);
        }
    }

    protected function attachmentTypes(): void
    {
        $rows = [
            ['survey', 'الرفع المساحي', 'pdf,jpg,jpeg,png,dwg,dxf', 20480, true, 1, 'لوحة الرفع المساحي للموقع بالإحداثيات.'],
            ['cad', 'ملف الكاد', 'dwg,dxf,zip,pdf', 30720, true, 2, 'ملف AutoCAD (DWG/DXF) أو أرشيف مضغوط.'],
            ['site_photos', 'صور الموقع', 'jpg,jpeg,png,pdf,zip', 20480, false, 3, 'صور المعاينة الميدانية.'],
            ['other', 'مرفقات أخرى', 'pdf,jpg,jpeg,png,zip,kml,kmz', 20480, false, 4, null],
        ];
        foreach ($rows as [$key, $name, $ext, $max, $required, $sort, $desc]) {
            AttachmentType::query()->firstOrCreate(['key' => $key], [
                'name' => $name, 'extensions' => $ext, 'max_size_kb' => $max, 'is_required' => $required,
                'sort_order' => $sort, 'description' => $desc, 'is_active' => true,
            ]);
        }
    }

    protected function accounts(): void
    {
        $password = self::password();

        foreach (self::ACCOUNTS as $key => [$local, $name, $role, $nid, $phone]) {
            $email = $local . '@demo.kfs.invalid';
            $user = User::query()->where('email', $email)->first() ?? new User(['email' => $email]);

            // الرقم القومي فريد: إن كان مستخدماً لحساب آخر نولد بديلاً ثابتاً.
            if (! $user->national_id) {
                $candidate = $nid;
                $i = 0;
                while (User::query()->where('national_id', $candidate)->where('email', '!=', $email)->exists()) {
                    $candidate = substr($nid, 0, 10) . str_pad((string) ((int) substr($nid, 10) + (++$i) * 7 % 10000), 4, '0', STR_PAD_LEFT);
                }
                $user->national_id = $candidate;
            }

            $user->fill([
                'name' => $name,
                'phone' => $user->phone ?: $phone,
                'password' => $password,
                'status' => 'approved',
                'job_title' => $role ? SvAccess::ROLE_LABELS[$role] : null,
                'center' => $user->center ?: 'كفر الشيخ',
            ]);
            $user->email = $email;
            $user->email_verified_at ??= now();
            $user->save();

            if ($role && ! $user->hasRole($role)) {
                $user->assignRole($role);
            }
            $this->users[$key] = $user;
        }
    }

    /** @param array<string, ServiceType> $types */
    protected function requests(array $types): int
    {
        $centre = Centre::query()->active()->first();
        $centres = Centre::query()->active()->get()->values();
        $now = Carbon::now()->startOfHour();
        $created = 0;

        // [suffix, citizen, service, status, paid, appointment offset (days), extra]
        $plan = [
            [1, 'citizen1', 'building-permit', SvStatus::SUBMITTED, false, null, []],
            [2, 'citizen2', 'utilities', SvStatus::SUBMITTED, true, null, []],
            [3, 'citizen1', 'reconciliation', SvStatus::APPOINTMENT_SET, false, 3, []],
            [4, 'citizen2', 'building-permit', SvStatus::APPOINTMENT_SET, true, 5, []],
            [5, 'citizen1', 'investment', SvStatus::APPROVED, true, 1, []],
            [6, 'citizen2', 'building-permit', SvStatus::APPROVED, false, -1, ['geo']],
            [7, 'citizen1', 'utilities', SvStatus::APPROVED, true, -2, ['engineer']],
            [8, 'citizen2', 'reconciliation', SvStatus::INSPECTED, true, -4, ['geo', 'engineer']],
            [9, 'citizen1', 'building-permit', SvStatus::ATTACHMENTS_UPLOADED, true, -6, ['geo', 'engineer', 'attachments']],
            [10, 'citizen2', 'investment', SvStatus::COMPLETION_REQUESTED, true, -8, ['geo', 'engineer', 'attachments', 'c1-open']],
            [11, 'citizen1', 'reconciliation', SvStatus::COMPLETION_SUBMITTED, false, -9, ['geo', 'engineer', 'attachments', 'c1-replied']],
            [12, 'citizen2', 'building-permit', SvStatus::COMPLETION_REQUESTED, true, -12, ['geo', 'engineer', 'attachments', 'c1-replied', 'c2-open']],
            [13, 'citizen1', 'building-permit', SvStatus::COMPLETED, true, -15, ['geo', 'engineer', 'attachments', 'final']],
            [14, 'citizen2', 'utilities', SvStatus::COMPLETED, true, -20, ['geo', 'engineer', 'attachments', 'c1-replied', 'final']],
            [15, 'citizen1', 'investment', SvStatus::REJECTED, false, 2, ['reject']],
            [16, 'citizen2', 'reconciliation', SvStatus::APPROVED, false, -3, []],
            [17, 'citizen1', 'building-permit', SvStatus::FINAL_UPLOADED, true, -11, ['geo', 'engineer', 'attachments', 'final']],
        ];

        $addresses = ['شارع الجيش — بجوار مسجد النور', 'طريق كفر الشيخ / دسوق — كم 4', 'شارع المدارس — خلف الوحدة الصحية', 'حوض الشيخ — قطعة 112', 'شارع البحر — أمام مكتب البريد', 'منطقة المستثمرين — بلوك 7'];

        foreach ($plan as $idx => [$n, $citizenKey, $typeSlug, $status, $paid, $apptOffset, $extra]) {
            $number = sprintf('SV-2026-9%05d', $n);
            if (SiteValidityRequest::query()->where('number', $number)->exists()) {
                continue;
            }

            $citizen = $this->users[$citizenKey];
            $type = $types[$typeSlug];
            $c = $centres->isNotEmpty() ? $centres[$idx % $centres->count()] : $centre;
            $village = $c ? Village::query()->active()->where('center_id', $c->id)->skip($idx % 3)->first() : null;
            $submitted = $now->copy()->subDays(max(2, 3 - (int) $apptOffset) + 2)->setTime(9 + $idx % 6, 15);

            DB::transaction(function () use ($number, $citizen, $type, $status, $paid, $apptOffset, $extra, $c, $village, $submitted, $addresses, $idx, $now) {
                $r = new SiteValidityRequest([
                    'service_type_id' => $type->id,
                    'construction_type' => $idx % 3 === 1 ? SiteValidityRequest::CONSTRUCTION_EXTENSION : SiteValidityRequest::CONSTRUCTION_NEW,
                    'applicant_capacity' => $idx % 4 === 2 ? SiteValidityRequest::CAPACITY_AGENT : SiteValidityRequest::CAPACITY_OWNER,
                    'applicant_name' => $citizen->name,
                    'national_id' => $citizen->national_id,
                    'phone' => $citizen->phone,
                    'center_id' => $c?->id,
                    'village_id' => $village?->id,
                    'center_name' => $c?->name ?? 'كفر الشيخ',
                    'village_name' => $village?->name,
                    'address' => $addresses[$idx % count($addresses)],
                    'notes' => $idx % 3 === 0 ? 'الأرض على ناصية شارعين.' : null,
                ]);
                $r->forceFill([
                    'number' => $number,
                    'user_id' => $citizen->getKey(),
                    'status' => $status,
                    'fee' => $type->fee,
                    'submitted_at' => $submitted,
                    'created_at' => $submitted,
                    'updated_at' => $now,
                ])->save();

                $t = $submitted->copy();
                $this->event($r, $citizen, 'submitted', null, SvStatus::SUBMITTED, null, $t);
                foreach (['sketch' => 'png', 'contract' => 'pdf', 'id_card' => 'png'] as $kind => $ext) {
                    $this->placeholder($r, $kind, $ext, $citizen);
                }
                if ($r->applicant_capacity === SiteValidityRequest::CAPACITY_AGENT) {
                    $this->placeholder($r, RequestFile::KIND_POA, 'pdf', $citizen);
                }
                if ($r->construction_type === SiteValidityRequest::CONSTRUCTION_EXTENSION) {
                    $this->placeholder($r, RequestFile::KIND_FLOORS, 'pdf', $citizen);
                }

                if ($paid) {
                    $r->forceFill(['paid_at' => $t->copy()->addHours(2), 'paid_amount' => $type->fee, 'payment_reference' => 'DEMO-' . strtoupper(Str::random(8))]);
                    $this->event($r, null, 'paid', SvStatus::SUBMITTED, SvStatus::SUBMITTED, 'تم سداد ' . number_format((float) $type->fee, 2) . ' ج.م عبر e-Finance (تجريبي).', $t->copy()->addHours(2), 'منظومة الدفع الإلكتروني', 'system');
                }

                if ($status === SvStatus::SUBMITTED) {
                    $r->save();

                    return;
                }

                $geo = $this->users['geo'];
                $eng = $this->users['engineer'];
                $appointment = $now->copy()->addDays((int) $apptOffset)->setTime(10 + $idx % 4, 0);
                $r->forceFill(['appointment_at' => $appointment, 'appointment_notes' => 'برجاء التواجد بالموقع ومعك أصل عقد الملكية والبطاقة.']);
                $t = $t->copy()->addDay();
                $this->event($r, $geo, 'set_appointment', SvStatus::SUBMITTED, SvStatus::APPOINTMENT_SET, 'موعد المعاينة: ' . $appointment->format('Y-m-d H:i'), $t);

                if (in_array('reject', $extra, true)) {
                    $r->forceFill(['rejected_at' => $t->copy()->addHours(5), 'rejection_reason' => 'الموقع يقع خارج الحيز العمراني المعتمد ولا يجوز إصدار صلاحية له.']);
                    $this->event($r, $eng, 'reject', SvStatus::APPOINTMENT_SET, SvStatus::REJECTED, $r->rejection_reason, $t->copy()->addHours(5));
                    $r->save();

                    return;
                }
                if ($status === SvStatus::APPOINTMENT_SET) {
                    $r->save();

                    return;
                }

                $t = $t->copy()->addHours(6);
                $r->approved_at = $t;
                $this->event($r, $eng, 'approve', SvStatus::APPOINTMENT_SET, SvStatus::APPROVED, null, $t);

                $inspect = $appointment->copy()->addHour();
                if (in_array('geo', $extra, true)) {
                    $r->forceFill(['geo_inspected_at' => $inspect, 'geo_inspection_notes' => 'تمت المعاينة بحضور المالك.']);
                    $this->event($r, $geo, 'geo_inspection', SvStatus::APPROVED, in_array('engineer', $extra, true) ? SvStatus::APPROVED : SvStatus::APPROVED, 'تمت المعاينة بحضور المالك.', $inspect);
                }
                if (in_array('engineer', $extra, true)) {
                    $both = in_array('geo', $extra, true);
                    $r->forceFill([
                        'engineer_inspected_at' => $inspect->copy()->addMinutes(20),
                        'inspection_data' => $this->answers($idx),
                        'inspection_notes' => 'الموقع مطابق للكروكي المقدم.',
                    ]);
                    $this->event($r, $eng, 'engineer_inspection', SvStatus::APPROVED, $both ? SvStatus::INSPECTED : SvStatus::APPROVED, 'الموقع مطابق للكروكي المقدم.', $inspect->copy()->addMinutes(20));
                }

                $t = $inspect->copy()->addDay();
                if (in_array('attachments', $extra, true)) {
                    foreach (AttachmentType::query()->active()->get() as $at) {
                        if ($at->is_required || $at->key === 'site_photos') {
                            $ext = match ($at->key) { 'cad' => 'dxf', 'site_photos' => 'png', default => 'pdf' };
                            $this->placeholder($r, RequestFile::KIND_ATTACHMENT, $ext, $geo, $at->id);
                        }
                    }
                    $r->attachments_uploaded_at = $t;
                    $this->event($r, $geo, 'upload_attachments', SvStatus::INSPECTED, SvStatus::ATTACHMENTS_UPLOADED, null, $t);
                }

                $round = 0;
                foreach (['c1-open' => false, 'c1-replied' => true, 'c2-open' => false] as $flag => $replied) {
                    if (! in_array($flag, $extra, true)) {
                        continue;
                    }
                    $round++;
                    $t = $t->copy()->addDays(1);
                    $comp = new Completion([
                        'round' => $round,
                        'notes' => $round === 1 ? 'برجاء إرفاق صورة عقد مسجل بدلاً من العقد الابتدائي، وتوضيح الحد البحري في الكروكي.' : 'برجاء إرفاق إيصال سداد الضريبة العقارية لعام 2026.',
                        'requested_items' => $round === 1 ? ['صورة عقد مسجل', 'كروكي معدل يوضح الحد البحري'] : ['إيصال الضريبة العقارية'],
                    ]);
                    $comp->forceFill(['request_id' => $r->id, 'requested_by' => $eng->getKey(), 'requested_at' => $t])->save();
                    $this->event($r, $eng, 'request_completion', SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_REQUESTED, $comp->notes, $t);
                    if ($replied) {
                        $t = $t->copy()->addDays(1);
                        $comp->forceFill(['reply' => 'تم إرفاق المستندات المطلوبة.', 'replied_at' => $t])->save();
                        $this->placeholder($r, RequestFile::KIND_COMPLETION, 'pdf', $r->user ?? $this->users['citizen1'], null, $comp->id);
                        $this->event($r, $r->user, 'submit_completion', SvStatus::COMPLETION_REQUESTED, SvStatus::COMPLETION_SUBMITTED, 'تم إرفاق المستندات المطلوبة.', $t);
                    }
                }
                $r->completion_rounds = $round;

                if (in_array('final', $extra, true)) {
                    $t = $t->copy()->addDays(1);
                    $this->placeholder($r, RequestFile::KIND_FINAL, 'pdf', $eng);
                    $r->forceFill(['final_notes' => 'شهادة صلاحية الموقع معتمدة وسارية لمدة سنة من تاريخه.']);
                    $this->event($r, $eng, 'upload_final', SvStatus::ATTACHMENTS_UPLOADED, SvStatus::FINAL_UPLOADED, $r->final_notes, $t);
                    if ($status === SvStatus::COMPLETED) {
                        $t = $t->copy()->addHours(4);
                        $r->forceFill(['completed_at' => $t, 'pickup_code' => 'DEMO-' . str_pad((string) $idx, 4, '0', STR_PAD_LEFT)]);
                        $this->event($r, $this->users['director'], 'complete', SvStatus::FINAL_UPLOADED, SvStatus::COMPLETED, null, $t);
                    }
                }

                $r->save();
            });
            $created++;
        }

        return $created;
    }

    /** @return list<array{key:string,label:string,type:string,value:?string}> */
    protected function answers(int $idx): array
    {
        $values = [
            'has_boundaries' => 'yes', 'has_projection' => $idx % 4 === 0 ? 'yes' : 'no', 'has_setback' => 'yes',
            'street_width' => (string) (6 + $idx % 5 * 2), 'land_type' => ['سكني', 'تجاري', 'زراعي داخل الحيز'][$idx % 3],
            'area_sqm' => (string) (150 + $idx * 35), 'utilities_status' => 'متوفرة جزئياً',
            'neighbor_notes' => 'بحري: منزل قائم — قبلي: شارع — شرقي: أرض فضاء — غربي: منزل قائم', 'notes' => null,
        ];

        return InspectionField::query()->active()->get()->map(fn (InspectionField $f) => [
            'key' => $f->key, 'label' => $f->label, 'type' => $f->type, 'value' => $values[$f->key] ?? null,
        ])->all();
    }

    protected function event(SiteValidityRequest $r, ?User $actor, string $action, ?string $from, ?string $to, ?string $notes, Carbon $at, ?string $name = null, ?string $role = null): void
    {
        $e = new RequestEvent([
            'action' => $action, 'from_status' => $from, 'to_status' => $to, 'notes' => $notes,
            'actor_name' => $name ?? $actor?->name, 'actor_role' => $role ?? SvAccess::roleKey($actor) ?? 'system',
        ]);
        $e->request_id = $r->id;
        $e->user_id = $actor?->getKey();
        $e->created_at = $at;
        $e->save();
    }

    protected function placeholder(SiteValidityRequest $r, string $kind, string $ext, ?User $by, ?int $attachmentTypeId = null, ?int $completionId = null): void
    {
        $disk = Storage::disk((string) config('site-validity.disk', 'local'));
        $path = 'site-validity/' . $r->id . '/' . $kind . '-demo-' . Str::lower(Str::random(10)) . '.' . $ext;
        [$content, $mime] = match ($ext) {
            'png' => [$this->png($kind), 'image/png'],
            'pdf' => [$this->pdf('KFS Site Validity ' . $r->number . ' - ' . $kind), 'application/pdf'],
            'dxf' => ["0\nSECTION\n2\nENTITIES\n0\nLINE\n8\n0\n10\n0.0\n20\n0.0\n11\n100.0\n21\n0.0\n0\nENDSEC\n0\nEOF\n", 'image/vnd.dxf'],
            default => ['KFS demo file', 'text/plain'],
        };
        $disk->put($path, $content);

        $names = [
            RequestFile::KIND_SKETCH => 'كروكي-الموقع.png', RequestFile::KIND_CONTRACT => 'عقد-الملكية.pdf', RequestFile::KIND_ID_CARD => 'صورة-البطاقة.png',
            RequestFile::KIND_POA => 'صورة-التوكيل.pdf', RequestFile::KIND_FLOORS => 'موقف-الأدوار-القائمة.pdf',
            RequestFile::KIND_COMPLETION => 'مستندات-الاستيفاء.pdf', RequestFile::KIND_FINAL => 'شهادة-صلاحية-الموقع-' . $r->number . '.pdf',
        ];

        $f = new RequestFile(['kind' => $kind, 'original_name' => $names[$kind] ?? ($kind . '.' . $ext)]);
        $f->forceFill([
            'request_id' => $r->id, 'attachment_type_id' => $attachmentTypeId, 'completion_id' => $completionId,
            'path' => $path, 'mime' => $mime, 'size' => strlen($content), 'uploaded_by' => $by?->getKey(),
        ])->save();
        if ($attachmentTypeId) {
            $f->original_name = (AttachmentType::query()->find($attachmentTypeId)?->name ?? 'مرفق') . '.' . $ext;
            $f->save();
        }
    }

    protected function png(string $kind): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        }
        $im = imagecreatetruecolor(480, 320);
        $bg = imagecolorallocate($im, 250, 248, 240);
        $line = imagecolorallocate($im, 101, 115, 66);
        $gold = imagecolorallocate($im, 216, 176, 62);
        imagefill($im, 0, 0, $bg);
        imagesetthickness($im, 3);
        if ($kind === RequestFile::KIND_ID_CARD) {
            imagerectangle($im, 40, 40, 440, 280, $line);
            imagefilledrectangle($im, 60, 70, 170, 200, $gold);
            for ($y = 90; $y <= 230; $y += 35) {
                imageline($im, 200, $y, 410, $y, $line);
            }
        } else {
            imagepolygon($im, [90, 70, 380, 60, 410, 250, 70, 270], $line);
            imageline($im, 20, 300, 460, 300, $gold);
            imagestring($im, 3, 200, 150, 'SITE', $line);
        }
        imagestring($im, 2, 10, 5, 'KFS demo placeholder', $line);
        ob_start();
        imagepng($im);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    protected function pdf(string $text): string
    {
        $text = preg_replace('/[^A-Za-z0-9 \-]/', '', $text);
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET\nBT /F1 12 Tf 72 690 Td (Kafr El-Sheikh Governorate - demo placeholder) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    /**
     * إحداثيات تجريبية للطلبات التجريبية فقط (SV-2026-9…) بلا موقع: نقطة عشوائية ثابتة داخل حدود مركزها
     * (أو بجوار نقطة المركز لما ليس له حدود) — لعرض «خريطة الطلبات». لا تمس الطلبات الحقيقية.
     */
    protected function demoLocations(): void
    {
        $path = public_path((string) config('site-validity.map.boundaries'));
        $geo = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $polygons = [];
        foreach ((array) ($geo['features'] ?? []) as $f) {
            $coords = $f['geometry']['type'] === 'Polygon' ? [$f['geometry']['coordinates']] : $f['geometry']['coordinates'];
            foreach ($coords as $poly) {
                $polygons[$f['properties']['centre']][] = $poly[0]; // الحلقة الخارجية [[lng, lat], …]
            }
        }
        $points = (array) config('site-validity.map.points', []);

        SiteValidityRequest::query()->where('number', 'like', 'SV-2026-9%')->whereNull('latitude')->get()
            ->each(function (SiteValidityRequest $r) use ($polygons, $points) {
                mt_srand(crc32($r->number));
                $ll = null;
                if (! empty($polygons[$r->center_name])) {
                    $ring = collect($polygons[$r->center_name])->sortByDesc(fn ($ring) => count($ring))->first();
                    $lngs = array_column($ring, 0);
                    $lats = array_column($ring, 1);
                    for ($i = 0; $i < 200 && ! $ll; $i++) {
                        $p = [min($lngs) + mt_rand() / mt_getrandmax() * (max($lngs) - min($lngs)), min($lats) + mt_rand() / mt_getrandmax() * (max($lats) - min($lats))];
                        if ($this->inside($p, $ring)) {
                            $ll = [$p[1], $p[0]];
                        }
                    }
                } elseif (isset($points[$r->center_name])) {
                    [$lat, $lng] = $points[$r->center_name];
                    $ll = [$lat + (mt_rand(-80, 80) / 10000), $lng + (mt_rand(-80, 80) / 10000)];
                }
                if ($ll) {
                    $r->forceFill(['latitude' => round($ll[0], 7), 'longitude' => round($ll[1], 7)])->saveQuietly();
                }
            });
        mt_srand();
    }

    /** نقطة داخل مضلع (ray casting). @param array{0: float, 1: float} $p  @param list<array{0: float, 1: float}> $ring */
    private function inside(array $p, array $ring): bool
    {
        $in = false;
        for ($i = 0, $j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            if ((($yi > $p[1]) !== ($yj > $p[1])) && ($p[0] < ($xj - $xi) * ($p[1] - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $in = ! $in;
            }
        }

        return $in;
    }

    protected function writeAccountsFile(): void
    {
        $path = storage_path('app/private/demo-accounts.txt');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        $existing = is_file($path) ? (string) file_get_contents($path) : '';
        $marker = '# Site validity service (/site-validity)';
        if (str_contains($existing, $marker)) {
            return;
        }

        $lines = [
            '',
            $marker . ' — password: DEMO_PASSWORD if set, else ' . self::FALLBACK_PASSWORD,
        ];
        foreach (self::ACCOUNTS as $key => [$local, $name, $role]) {
            $user = $this->users[$key] ?? null;
            $lines[] = sprintf('%-41s %-11s %-22s NID %s', $local . '@demo.kfs.invalid', $role ? 'staff' : 'citizen', $role ?? 'مواطن', $user?->national_id ?? '-');
        }
        file_put_contents($path, rtrim($existing) . PHP_EOL . implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
