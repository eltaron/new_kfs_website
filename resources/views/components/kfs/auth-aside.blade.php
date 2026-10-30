@props([
    'title' => 'بوابتك الرقمية لخدمات المحافظة',
    'text' => 'حساب واحد للوصول إلى الخدمات الإلكترونية ومتابعة طلباتك وشكاواك ومدفوعاتك.',
    'perks' => [
        ['fa-solid fa-id-card', 'دخول آمن بالرقم القومي وكلمة المرور'],
        ['fa-solid fa-list-check', 'تابع طلباتك وشكاواك خطوة بخطوة'],
        ['fa-solid fa-credit-card', 'سداد إلكتروني آمن عبر e-finance'],
        ['fa-solid fa-shield-halved', 'حماية إضافية بالتحقق الثنائي'],
    ],
])
{{-- Identity side panel for auth pages (dark surface + pattern + wave + lotus). Decorative content only. --}}
<aside {{ $attributes->class(['kfs-auth__aside']) }} aria-label="عن البوابة">
    <span class="kfs-float kfs-float--ring" aria-hidden="true"></span>
    <span class="kfs-float kfs-float--dot" aria-hidden="true"></span>
    <div class="kfs-auth__brand">
        <x-kfs.lotus :size="46" />
        <span><small>جمهورية مصر العربية</small><strong>محافظة كفر الشيخ</strong></span>
    </div>
    <h2>{{ $title }}</h2>
    <x-kfs.heritage :width="140" light />
    <p>{{ $text }}</p>
    @if (! empty($perks))
        <ul class="kfs-auth__perks" data-stagger>
            @foreach ($perks as [$icon, $label])
                <li><i class="{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span></li>
            @endforeach
        </ul>
    @endif
    @if ($slot->isNotEmpty())
        <div class="kfs-auth__aside-foot">{{ $slot }}</div>
    @endif
</aside>
