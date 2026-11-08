{{-- v2 portal header: brand + portal name on the dark band, quick actions at the end --}}
<div class="top-nav-dash">
    <a href="{{ route('citizen.dashboard') }}" class="top-nav-dash__brand">
        <img src="{{ !empty($settings['site_logo_header']) ? Storage::url($settings['site_logo_header']) : asset('logo.png') }}" alt="شعار المحافظة" width="46" height="46">
        <span>
            <span class="top-nav-dash__eyebrow">بوابة المواطن الإلكترونية</span>
            <span class="top-nav-dash__title">{{ $settings['site_name'] ?? 'محافظة كفر الشيخ' }}</span>
        </span>
    </a>
    <div class="top-nav-dash__actions">
        @if (config('app.show_hidden') && auth()->user()->isEmployee() && Route::has('employee.erp.index'))
            <a href="{{ route('employee.erp.index') }}" class="btn-dash-ghost">
                <i class="fa-solid fa-laptop-code" aria-hidden="true"></i> بوابة الموظفين
            </a>
        @endif
        <a href="{{ route('home') }}" class="btn-dash-ghost d-none d-md-inline-flex">
            <i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية
        </a>
        <div class="dropdown">
            <button type="button" class="btn-dash-ghost dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                <span class="d-none d-md-inline">{{ $user->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted">{{ $user->email }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('citizen.dashboard') }}">
                        <i class="fa-solid fa-table-columns ms-2 text-primary" aria-hidden="true"></i>لوحة التحكم
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fa-solid fa-power-off ms-2" aria-hidden="true"></i>تسجيل خروج
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
