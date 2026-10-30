<aside class="dashboard-sidebar">
    <div class="sidebar-sticky-top">
        @php
            $nameWords = preg_split('/\s+/u', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY);
            $initial = $nameWords ? mb_substr($nameWords[0], 0, 1) : '؟';
            [$statusLabel, $statusTone, $statusIcon] = match (strtolower((string) $user->status)) {
                'approved', 'active' => ['حساب موثّق', 'ok', 'fa-circle-check'],
                'pending' => ['الحساب قيد التوثيق', 'wait', 'fa-hourglass-half'],
                'rejected', 'inactive' => ['الحساب موقوف', 'off', 'fa-circle-xmark'],
                default => ['مواطن', 'ok', 'fa-user'],
            };
            $secured = filled($user->two_factor_confirmed_at ?? null);
            $nid = (string) ($user->national_id ?? '');
        @endphp
        <section class="user-profile-section" aria-label="بيانات الحساب">
            <div class="up-card">
                <div class="avatar-box" aria-hidden="true">
                    <span class="avatar-initial">{{ $initial }}</span>
                    <span class="online-indicator up-tone-{{ $statusTone }}"></span>
                </div>
                <div class="up-id">
                    <p class="up-hello">أهلاً بك</p>
                    <h2 class="user-name" title="{{ $user->name }}">{{ \Illuminate\Support\Str::words($user->name, 3, '') }}</h2>
                    <p class="user-role up-tone-{{ $statusTone }}"><i class="fa-solid {{ $statusIcon }}" aria-hidden="true"></i> {{ $statusLabel }}</p>
                </div>
            </div>
            <dl class="up-meta">
                @if (strlen($nid) >= 4)
                    <div>
                        <dt><i class="fa-solid fa-id-card" aria-hidden="true"></i> الرقم القومي</dt>
                        <dd dir="ltr" aria-label="ينتهي بـ {{ substr($nid, -4) }}">•••• {{ substr($nid, -4) }}</dd>
                    </div>
                @endif
                @if ($user->created_at)
                    <div>
                        <dt><i class="fa-regular fa-calendar" aria-hidden="true"></i> عضو منذ</dt>
                        <dd>{{ $user->created_at->format('Y/m') }}</dd>
                    </div>
                @endif
            </dl>
            @if (Route::has('two-factor.settings'))
                <a href="{{ route('two-factor.settings') }}" class="up-security {{ $secured ? 'is-on' : '' }}">
                    <i class="fa-solid {{ $secured ? 'fa-shield-halved' : 'fa-shield' }}" aria-hidden="true"></i>
                    <span>{{ $secured ? 'المصادقة الثنائية مفعّلة' : 'فعّل المصادقة الثنائية لحماية حسابك' }}</span>
                    <i class="fa-solid fa-chevron-left up-security__go" aria-hidden="true"></i>
                </a>
            @endif
        </section>

        <nav class="sidebar-nav-custom" aria-label="قائمة بوابة المواطن">
            <div class="nav-group">
                <label>الرئيسية</label>
                <a href="{{ route('citizen.dashboard') }}" class="nav-item {{ $activeNav === 'overview' ? 'active' : '' }}" @if ($activeNav === 'overview') aria-current="page" @endif>
                    <i class="fas fa-th-large"></i> <span>نظرة عامة</span>
                </a>
            </div>

            <div class="nav-group">
                <label>طلباتي</label>
                <a href="{{ route('citizen.dashboard.services') }}" class="nav-item {{ $activeNav === 'services' ? 'active' : '' }}" @if ($activeNav === 'services') aria-current="page" @endif>
                    <i class="fas fa-briefcase"></i> <span>خدمات عامة</span>
                </a>
                <a href="{{ route('citizen.dashboard.gis') }}" class="nav-item {{ $activeNav === 'gis' ? 'active' : '' }}" @if ($activeNav === 'gis') aria-current="page" @endif>
                    <i class="fas fa-map-marked-alt"></i> <span>خدمات مكانية</span>
                </a>
                <a href="{{ route('citizen.dashboard.estidama') }}" class="nav-item {{ $activeNav === 'estidama' ? 'active' : '' }}" @if ($activeNav === 'estidama') aria-current="page" @endif>
                    <i class="fas fa-graduation-cap"></i> <span>استدامة</span>
                </a>
                @if (Route::has('site-validity.requests.index'))
                    <a href="{{ route('site-validity.requests.index') }}" class="nav-item {{ $activeNav === 'site-validity' ? 'active' : '' }}" @if ($activeNav === 'site-validity') aria-current="page" @endif>
                        <i class="fas fa-map-location-dot"></i> <span>صلاحية الموقع</span>
                    </a>
                @endif
            </div>

            <div class="nav-group">
                <label>التواصل والمراقبة</label>
                <a href="{{ route('citizen.dashboard.monitoring') }}" class="nav-item {{ $activeNav === 'monitoring' ? 'active' : '' }}" @if ($activeNav === 'monitoring') aria-current="page" @endif>
                    <i class="fas fa-comments"></i> <span>الشكاوي والمقترحات</span>
                </a>
                <a href="{{ route('citizen.dashboard.fulfillment') }}" class="nav-item {{ $activeNav === 'fulfillment' ? 'active' : '' }}" @if ($activeNav === 'fulfillment') aria-current="page" @endif>
                    <i class="fas fa-tasks"></i> <span>الاستيفاء والمتابعة</span>
                    @if (isset($fulfillmentCount) && $fulfillmentCount > 0)
                        <span class="badge bg-warning text-dark me-auto" style="font-size:0.7rem;padding:2px 8px">{{ $fulfillmentCount }}</span>
                    @endif
                </a>
            </div>

            <div class="nav-group">
                <label>خدمات التقديم</label>
                <a href="{{ route('services.index') }}" class="nav-item">
                    <i class="fas fa-concierge-bell"></i> <span>دليل الخدمات</span>
                </a>
                <a href="{{ route('complaints.create') }}" class="nav-item">
                    <i class="fas fa-exclamation-triangle"></i> <span>تقديم شكوى</span>
                </a>
                <a href="{{ route('suggestions.create') }}" class="nav-item">
                    <i class="fas fa-lightbulb"></i> <span>إضافة مقترح</span>
                </a>
                <a href="{{ route('emergency.create') }}" class="nav-item">
                    <i class="fas fa-shield-alt"></i> <span>بلاغ طوارئ</span>
                </a>
                <a href="{{ route('surveys.service.create') }}" class="nav-item">
                    <i class="fas fa-star"></i> <span>تقييم الأداء</span>
                </a>
            </div>

            <div class="nav-group">
                <label>أدوات الاستكشاف</label>
                <a href="{{ route('directory.index') }}" class="nav-item">
                    <i class="fas fa-map-signs"></i> <span>دليل المناطق</span>
                </a>
                <a href="{{ route('governorate.map') }}" class="nav-item">
                    <i class="fas fa-globe-asia"></i> <span>الخريطة التفاعلية</span>
                </a>
                <a href="{{ route('estidama.index') }}" class="nav-item">
                    <i class="fas fa-leaf"></i> <span>منصة استدامة</span>
                </a>
            </div>

            <div class="nav-group">
                <label>الحساب</label>
                <a href="{{ route('two-factor.settings') }}" class="nav-item">
                    <i class="fas fa-shield-halved"></i> <span>المصادقة الثنائية</span>
                </a>
                <a href="{{ route('logout') }}" class="nav-item is-danger" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> <span>تسجيل خروج</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </nav>
    </div>
</aside>