# وحدة نقابة المهندسين — الإشراف على المواقع وإلغاء الإشراف

تربط مجالس المدن / الوحدات المحلية بمحافظة كفر الشيخ بنقابة المهندسين في دورة حياة **شهادة الإشراف على الموقع**
و**شهادة إلغاء الإشراف**، مع إيقاف الأعمال تلقائياً عند خلو الموقع من مهندس مشرف.

## سير العمل

| # | الخطوة | الجهة | النتيجة |
|---|--------|-------|---------|
| 1 | تسجيل المشروع (الموقع) + طلب إشراف | مجلس المدينة (`/council`) أو المواطن إلكترونياً (`/syndicate/my-projects`) | رقم معاملة `SYN-REQ-YYYY-NNNNNN` |
| 2 | البحث برقم المعاملة ومراجعة الطلب | النقابة (`/syndicate` أو الـ API) | `under_review` / `approved` / `rejected` |
| 3 | إصدار شهادة الإشراف وتعيين المهندس / المكتب | النقابة | `SYN-SUP-YYYY-NNNNNN` — المشروع **نشط - تحت الإشراف** |
| 4 | الإخطار | تلقائي | المالك + المهندس + مجلس المدينة (قاعدة بيانات، بريد، SMS، Web Push عند الاشتراك) |
| 5 | طلب إلغاء الإشراف (من المهندس أو المواطن أو المجلس) | مجلس المدينة / المواطن | `SYN-CRQ-YYYY-NNNNNN` |
| 6 | إصدار شهادة الإلغاء | النقابة | `SYN-CAN-YYYY-NNNNNN` — المشروع **موقوف - بدون إشراف** + تنبيه بوجوب إيقاف الأعمال |
| 7 | شهادة إشراف جديدة لنفس المشروع | النقابة | المشروع يعود **نشطاً** ويُخطر المجلس والمالك والمهندس الجديد والسابق |

### قواعد العمل (آلة الحالات — `Services/SupervisionService.php`)
- كل عملية داخل معاملة قاعدة بيانات مع قفل الصفوف (`lockForUpdate`) — والترقيم من جدول `syndicate_sequences` آمن من التزامن.
- **شهادة إشراف سارية واحدة فقط لكل مشروع**: إصدار شهادة جديدة يجعل السابقة `superseded`.
- لا يُقبل طلب مفتوح مكرر من نفس النوع على نفس المشروع، ولا طلب إلغاء بدون شهادة سارية.
- المهندس يقدم طلبات الإلغاء فقط؛ طلب الإشراف من المالك أو المجلس.
- لا يُعيَّن مهندس عضويته غير سارية.
- أحداث النطاق (`Events/*`) تُطلق بعد نجاح المعاملة (`ShouldDispatchAfterCommit`) وتُشغّل الإشعارات و Webhook الاختياري.
- كل خطوة تُسجل في الخط الزمني للمشروع (`syndicate_events`) مع الفاعل (موظف / API / النظام).

## الواجهات

| المسار | الوصف | الصلاحية |
|--------|-------|----------|
| `/syndicate` | لوحة النقابة: الطلبات (مراجعة، اعتماد، رفض، إصدار شهادات)، الشهادات، المشروعات، دليل المهندسين | دور `نقابة المهندسين` أو `super_admin`/`Admin` |
| `/council` | لوحة مجلس المدينة: تسجيل المشروعات والطلبات، البحث برقم الشهادة/المعاملة، شارة وتنبيه المشروعات الموقوفة، الخط الزمني | دور `مجلس المدينة` أو الأدوار الهندسية في `config/syndicate.php` (`roles.council_extra`) |
| `/syndicate/verify/{number}` | التحقق العام من الشهادة (QR) — البيانات الشخصية مخفية جزئياً إلا مع رمز التحقق المطبوع | عام (throttle 30/دقيقة) |
| `/syndicate/my-projects` | مشروعات المواطن / المهندس + تقديم طلب إشراف أو إلغاء إلكترونياً | مستخدم مسجل (الربط بالرقم القومي) |
| `/syndicate/print/{number}` | نسخة طباعة / PDF (طباعة المتصفح) مع رمز QR | أطراف الشهادة وموظفو النقابة والمجلس |
| `/citizen-lookup` | الاستعلام العام بالرقم القومي أو رقم الشهادة أو رقم المعاملة | عام (throttle 10/دقيقة) |
| `/admin/syndicate-certificates` | عرض إداري للقراءة فقط | المدراء |

## واجهة البرمجة `api/syndicate/v1`

التوثيق الكامل: [`docs/openapi.yaml`](docs/openapi.yaml)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| GET | `requests?status=&type=&center=` | قائمة الطلبات |
| GET | `requests/{transaction_number}` | البحث برقم المعاملة |
| POST | `requests/{transaction_number}/status` | `under_review` / `approved` / `rejected` |
| POST | `certificates` | إصدار شهادة إشراف |
| GET | `certificates/{number}` | الاستعلام عن شهادة |
| POST | `cancellations/{transaction_number}/certificate` | إصدار شهادة إلغاء إشراف |
| GET | `projects/{id}/timeline` | الخط الزمني للمشروع |
| POST | `engineers/sync` | مزامنة دليل المهندسين |

**الأمان**: سجل تدقيق ← قائمة سماح IP ← توكن Sanctum لحساب تكامل نشط بالقدرة `syndicate` ← حد المعدل ← Idempotency-Key.

```bash
# إنشاء حساب تكامل وتوكن (يظهر مرة واحدة فقط)
php artisan syndicate:token "نقابة المهندسين - فرعية كفر الشيخ" --ips=196.204.10.5,10.10.0.0/16 --expires=365
# تدوير التوكن
php artisan syndicate:token "نقابة المهندسين - فرعية كفر الشيخ" --revoke

curl -H "Authorization: Bearer <TOKEN>" https://<host>/api/syndicate/v1/requests/SYN-REQ-2026-000123
curl -X POST -H "Authorization: Bearer <TOKEN>" -H "Idempotency-Key: 7f1c..." -H "Content-Type: application/json" \
     -d '{"transaction_number":"SYN-REQ-2026-000123","engineer":{"syndicate_number":"KFS-10231","name":"م. أحمد"}}' \
     https://<host>/api/syndicate/v1/certificates
```

## الإعدادات (`.env`)

| المتغير | الافتراضي | الوصف |
|---------|-----------|-------|
| `SYNDICATE_API_ALLOWED_IPS` | فارغ (بدون تقييد) | IP/CIDR مفصولة بفواصل — **يجب تعبئتها في الإنتاج** |
| `SYNDICATE_API_RATE_LIMIT` | 60 | طلب/دقيقة لكل حساب |
| `SYNDICATE_API_IDEMPOTENCY_TTL` | 24 | ساعات الاحتفاظ بمفاتيح Idempotency |
| `SYNDICATE_API_LOG_BODIES` | true | تسجيل أجسام الطلبات (مع إخفاء الحقول الحساسة) |
| `SYNDICATE_WEBHOOK_ENABLED` / `_URL` / `_SECRET` / `_TIMEOUT` | false | إشعار صادر للنقابة موقع بـ `X-Syndicate-Signature: sha256=HMAC(body)` |
| `SYNDICATE_NOTIFY_MAIL` / `_SMS` / `_WEBPUSH` | true | قنوات الإشعار |
| `SYNDICATE_COUNCIL_EMAILS` | فارغ | بريد إضافي لمجلس المدينة لاستلام إشعارات الإيقاف |
| `SYNDICATE_SMS_DRIVER` | log | مزود SMS؛ اربط تنفيذاً لـ `Contracts\SmsGateway` |

ربط مزود SMS فعلي:

```php
$this->app->bind(\Modules\Syndicate\Contracts\SmsGateway::class, \App\Sms\VodafoneGateway::class);
```

## قاعدة البيانات

| الجدول | الغرض |
|--------|-------|
| `syndicate_projects` | المواقع (المالك، العنوان، المركز، الترخيص، `work_status`, `suspended_at`, `active_certificate_id`) |
| `syndicate_requests` | المعاملات (`transaction_number`, `type`, `status`, `requested_by`, `channel`) |
| `syndicate_certificates` | **الجدول القديم بعد توسيعه** (لم يُعد إنشاؤه): `number`, `kind`, `status`, `project_id`, `request_id`, `cancels_certificate_id`, بيانات المهندس والمكتب... الصفوف القديمة نُقلت لمشروعات تلقائياً |
| `syndicate_engineers` | دليل المهندسين والمكاتب |
| `syndicate_events` | الخط الزمني / التدقيق |
| `syndicate_api_logs` | سجل تدقيق كل استدعاءات الـ API |
| `syndicate_integration_accounts`, `syndicate_idempotency_keys`, `syndicate_sequences` | التكامل والترقيم |

```bash
php artisan migrate --path=modules/Syndicate/database/migrations --force
```

## البيانات التجريبية والأدوار

```bash
php artisan db:seed --class="Modules\Syndicate\database\seeders\SyndicateDemoSeeder"
```

ينشئ الدورين `نقابة المهندسين` و`مجلس المدينة`، و5 مهندسين، و5 مشروعات تغطي كل الحالات (نشط، موقوف، عائد للعمل، بانتظار النقابة، قيد المراجعة)
وطلب إلغاء مفتوح. آمن للتكرار. (مساحة الاسم بأحرف صغيرة `database\seeders` لتطابق المسار على Linux.)

## الاختبارات

```bash
php artisan test --testsuite=Syndicate
```

تغطي: دورة الحياة الكاملة (إشراف ← إلغاء ← موقوف ← إشراف جديد ← نشط)، شهادة سارية واحدة، قواعد الرفض، الإشعارات لكل الأطراف وقنواتها،
Webhook الموقّع، مصادقة الـ API والقدرة وقائمة IP، البحث برقم المعاملة، Idempotency، سجل التدقيق، أمر `syndicate:token`،
صفحات التحقق والاستعلام ولوحة المواطن والطباعة، صلاحيات اللوحات، وإجراءات Filament عبر Livewire.
