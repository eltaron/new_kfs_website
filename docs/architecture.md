# معمارية البوابة — مونوليث معياري / Architecture — Modular Monolith

> بوابة محافظة كفر الشيخ (Laravel 12 + Filament 3.3 + Livewire 3).
> هذه الوثيقة هي المرجع للخطوة الأولى نحو الخدمات المصغّرة: تقسيم التطبيق الواحد إلى
> **سياقات محدودة (Bounded Contexts)** داخل `modules/<Name>` مع حدود وتبعيات واضحة،
> دون تغيير أي رابط أو اسم مسار أو بيانات مخزّنة.

---

## 1. المبادئ / Principles

1. **نفس قاعدة البيانات، ملكية منفصلة للجداول** — every table has exactly one owning module (section 3).
   Other modules may *read* through the owner's contract/service, never write directly.
2. **روابط ثابتة** — moving code never changes URIs, route names or middleware.
   `routes/web.php` keeps only platform routes; each module loads `modules/<Name>/routes/web.php`
   (middleware `web`) and `routes/api.php` (prefix `api`, middleware `api`) from its service provider.
3. **أنواع Morph المخزّنة لا تتغير** — stored polymorphic strings are never rewritten:
   * models with a short alias keep it (`service_submission`, `gis_submission`, `fulfillment_fee`,
     `landmark_booking`, `tour_booking`, `removal_order`, `program_enrollment`, `market_order`, ...);
   * every other moved model keeps its **legacy FQCN** as morph class through
     `Relation::morphMap(['App\Models\X' => Modules\Y\Models\X::class])`
     (registered automatically by `App\Support\Modules\ModuleServiceProvider`), so rows such as
     `activity_log.subject_type = 'App\Models\Post'` keep resolving and new rows keep the same value.
4. **أسماء الأصناف القديمة تعمل** — each moved class leaves a 3-line `class_alias` stub at its old
   path (`app/Models/X.php`, `app/Http/Controllers/X.php`). The alias is the *same* class
   (not a subclass), so `instanceof`, type-hints, Livewire snapshots and serialized jobs keep working.
5. **السياسات (Policies)** stay in `app/Policies` for now; the module provider registers
   `App\Policies\XPolicy` for every moved model explicitly (auto-discovery guesses from the namespace).
6. **Filament** resources/pages/widgets stay in `app/Filament/**` (admin, gis, estidama panels) for now;
   they reference the module models by their new FQCN. Moving them per module (`discoverResources`
   per module) is phase 2 — see roadmap.
7. **Views** stay in `resources/views` (shared layout `layouts/app.blade.php`); every module also gets a
   view namespace (`content::`, `gis::`, ...) for new views.

### كيف تتحدث الوحدات مع بعضها / Allowed dependencies

A module may depend only on:

| مسموح / Allowed | مثال / Example |
|---|---|
| **Identity shared kernel** | `App\Models\User`, `App\Models\Role`, `App\Models\Permission`, 2FA, `auth` guard |
| **Payments contract** | `App\Contracts\Payable`, `App\Concerns\HasPayments`, `route('payments.pay', [type, id])`, event `App\Events\PaymentCompleted` |
| **Domain events** | Laravel events listed in the event catalogue (section 4) |
| **Contracts of another module** | interfaces in `modules/<X>/Contracts` bound in `<X>ServiceProvider` |
| **Platform** | `App\Support\*`, `App\Services\Security\*`, `App\Http\Middleware\*`, Laravel/Filament/Spatie |

**Forbidden**: `use Modules\Other\Models\...` from inside `modules/<This>` — enforced by
`tests/Architecture/ModuleBoundariesTest.php`. Existing cross-module model references at the time of the
split are **frozen in an allow-list** in that test (they are the backlog of contracts to write);
the test fails on any *new* one.

`app/` (Filament panels, `PaymentServiceProvider`, `DashboardMonitorController`, policies, exports,
seeders) is the **composition root** of the monolith and may still reference any module's models.

---

## 2. السياقات المحدودة / Bounded contexts

| الوحدة / Module | المسؤولية | Namespace | Routes (names) |
|---|---|---|---|
| **Identity** (shared kernel) | المستخدمون، الأدوار، الصلاحيات، المصادقة الثنائية، استعادة كلمة المرور، الملفات المحمية | `Modules\Identity` (+ `App\Models\User/Role/Permission` stay in `app/` as kernel) | `two-factor.*`, `password.*`, `secure-files.show` |
| **Content** | الأخبار، الفعاليات، المسؤولون، بيانات المحافظة، الإعدادات، السلايدر، الإحصاءات، الشركاء، الدليل، البحث، نتائج الامتحانات، المراكز والقرى والشياخات المرجعية | `Modules\Content` | `home`, `wait`, `search`, `posts.*`, `comments.store`, `directory.index`, `officials.show`, `about`, `about.governor`, `exam-results.*`, `employee.erp.index` |
| **Gis** | الخدمات المكانية، بوابة GIS، الخريطة التفاعلية، فواتير GIS | `Modules\Gis` | `gis.index`, `gis.service.show`, `gis.tracking`, `gis.apply.*`, `gis.print`, `gis.invoice.*`, `governorate.map` |
| **RemovalGovernance** | أوامر الإزالة ودورة اعتمادها وتصدير GeoJSON/Shapefile | `Modules\RemovalGovernance` | `gis.removal.*` |
| **Estidama** | التدريب ونظام إدارة التعلم (LMS) والشهادات | `Modules\Estidama` | `estidama.*`, `lms.*` |
| **Tourism** | المعالم، المواقع، حجوزات المعالم والجولات، دليل المدينة | `Modules\Tourism` | `landmarks.*`, `bookings.store`, `tour-bookings.*` |
| **Investment** | الاستثمار، المشروعات، الخطط، حياة كريمة، أدلة المراكز، رسائل المستثمرين، دليل الإعلانات | `Modules\Investment` | `investments.*`, `projects.*`, `investment.*`, `center-guides.*`, `advertising.guide` |
| **Citizen** | الخدمات وطلباتها، الشكاوى، المقترحات، البلاغات الطارئة، التواصل، الاستبيانات، تسجيل/دخول المواطن ولوحته، الاستعلام | `Modules\Citizen` | `services.*`, `complaints.*`, `suggestions.*`, `emergency.*`, `contact.*`, `surveys.*`, `register.citizen*`, `login`, `logout`, `citizen.*`, `citizen-lookup*`, `requests.index`, `api.voice-complaint` |
| **Payments** | الدفع الموحد (e-Finance)، المعاملات، رسوم الاستيفاء، الإيصالات | `Modules\Payments` (+ `App\Services\Payments`, `App\Contracts\Payable` stay in `app/` as the published contract) | `payments.*`, `efinance.*`, `receipt.print` |
| **Syndicate** (existing) | نقابة المهندسين — الإشراف والشهادات | `Modules\Syndicate` | `syndicate.*` |
| **Marketplace** (existing) | متجر ذوي الهمم | `Modules\Marketplace` | `store.*`, … |

Remaining in `routes/web.php` (platform): `dashboard-monitor` (cross-module back-office read model) and
legacy comments. `routes/api.php` keeps `api.fcm-token` (Identity device token, platform).

---

## 3. ملكية الجداول / Table ownership

| Module | Tables (owner writes; others read via contracts) |
|---|---|
| Identity | `users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `password_reset_tokens`, `sessions`, `personal_access_tokens`, `notifications`, `push_subscriptions`, `activity_log` (platform audit) |
| Content | `posts`, `comments`, `categories`, `officials`, `official_roles`, `governorate_details`, `settings`, `hero_sliders`, `statistics`, `partners`, `directory_entries`, `famous_people`, `images`, `employees`, `centers`, `markazs`, `villages`, `shiakhas`, `exam_results` |
| Gis | `gis_markazs`, `gis_shiakhas`, `gis_villages`, `gis_service_types`, `gis_sub_services`, `gis_study_reasons`, `gis_submissions`, `gis_reports` |
| RemovalGovernance | `removal_orders` |
| Estidama | `training_programs`, `program_modules`, `program_lessons`, `lesson_progress`, `enrollments`, `exams`, `exam_questions`, `exam_submissions`, `training_applications`, `training_centers`, `training_certificates`, `badges`, `estidama_events`, `partner_logos` |
| Tourism | `landmarks`, `locations`, `bookings`, `tour_bookings`, `city_guide_categories`, `city_villages` |
| Investment | `investments`, `investment_messages`, `investment_plans`, `projects`, `hayah_karima_projects`, `center_guides`, `advertisements` |
| Citizen | `services`, `service_submissions`, `service_surveys`, `complaints`, `suggestions`, `emergency_reports`, `contact_messages` |
| Payments | `transactions`, `fulfillment_fees` |
| Syndicate | `syndicate_*` |
| Marketplace | `market_*` |

(Exact table names: see each model's `$table`; the list is maintained by the module owner.)

---

## 4. كتالوج الأحداث / Event catalogue

| Event | Producer | Payload | Consumers (today / planned) |
|---|---|---|---|
| `App\Events\PaymentCompleted` | Payments | `Transaction` (+ payable via morph) | Citizen (submission → paid), Gis, Tourism (booking confirmed), Estidama (enrollment activated), Marketplace (order paid) — today through `Payable::markPaid()` callback, planned as async listeners |
| `payment.failed` *(planned)* | Payments | transaction id, payable type/id | same as above (`markPaymentFailed`) |
| `citizen.submission.created` *(planned)* | Citizen | submission id, service id, user id | Payments (fees), notifications |
| `gis.submission.created` / `gis.submission.status-changed` *(planned)* | Gis | submission id, status | Payments, Citizen dashboard |
| `removal.order.approved` *(planned)* | RemovalGovernance | order id, step | Gis map layers, notifications |
| `estidama.enrollment.completed` / `estidama.certificate.issued` *(planned)* | Estidama | enrollment id, certificate serial | Citizen dashboard, notifications |
| `tourism.booking.confirmed` *(planned)* | Tourism | booking id | notifications |
| `investment.message.received` *(planned)* | Investment | message id | notifications |
| `identity.user.registered` / `identity.user.roles-changed` *(planned)* | Identity | user id | all modules (cache/projections) |
| `syndicate.*`, `marketplace.*` | Syndicate / Marketplace | see module docs | notifications |

Rule: events carry **ids and primitive values only** (no Eloquent models) so they can be serialized to a
broker unchanged when the module is extracted.

---

## 5. ما الذي نُقل / What moved (phase 1)

* Models `app/Models/X.php` → `modules/<Name>/Models/X.php` (stub alias left behind).
* Controllers `app/Http/Controllers/X.php` → `modules/<Name>/Http/Controllers/X.php` (stub alias left behind).
* Route definitions → `modules/<Name>/routes/{web,api}.php` (identical URIs / names / middleware;
  verified by comparing `php artisan route:list` and route matching before/after).
* `<Name>ServiceProvider` (extends `App\Support\Modules\ModuleServiceProvider`) registered in
  `bootstrap/providers.php`.

| Module | Models (`Modules\<Name>\Models`) | Controllers (`Modules\<Name>\Http\Controllers`) |
|---|---|---|
| Identity | — (User/Role/Permission stay in `App\Models`) | TwoFactor, ForgotPassword, ResetPassword, SecureFile |
| Content | Post, Comment, Category, Official, OfficialRole, GovernorateDetail, Setting, HeroSlider, Statistic, Partner, DirectoryEntry, FamousPeople, Image, Employee, Center, Markaz, Village, Shiakha, ExamResult | Home, AboutPage, Post, Comment, Search, Directory, Officials, Governorate, ExamResult, Erp |
| Gis | GisMarkaz, GisReport, GisServiceType, GisShiakha, GisStudyReason, GisSubmission, GisSubService, GisVillage | GisPortal, Map |
| RemovalGovernance | RemovalOrder | (closure routes moved verbatim) |
| Estidama | Badge, Enrollment, EstidamaEvent, Exam, ExamQuestion, ExamSubmission, LessonProgress, ProgramLesson, ProgramModule, TrainingApplication, TrainingCenter, TrainingCertificate, TrainingProgram, PartnerLogo | Estidama, `Estidama\{Learning,Exam,Certificate}` |
| Tourism | Landmark, Location, Booking, TourBooking, CityGuideCategory, CityVillage | Landmark, Booking, TourBooking |
| Investment | Investment, InvestmentMessage, InvestmentPlan, Project, HayahKarimaProject, CenterGuide, Advertisement | Investment, Project, InvestmentPlan, HayahKarima, CenterGuide, InvestmentContact, AdvertisingGuide |
| Citizen | Service, ServiceSubmission, ServiceSurvey, Complaint, Suggestion, EmergencyReport, ContactMessage | Service, ServiceSubmission, ServiceSurvey, Complaint, Suggestion, EmergencyReport, Contact, RegisterCitizen, LoginCitizen, Dashboard, CitizenLookup, Request, `Api\VoiceComplaint` |
| Payments | Transaction, FulfillmentFee | Payment, Receipt |

Every reference in the codebase (Filament, policies, seeders, factories, tests, views) was rewritten to the
new FQCN, so `X::class` equals the real class name everywhere; the `App\...` stubs only serve stored strings
and third-party/legacy references. Factories stay in `database/factories`; moved models that have one
declare `newFactory()`.

**How equivalence was verified** (phase 1): a snapshot taken before and after each module of
(a) every route's methods/URI/name/action/middleware/wheres, (b) which route a sample URL of every route
*matches* (registration order changed: module routes now load before `routes/web.php`), (c) for every model
the morph class, resolved policy (by instance and by class string), table, foreign key and factory, and
(d) Filament `getModelResource()` for every resource — all identical; `route:list` 471 routes before and after.
* **Not moved yet** (documented debt): Filament resources/pages/widgets (`app/Filament/**`), policies
  (`app/Policies`), views (`resources/views`), services (`app/Services/{Lms,Payments,ArcGisService,EFinanceService}`),
  exports, seeders/factories, migrations (still in `database/migrations`), `User/Role/Permission`
  (Identity kernel stays in `app/Models`), `SyndicateCertificate` (owned by the Syndicate team).

---

## 6. خارطة الطريق للخدمات المصغّرة / Roadmap to microservices

### 6.1 Target platform

* **API Gateway** (Kong / Traefik / NGINX) in front of everything: routes `/store/*` → marketplace service,
  `/syndicate/*` → syndicate service, … and everything else → the monolith (strangler fig).
* **SSO**: Identity becomes an OAuth2/OIDC provider — either Laravel Passport inside the Identity service
  or **Keycloak** (recommended for the government context: 2FA/TOTP, LDAP, realm roles mapped from the
  Arabic Spatie role names). Services validate JWTs locally (JWKS), roles/permissions in claims.
* **Database per service**: each extracted service gets its own MySQL schema/instance containing only the
  tables it owns (section 3). Cross-service reads go through APIs or local read-model projections fed by events.
* **Message broker**: RabbitMQ (topic exchange `kfs.events`) or Redis Streams; Laravel queue workers consume.
* **Transactional outbox**: each service writes domain events to an `outbox` table in the same DB
  transaction as the state change; a relay publishes them to the broker (at-least-once) and consumers are
  idempotent (event id dedup table). Payments callbacks are already idempotent.
* **Shared files**: private uploads move to S3-compatible object storage (MinIO) with signed URLs instead
  of `secure-files.show`.
* **Observability**: correlation id header propagated by the gateway; central logs (Loki/ELK).

### 6.2 Extraction order (strangler fig)

| # | Module | Why this order | Pre-requisites |
|---|---|---|---|
| 1 | **Marketplace** | already self-contained (own panels, own tables, payable alias) | SSO, Payments API |
| 2 | **Syndicate** | own panels/API/tables; Sanctum tokens already | SSO, notifications via events |
| 3 | **Estidama / LMS** | own panel; few links (User, payments) | SSO, `PaymentCompleted` over broker |
| 4 | **Tourism** | small, public read + bookings | Payments API |
| 5 | **Investment** | mostly content-like reads + messages | — |
| 6 | **Gis + RemovalGovernance** | heavy spatial data, ArcGIS integration, own panel | Payments API, file storage |
| 7 | **Citizen** | central citizen flows, depends on most modules (dashboard aggregates) | read models for dashboard |
| 8 | **Content** | last: the shell/home page that aggregates everything becomes the BFF/front | all of the above |

Payments and Identity are platform services extracted alongside step 1 (they are prerequisites).

### 6.3 Per-module extraction checklist

1. Remove every allow-listed cross-module model reference → contract/event (architecture test list must be empty for that module).
2. Move its Filament resources to the module (`discoverResources(in: modules/<X>/Filament/...)`).
3. Move migrations to `modules/<X>/database/migrations`; create the dedicated schema and copy data.
4. Publish events through the outbox; replace synchronous `markPaid()` callbacks with `PaymentCompleted` consumers.
5. Deploy the module as its own Laravel app (same code, only its provider enabled), route it via the gateway.
6. Delete the module from the monolith (keep alias stubs one release for stored strings, then data-migrate morph types to short aliases).

### 6.4 docker-compose sketch

See [`docker-compose.example.yml`](docker-compose.example.yml) — **illustrative only, not wired**.

---

## 7. إضافة وحدة جديدة / Adding a module

```
modules/<Name>/
  <Name>ServiceProvider.php   extends App\Support\Modules\ModuleServiceProvider
  Contracts/                  interfaces other modules may use
  Models/  Http/Controllers/  Services/  Events/
  routes/web.php  routes/api.php
  resources/views  database/migrations  tests/
```
Register the provider in `bootstrap/providers.php` (after `PaymentServiceProvider` so payable aliases win).
