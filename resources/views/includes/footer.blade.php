@php
    $kfsFooterLogo = !empty($settings['site_logo_footer'])
        ? Storage::url($settings['site_logo_footer'])
        : (!empty($settings['site_logo_header']) ? Storage::url($settings['site_logo_header']) : asset('logo.png'));
    $socials = [
        'facebook'  => ['fa-facebook-f', $settings['social_facebook'] ?? '', 'فيسبوك'],
        'twitter'   => ['fa-x-twitter',  $settings['social_twitter'] ?? '', 'إكس (تويتر)'],
        'instagram' => ['fa-instagram',  $settings['social_instagram'] ?? '', 'إنستجرام'],
        'youtube'   => ['fa-youtube',    $settings['social_youtube'] ?? '', 'يوتيوب'],
        'tiktok'    => ['fa-tiktok',     $settings['social_tiktok'] ?? '', 'تيك توك'],
    ];
    // Fallback to the official accounts already listed in the mobile menu when none are configured.
    if (!collect($socials)->contains(fn ($s) => !empty($s[1]))) {
        $socials['facebook'][1] = 'https://www.facebook.com/KafrelsheikhGovernorate';
        $socials['twitter'][1] = 'https://x.com/kfs_gov';
        $socials['youtube'][1] = 'https://youtube.com/@kafrelsheikhgovernorate583';
        $socials['instagram'][1] = 'https://www.instagram.com/kafr_elsheikh_gov';
    }
    $kfsFooterQuick = [
        ['fa-solid fa-file-pen', 'تقديم شكوى أو بلاغ', 'منظومة الشكاوى الحكومية', route('requests.index')],
        ['fa-solid fa-address-book', 'دليل الهاتف', 'أرقام الطوارئ والخدمات', route('directory.index')],
        ['fa-solid fa-headset', 'الدعم الفني', 'نرد عليك في أقرب وقت', route('contact.index')],
        ['fa-solid fa-map-location-dot', 'الخريطة التفاعلية', 'المراكز والخدمات على الخريطة', route('governorate.map')],
    ];
@endphp
<footer class="site-footer-new kfs-footer kfs-footer--rich" role="contentinfo">
    <div class="kfs-footer__top" aria-hidden="true">
        <x-kfs.wave position="bottom" fill="var(--kfs-footer-bg, var(--kfs-dark-2))" :back="false" />
    </div>
    <div class="kfs-footer__watermark" aria-hidden="true"><x-kfs.lotus :size="320" /></div>
    <div class="container">
        <div class="kfs-footer__help" data-reveal>
            <div class="kfs-footer__help-intro">
                <span class="kfs-footer__help-ico" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span>
                <div>
                    <h2 class="kfs-footer__help-title">كيف نقدر نساعدك؟</h2>
                    <p class="kfs-footer__help-sub">وصول سريع لأهم قنوات خدمة المواطنين.</p>
                </div>
            </div>
            <ul class="kfs-footer__quick" data-stagger>
                @foreach ($kfsFooterQuick as $q)
                    <li>
                        <a href="{{ $q[3] }}" class="kfs-footer__qitem">
                            <span class="kfs-footer__qicon" aria-hidden="true"><i class="{{ $q[0] }}"></i></span>
                            <span class="kfs-footer__qtext"><strong>{{ $q[1] }}</strong><small>{{ $q[2] }}</small></span>
                            <i class="fa-solid fa-arrow-left kfs-footer__qarrow" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <x-kfs.heritage :width="260" light class="kfs-footer__orn" />
    </div>
    <div class="container">
        <div class="kfs-footer__grid" data-stagger>
            <div class="kfs-footer__brand footer-brand-col">
                <img src="{{ $kfsFooterLogo }}" alt="شعار {{ $settings['site_name'] ?? 'محافظة كفر الشيخ' }}" class="footer-logo-main" width="76" height="74" loading="lazy">
                <p class="footer-desc">
                    {{ $settings['footer_description'] ?? 'البوابة الإلكترونية الرسمية لمحافظة كفر الشيخ — نعمل معاً لخدمة المواطن وتحقيق التنمية المستدامة.' }}
                </p>
                <div class="kfs-footer__socials footer-socials">
                    @foreach ($socials as $name => $data)
                        @if ($data[1])
                            <a href="{{ $data[1] }}" class="social-{{ $name }}" aria-label="{{ $data[2] }} (يفتح في نافذة جديدة)" target="_blank" rel="noopener">
                                <i class="fa-brands {{ $data[0] }}" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <nav aria-labelledby="footerGovTitle">
                <h2 class="footer-heading" id="footerGovTitle">المحافظة</h2>
                <ul class="footer-links">
                    <li><a href="{{ route('about') }}"><i class="fa-solid fa-landmark-flag" aria-hidden="true"></i>عن المحافظة</a></li>
                    <li><a href="{{ route('about.governor') }}"><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i>كلمة المحافظ</a></li>
                    <li><a href="{{ route('posts.index') }}"><i class="fa-solid fa-newspaper" aria-hidden="true"></i>الأخبار</a></li>
                    <li><a href="{{ route('projects.index') }}"><i class="fa-solid fa-building-flag" aria-hidden="true"></i>المشروعات</a></li>
                    <li><a href="{{ route('investments.index') }}"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>الاستثمار</a></li>
                    <li><a href="{{ route('landmarks.index') }}"><i class="fa-solid fa-monument" aria-hidden="true"></i>السياحة</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="footerServicesTitle">
                <h2 class="footer-heading" id="footerServicesTitle">الخدمات والتواصل</h2>
                <ul class="footer-links">
                    <li><a href="{{ route('services.index') }}"><i class="fa-solid fa-hand-holding-hand" aria-hidden="true"></i>الخدمات الإلكترونية</a></li>
                    <li><a href="{{ route('requests.index') }}"><i class="fa-solid fa-file-pen" aria-hidden="true"></i>الشكاوى والبلاغات</a></li>
                    <li><a href="{{ route('governorate.map') }}"><i class="fa-solid fa-map" aria-hidden="true"></i>الخريطة التفاعلية</a></li>
                    <li><a href="{{ route('directory.index') }}"><i class="fa-solid fa-address-book" aria-hidden="true"></i>دليل الهاتف</a></li>
                    <li><a href="{{ route('contact.index') }}"><i class="fa-solid fa-headset" aria-hidden="true"></i>تواصل معنا</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="footerLinksTitle">
                <h2 class="footer-heading" id="footerLinksTitle">مواقع مهمة</h2>
                <ul class="footer-links">
                    <li><a href="https://www.egypt.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-desktop" aria-hidden="true"></i>مصر الرقمية</a></li>
                    <li><a href="https://jobs.egypt.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-briefcase" aria-hidden="true"></i>بوابة الوظائف</a></li>
                    <li><a href="https://etenders.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-file-signature" aria-hidden="true"></i>بوابة التعاقدات</a></li>
                    <li><a href="https://www.takaful.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>التضامن الاجتماعي</a></li>
                    <li><a href="https://www.sis.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>الهيئة العامة للاستعلامات</a></li>
                </ul>
            </nav>
        </div>
    </div>
    <div class="kfs-footer__bottom footer-copyright">
        <div class="container">
            <span class="footer-copy-text">
                {!! clean($settings['copyright_text'] ?? 'جميع الحقوق محفوظة &copy; محافظة كفر الشيخ', "cms") !!}
                <span class="footer-year-badge" id="footer-year"></span>
            </span>
            <div class="kfs-footer__tools">
                <button type="button" data-a11y-open><i class="fa-solid fa-universal-access" aria-hidden="true"></i> أدوات الإتاحة</button>
                <button type="button" data-voice-help><i class="fa-solid fa-microphone-lines" aria-hidden="true"></i> الأوامر الصوتية</button>
                <button type="button" data-pwa-install hidden><i class="fa-solid fa-download" aria-hidden="true"></i> تثبيت التطبيق</button>
                <button type="button" data-motion-toggle aria-pressed="false"><i class="fa-solid fa-circle-pause" aria-hidden="true"></i> <span class="lbl">إيقاف الحركة</span></button>
            </div>
        </div>
    </div>
</footer>
