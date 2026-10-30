# KFS Design System — v2 (الإصدار الحالي المعتمد)

> v2 (أكتوبر 2026) يَحِلّ محل أي قاعدة تتعارض معه في الأقسام القديمة أدناه (§1 وما بعده تبقى كمرجع للهوية والألوان فقط).
> الهدف: بوابة حكومية **موثوقة، هادئة، واضحة، سهلة القراءة** — فخامة من التناسق والفراغ لا من المؤثرات.
> التنفيذ: `public/css/kfs-ds.css` (tokens + base + header + components) · `kfs-motion.css` (الحركة فقط) · `kfs-sections.css` (footer / empty / forms / prose) · `kfs-portal.css` (بوابة المواطن).

## v2.1 توزيع الألوان (نفس الباليت، استخدام مُعاد التوازن)

| الدور | Token | القيمة | الاستخدام |
|---|---|---|---|
| خلفية الصفحة | `--kfs-bg` | `#FCFBF7` | عاجي فاتح جداً (لا أصفر) |
| شريط بديل | `--kfs-bg-alt` | `#F4F2EA` | تبادل خلفيات الأقسام |
| أسطح | `--kfs-surface` | `#FFFFFF` | كل البطاقات والحقول |
| البنية | `--kfs-dark` / `--kfs-dark-2` | `#26302D` / `#1D2422` | الهيدر، الفوتر، لوحة CTA واحدة، أشرطة الصفحات الداخلية |
| **الإجراء الأساسي** | `--kfs-primary` | `#00788A` (hover `#005F6D`) | الأزرار الأساسية، الروابط، أيقونات البطاقات (AA مع الأبيض 5:1) |
| تلوين خفيف | `--kfs-primary-soft` | `#E5F4F6` | مربعات الأيقونات، الشارات |
| سماوي العلامة | `--kfs-cyan` `#00BFD8` | | حلقة التركيز، خط التبويب النشط فقط |
| ذهبي (لمسة) | `--kfs-gold` `#D8B03E` | | شريط عنوان القسم 36×3، زر واحد على الخلفيات الداكنة، شارة «فعالية/استثمار» |
| نص | `--kfs-charcoal` / `--kfs-text` / `--kfs-text-soft` | | عناوين / متن / وصف |

قواعد: لا خلفيات ذهبية واسعة · لا تدرجات ملونة على البطاقات · لون دلالي (أحمر) فقط للطوارئ/الأخطاء.

## v2.2 المسافات · الزوايا · الظلال

- **Spacing (4-based):** `--sp-1..9` = 4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 · 96px. حشوة القسم `--section-y: clamp(56px, 7vw, 96px)`؛ فجوة الشبكات `clamp(16px, 2vw, 24px)`.
- **Radius:** `--r-sm 6` (شارات) · `--r 8` (أزرار، حقول) · `--r-lg 12` (بطاقات) · `--r-xl 18` (لوحات كبيرة) · `--r-pill` (رقائق التصفية).
- **Elevation:** `--e-1` بطاقة في حالة السكون (مع حد 1px `--line`) · `--e-2` hover/عناصر عائمة · `--e-3` القوائم المنسدلة والنوافذ.

## v2.3 الخطوط والمقاسات

Tajawal (العناوين 700/800) · IBM Plex Sans Arabic (المتن 400/500/600) · أرقام الإحصاءات بخط Tajawal 800 (tabular).

| Token | المقاس | الاستخدام |
|---|---|---|
| `--fs-2xl` | clamp(1.75→2.75rem) | عنوان الصفحة (Page hero) |
| `--fs-xl` | clamp(1.45→1.875rem) | عنوان القسم H2 |
| `--fs-lg` | 1.25rem | عنوان بطاقة مميزة |
| `--fs-md` | 1.0625rem | عنوان البطاقة |
| `--fs-base` | 1rem / 1.85 | المتن |
| `--fs-sm` / `--fs-xs` | .875 / .8125rem | وصف، بيانات وصفية، شارات |

## v2.4 المكوّنات

- **Section title** `<x-kfs.section-title>` → `.kfs-stitle`: شريط ذهبي 36×3 · H2 · سطر وصف · رابط «عرض الكل» كزر محدد (outline) في نهاية السطر · slot اختياري `tools` لأزرار السلايدر. (أُلغي ترقيم «01/02» والموجة.)
- **Buttons:** `.btn-kfs` / `.btn-primary` = تيل أساسي · `.btn-kfs-outline` ثانوي · `.btn-kfs-gold` / `.btn-modern-gold` ذهبي على الخلفيات الداكنة فقط · ارتفاع 44px (صغير 38px) · Tajawal 700 · بلا ripple/glow.
- **Badge** `.kfs-badge` (`--gold`, `--olive`, `--danger`, `--success`, `--warning`, `--dark`): نص + لون دائماً، radius 6. **Chip** `.kfs-chip` للتصفية (pill، النشط داكن).
- **Card base:** أبيض · حد 1px · radius 12 · `--e-1`؛ hover: رفع 3px + `--e-2` + حد مائل للتيل؛ focus: إطار 3px. البطاقة كلها رابط واحد، ومتساوية الارتفاع داخل `.kfs-grid--4/3/2` (4→3→2→1 عمود عند 1200/992/576).
  - **Service** `.kfs-svc`: أيقونة 52px داخل مربع ملوّن خفيف radius 12 · عنوان سطران · وصف سطران · «ابدأ الخدمة ←» · حشوة 24 · على الجوال تتحول لصف مضغوط (أيقونة + نص + سهم). متغيرات: `--feature` (أفقية بخط ذهبي علوي) · `--gold` · `--danger`.
  - **News** `.kfs-news`: صورة 16:9 + شارة تاريخ بيضاء على الصورة · شارة تصنيف · عنوان سطران · مقتطف سطران · «اقرأ المزيد». متغيرات `--feature` و`--row`.
  - **Event** `.kfs-event`: مثل الخبر داخل Swiper بلا تشغيل تلقائي، أزرار تنقل في رأس القسم.
  - **Tourism** `.kfs-tour`: صورة كاملة 3:4 (`--wide` 16:10) مع تدرج سفلي وعنوان أبيض.
  - **Investment** `.kfs-inv`: أفقية (صورة 40%) تتحول عمودية على الجوال.
  - **Media** `.kfs-media` (مشروعات/عام): صورة 16:10 + شارة + عنوان + رابط.
  - **Stat** `<x-kfs.stat-counter>` → `.kfs-stat2`: أيقونة 56px ملونة + رقم + وصف.
- **Panels:** `.kfs-panel` (أبيض، حد، radius 12) · `.kfs-panel__title` · `.kfs-detail` (محتوى + عمود جانبي 340px لاصق) · `.kfs-linklist`.
- **Page hero** `<x-kfs.page-hero>`: شريط داكن هادئ، مسار تنقل أولاً، عنوان ووصف بمحاذاة البداية، خط ذهبي سفلي 3px — بلا أشكال طافية أو موجة متحركة.
- **Header:** شريط حكومي بارتفاع ثابت `--govbar-h` + شريط تنقل `--header-h`؛ الـ `<header>` لاصق عند `top: -var(--govbar-h)` فيختفي الشريط العلوي طبيعياً ويبقى التنقل ثابتاً دون أي تغيير في الأبعاد (لا قفزات ولا فراغ).

## v2.5 الحركة

- تظهر فقط عند `html.kfs-motion` (JS + بدون «إيقاف الحركة» `kfs-a11y-v1.noanim` + بدون `prefers-reduced-motion`).
- Reveal: fade-up 16px خلال .6s، تتابع 60ms للبطاقات · دخول الهيرو مرة واحدة 14px · hover رفع 3px خلال .28s · صور البطاقات تكبير 1.04.
- ممنوع: أشكال طافية، parallax، لمعان ذهبي، ripple، شريط أخبار متحرك، تشغيل تلقائي للسلايدرات (عدا الهيرو 7s ويتوقف مع «إيقاف الحركة»).

## v2.6 ترتيب الصفحة الرئيسية

الهيرو (+ بحث سريع) → القيم (secondary hero) → أهم الأحداث → الأخبار (مميز + أحدث) → الخدمات → السياحة (سلايدر) → الاستثمار → المشروعات القومية/حياة كريمة → الخطة الاستثمارية (CTA) → الإنجازات → مواقع تهمك (تبويبات) → المحافظة في أرقام → دليل المدينة (خريطة) → إضافات: متجر ذوي الهمم، بوابات حكومية. كل قسم محمي بشرط ويختفي بهدوء إذا كانت قاعدة البيانات فارغة.


## v2.7 الوضع الليلي / النهاري (Theme)

- **الآلية:** `<html data-theme="light|dark" data-bs-theme="…">` يضبطها سكربت مضمَّن في `<head>` (`layouts/app.blade.php`) قبل رسم أي CSS (بدون وميض). **الافتراضي نهاري دائماً**؛ الليلي فقط عند اختيار الزائر صراحةً (`localStorage['kfs-theme'] === 'dark'`) — لا يُتّبع إعداد نظام التشغيل. يُحدَّث `color-scheme` و`<meta name="theme-color">`، ويتزامن الاختيار بين التبويبات.
- **API:** `window.KFSTheme.get()` · `.set('light'|'dark'|'auto')` · `.toggle()` · حدث `kfs:themechange`. أي زر يحمل `data-theme-toggle` يصبح مفتاح تبديل (aria-pressed + aria-label تلقائياً).
- **نقاط الدخول:** زر شمس/قمر في الهيدر بجوار البحث (ظاهر على الجوال) · خيار «الوضع الليلي» في لوحة أدوات الإتاحة · أوامر صوتية «الوضع الليلي» / «الوضع النهاري» / «تبديل الوضع».
- **الألوان:** `public/css/kfs-theme.css` (يُحمَّل آخر ملف) يعيد تعريف الـ tokens تحت `:root[data-theme="dark"]`: خلفية `#141A18` · شريط بديل `#1A211F` · أسطح `#1E2624` · نص `#ECE8D6` / `#B9B4A0` · أساسي `#2BC4DA` · ذهبي `#E2BE55`. الأزرار المعبأة بلون مميز تستخدم نصاً داكناً `--kfs-ink-on-accent` (#0F1C1B).
- **قواعد للمطوّرين:** لا تكتب `#fff` كخلفية مكوّن — استخدم `var(--kfs-surface)`؛ ولا `var(--kfs-dark)` كلون نص — استخدم `var(--kfs-charcoal)`. الهيدر والفوتر وأقسام السياحة/الأرقام تبقى داكنة في الوضعين. الشعارات تبقى على لوحتها الفاتحة. الانتقال 220ms ويتوقف مع `prefers-reduced-motion` و«إيقاف الحركة».
- لوحات Filament خارج هذا النظام (لها وضعها الليلي الخاص).
---

# Design System — KFS Egyptian Identity
## نظام التصميم الرسمي لمحافظة كفر الشيخ — مستوحى من الشعار الرسمي

نظام تصميم حكومي مصري فاخر، هادئ، حديث وموثوق، مبني بصرياً حول الهوية الموجودة في شعار محافظة كفر الشيخ.

- **الذهبي الأصفر**: اللون الرئيسي للهوية، مستوحى من المركب والعناصر الذهبية في الشعار.
- **السماوي / الفيروزي**: مستوحى من أمواج الماء، ويرمز إلى بحيرة البرلس والنيل والمياه.
- **العاجي الدافئ**: مستوحى من خلفية اللوحة داخل الشعار.
- **البني البرونزي**: لون تفصيلي مستوحى من الخطوط الخشبية/البرونزية في الشعار.
- **الأخضر الزيتوني الهادئ**: لون مساعد محدود (الزراعة والطبيعة).
- **الفحمي الدافئ**: للنصوص بدلاً من الأسود الصريح.

النتيجة: **Governmental · Egyptian · Coastal · Heritage · Premium · Digital**.

---

## 1) فلسفة الهوية

> **تراث كفر الشيخ × الماء × الأرض × الدولة المصرية × التحول الرقمي**

1. الشعار هو مصدر الهوية، وليس مجرد Logo في الهيدر.
2. السماوي والذهبي هما بطلا الهوية.
3. العاجي هو الخلفية الرئيسية بدلاً من الأبيض البارد.
4. البني والأخضر الزيتوني تفاصيل مساعدة فقط.
5. لا نستخدم الأخضر الزمردي كلون أساسي.
6. لا نستخدم الذهبي كخلفية واسعة؛ الذهبي للتأكيد فقط.
7. الماء يظهر في الحركة والخطوط والزخارف.
8. الطابع المصري في الهندسة والمواد والفراغ، لا في تكديس الرموز الفرعونية.
9. الموقع حكومي أولاً: واضح، رسمي، Accessible.
10. الفخامة من التناسق والفراغ والتفاصيل، لا من كثرة المؤثرات.

## 2) Palette

```css
:root {
  /* GOLD */
  --kfs-gold: #D8B03E;
  --kfs-gold-dark: #B88920;
  --kfs-gold-deep: #8F6917;
  --kfs-gold-light: #F0D878;
  --kfs-gold-soft: rgba(216,176,62,.16);
  /* WATER / CYAN */
  --kfs-cyan: #00BFD8;
  --kfs-cyan-deep: #008FA5;
  --kfs-cyan-light: #39D9E8;
  --kfs-cyan-soft: rgba(0,191,216,.12);
  /* IVORY / PAPYRUS */
  --kfs-ivory: #FFFDE1;
  --kfs-ivory-2: #FAF6D7;
  --kfs-papyrus: #F2EBC7;
  --kfs-sand: #E9DFB7;
  /* BRONZE */
  --kfs-bronze: #795329;
  --kfs-bronze-light: #A9793C;
  --kfs-bronze-soft: rgba(121,83,41,.12);
  /* OLIVE */
  --kfs-olive: #657342;
  --kfs-olive-deep: #48552F;
  --kfs-olive-soft: rgba(101,115,66,.12);
  /* TEXT */
  --kfs-charcoal: #282724;
  --kfs-text: #393733;
  --kfs-text-soft: #68645A;
  --kfs-muted: #929083;
  /* DARK SURFACES */
  --kfs-dark: #26302D;
  --kfs-dark-2: #1D2422;
  --kfs-dark-soft: #34413D;
  /* SYSTEM */
  --success: #3D7A50;
  --warning: #B7791F;
  --danger: #A63A32;
  --info: #147C92;
  /* BORDERS */
  --line: rgba(40,39,36,.11);
  --line-gold: rgba(216,176,62,.34);
  --line-cyan: rgba(0,191,216,.25);
}
```

## 3) قواعد استخدام الألوان

| الاستخدام | اللون |
|---|---|
| الخلفية الأساسية | `--kfs-ivory` |
| الخلفية الثانوية | `--kfs-ivory-2` / `--kfs-papyrus` |
| Primary CTA | `--kfs-gold-dark` أو `--kfs-gold` |
| Links / Water / Digital | `--kfs-cyan-deep` |
| Header | `--kfs-dark` |
| Footer | `--kfs-dark-2` |
| العناوين | `--kfs-charcoal` |
| النصوص الثانوية | `--kfs-text-soft` |
| التفاصيل التراثية | `--kfs-bronze` |
| الطبيعة والزراعة | `--kfs-olive` |

النسب: Ivory/Papyrus 55–65% · Dark 15–20% · Cyan 8–12% · Gold 5–10% · Bronze/Olive 2–5%.
لا تجعل الموقع بالكامل أصفر أو سماوياً.

## 4) Typography

- Arabic headings: **Tajawal 700/800** — `--ff-head`
- Arabic body: **IBM Plex Sans Arabic 300–700** — `--ff-body`
- English: **Inter 400–700** — `--ff-en`
- Decorative numbers: **Playfair Display 500/600** — `--ff-num` (أرقام كبيرة فقط، `font-variant-numeric: lining-nums`)

## 5) Type Scale

```css
.hero-title { font-size: clamp(2.35rem, 5vw, 4.8rem); line-height: 1.2; font-weight: 800; }
.page-title { font-size: clamp(2rem, 4vw, 4rem); line-height: 1.2; font-weight: 800; }
.title-lg   { font-size: clamp(1.8rem, 3.5vw, 3rem); }
.title-md   { font-size: clamp(1.45rem, 2.5vw, 2.1rem); }
body        { font-size: 1rem; line-height: 1.9; }
.lead       { font-size: clamp(1.02rem, 1.3vw, 1.18rem); }
```

لا تستخدم `-webkit-text-stroke` مع العربية.

## 6) Shape & Motion tokens

```css
:root {
  --r: 6px; --r-lg: 10px; --r-xl: 14px;
  --shadow: 0 10px 35px rgba(40,39,36,.07);
  --shadow-lg: 0 25px 70px rgba(40,39,36,.13);
  --shadow-gold: 0 10px 35px rgba(216,176,62,.15);
  --ease: cubic-bezier(.22,.61,.36,1);
  --ease-out: cubic-bezier(.16,1,.3,1);
  --section-y: clamp(72px, 9vw, 125px);
}
```

زوايا هندسية هادئة 6–14px. لا border-radius مبالغ فيه.

## 7) KFS Wave Line
خط موجة سماوي هندسي بسيط (SVG أو CSS) يُستخدم في: فواصل الأقسام، أسفل الـHero، أعلى الفوتر، الزخارف، مؤشرات Active، حالات التحميل.

## 8) KFS Heritage Line
خط ذهبي رفيع مزدوج (1px فوق و1px تحت داخل ارتفاع 8px) مستوحى من إطار المركب.

## 9) Pattern
خطوط موجية + وحدات نباتية مجردة + خطوط بردي + دوائر شمسية صغيرة + هندسة المركب. Opacity بين .025 و .06. ممنوع pattern فرعوني كثيف.

## 10) Header
خلفية `--kfs-dark`، نص أبيض، `border-bottom: 1px solid var(--line-gold)`.
شريط حكومي علوي: «جمهورية مصر العربية | محافظة كفر الشيخ — البوابة الإلكترونية الرسمية» + Accessibility + اللغة + البحث + التواصل.
القائمة: الرئيسية · المحافظة · الخدمات الإلكترونية · المشروعات · الأخبار · البيانات والمعلومات · تواصل معنا.
Active: Cyan underline + Gold micro-indicator.

## 11) Logo
لا تغيير ألوان، لا Glow، لا Shadow قوي، لا تدوير، لا قص، لا خلفية مزدحمة.

## 12–14) Hero
حكومي ومحلي وليس Landing تجارية. «محافظة كفر الشيخ — البوابة الإلكترونية الرسمية — خدماتك الحكومية ومعلومات محافظتك في مكان واحد» + [الخدمات الإلكترونية] [اكتشف المحافظة].
الخلفية: `linear-gradient(120deg, rgba(38,48,45,.98) 0%, rgba(38,48,45,.92) 55%, rgba(0,143,165,.76) 100%)` + pattern خفيف + موجة أسفل.
Kicker: «01 | البوابة الرسمية» — الرقم ذهبي والفاصل سماوي.

## 15) الإحصائيات «كفر الشيخ في أرقام»
أرقام كبيرة + خطوط ذهبية + indicators سماوية. لا أرقام حقيقية إلا من بيانات النظام.

## 16–17) Service Hub / Service Card
```css
.service-card { background: var(--kfs-ivory); border: 1px solid var(--line); border-top: 3px solid transparent;
  border-radius: var(--r-lg); box-shadow: var(--shadow); transition: .5s var(--ease); }
.service-card:hover { transform: translateY(-5px); border-top-color: var(--kfs-cyan); box-shadow: var(--shadow-lg); }
```
الأيقونة داخل إطار هندسي صغير: أيقونة Cyan، حد Gold، خلفية Ivory. لا دوائر ملونة ضخمة.

## 18) News
بطاقة: التاريخ (رقم ذهبي كبير) · العنوان · ملخص سطرين · «اقرأ المزيد ←».

## 19) كلمة المحافظ
خلفية `--kfs-papyrus`، صورة بإطار ذهبي رفيع + موجة سماوية صغيرة. بدون اقتباسات ضخمة.

## 20) مشروعات التنمية
صورة · اسم · المركز · الحالة · نسبة الإنجاز · رابط. الحالة: مكتمل أخضر، قيد التنفيذ ذهبي، مخطط سماوي، متوقف أحمر.

## 21–22) قصة كفر الشيخ / هوية الماء
«كفر الشيخ — أرض الماء والزراعة والتنمية»: الماء → الأرض → الإنسان → التنمية. صور حقيقية للمحافظة فقط. حركة بطيئة جداً.

## 23) الخريطة
Ivory base · Charcoal labels · Cyan water · Gold selected · Olive agriculture.

## 24–26) الجهات والإدارات / التحول الرقمي / Data
التحول الرقمي على `--kfs-dark` مع Cyan + Gold: «محافظة أكثر اتصالاً وخدمات أكثر سهولة». DATA → INFORMATION → KNOWLEDGE → DECISION.

## 27–28) Search / Breadcrumbs
Focus: `border-color: var(--kfs-cyan); box-shadow: 0 0 0 3px var(--kfs-cyan-soft);` — فاصل الـbreadcrumb بلون `--kfs-gold`.

## 29) Buttons
```css
.btn-kfs { background: var(--kfs-gold); color: var(--kfs-charcoal); border: 1px solid var(--kfs-gold); border-radius: 6px; }
.btn-kfs:hover { background: var(--kfs-gold-dark); color: #fff; }
.btn-kfs-cyan { background: var(--kfs-cyan); color: #07343B; border: 1px solid var(--kfs-cyan); }
.btn-kfs-outline { background: transparent; color: var(--kfs-charcoal); border: 1px solid var(--kfs-gold); }
```

## 30–32) CTA / Forms / Documents
CTA حكومي: «أنجز خدمتك إلكترونياً — [ابدأ الآن ←]» على Dark مع Wave + Gold line.
```css
.field { background: var(--kfs-ivory); border: 1px solid var(--line); border-radius: 6px; }
.field:focus-within { border-color: var(--kfs-cyan); box-shadow: 0 0 0 3px var(--kfs-cyan-soft); }
.field.invalid { border-color: var(--danger); }
```
وثيقة رسمية: الاسم · التاريخ · PDF • الحجم · [عرض] [تحميل].

## 33–35) Tables / Cards / Images
```css
.table thead { background: var(--kfs-dark); color: #fff; }
.table tbody tr:hover { background: var(--kfs-ivory-2); }
.card-kfs { background: var(--kfs-ivory); border: 1px solid var(--line); border-radius: var(--r-lg); box-shadow: var(--shadow); }
.kfs-image { object-fit: cover; border-radius: var(--r-lg); border: 1px solid var(--line-gold); }
```

## 36) Assets
`kfs-wave.svg`, `kfs-wave-gold.svg`, `kfs-pattern.svg`, `kfs-pattern-gold.svg`, `kfs-cornice.svg`, `kfs-water-line.svg`, `pwa/` icons.

## 37–39) Floating / Scroll progress / Cursor
مسموح: Accessibility · Contact رسمي · Back to top · Scroll progress (2px Cyan، ينتهي Gold). Cursor مخصص Desktop فقط ويختفي مع `pointer: coarse`.

## 40) Motion
`data-reveal`, `data-delay`, `data-stagger`, `data-lines`, `data-img-reveal`, `data-parallax`, `data-speed`, `data-count`, `data-magnetic`, `data-cursor`, `data-copy`.
Reveal: opacity 0→1، translateY 24px→0. Image reveal: clip-path + scale(.98→1). لا bounce ولا elastic.

## 41–42) Accessibility / Reduced motion
Keyboard · Visible focus · Screen readers · Font resize · High contrast · Reduced motion · Skip to content · أخطاء نماذج واضحة · Semantic HTML · RTL-first.
```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important;
    transition-duration: .01ms !important; scroll-behavior: auto !important; }
}
```

## 43–44) Responsive
≥1400 Hero واسع · <1200 Burger · <992 Hero عمودي، إحصائيات عمودين · <576 `padding-inline:16px`، Header 64–68px، بطاقات عمود واحد، بدون horizontal overflow. اختبار 375/390/414:
`document.documentElement.scrollWidth === document.documentElement.clientWidth`.
Mobile menu: Dark · Gold separators · Cyan active · Wave خفيف.

## 45–47) Footer / Page Hero
Footer `--kfs-dark-2` + موجة Cyan أعلى + Heritage Gold line + Papyrus pattern خفيف جداً. عناوين ذهبية، روابط سماوية، نص عاجي.
Page hero داخلية: Dark + Cyan gradient خفيف + Gold line + Wave + Breadcrumb.

## 48) Status
```css
.status-success { color: var(--success); background: rgba(61,122,80,.09); }
.status-pending { color: var(--warning); background: rgba(183,121,31,.09); }
.status-danger  { color: var(--danger);  background: rgba(166,58,50,.09); }
.status-info    { color: var(--info);    background: rgba(20,124,146,.09); }
```

## 49) Section heading
«01 ───── / العنوان / الوصف» — رقم Gold، خط Cyan، عنوان Charcoal، وصف Text Soft.

## 50) Visual language
WATER (Cyan/Wave/Digital) · LAND (Olive/Papyrus/Agriculture) · HERITAGE (Gold/Bronze/Geometry) — يربطها Dark Government Surface.

## 51) Home composition
Government bar → Header → Hero + Wave → Statistics → Services → News → Governor message → Projects → KFS Story → Map → Departments → Digital transformation → Documents → Links → CTA → Footer + Wave.

## 52–54) Stack
Bootstrap RTL 5.3.3 · Font Awesome 6.5.2 · GSAP 3.13 + ScrollTrigger · Lenis 1.1.20 · Swiper 11.1.15 · GLightbox 3.3 · SweetAlert2 11.14.5 — Laravel 12 / Filament / Livewire / Blade.

## 55) Do / Don't
DO: Ivory أساسية · Cyan للماء والتقنية · Gold للهوية · Bronze/Olive تفاصيل · صور كفر الشيخ الحقيقية · فراغات واسعة · خطوط رفيعة · سرعة.
DON'T: Emerald كهوية · موقع كله Gold · Cyan Neon · Black صريح · White بارد بكثرة · زخارف فرعونية كثيرة · معابد عشوائية · Cyberpunk · Glassmorphism مبالغ · Animations سريعة · بطاقات مستديرة جداً.

## 58) Statement
> الموقع لا يجب أن يبدو كنسخة من الشعار؛ بل كأن الشعار تحوّل إلى نظام رقمي كامل.
رسميّة دون جمود، مصرية دون مبالغة، فاخرة دون استعراض، حديثة دون فقدان الهوية.

## 59) Checklist
Palette جديدة بالكامل · gold/cyan أساسيين · Ivory بدل الأبيض · Header/Footer · Wave SVG · Pattern · Heritage line · Buttons · Service cards · Hero · Statistics · Status badges · Forms · Tables · Mobile menu · RTL · 375px بدون overflow · Keyboard · reduced-motion · Contrast · سرعة الصور والـPWA.
