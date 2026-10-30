@props([
    // Colour of the area under the wave (CSS colour / var), or "none" for a line-only wave.
    'fill' => 'var(--kfs-ivory)',
    // bottom | top  (top flips the wave so it can sit above a dark footer/section)
    'position' => 'bottom',
    // Slow water drift. Stops automatically with prefers-reduced-motion / "إيقاف الحركة".
    'animated' => true,
    'gold' => false,
    // Thin second wave line behind the main one.
    'back' => true,
])
{{-- KFS Wave Line: two periodic 1440px periods side by side so a -50% translate loops seamlessly. --}}
<div {{ $attributes->class(['kfs-wavebar', 'kfs-wavebar--' . $position, 'is-animated' => $animated, 'is-gold' => $gold]) }} aria-hidden="true">
    @if ($back)
    <svg class="kfs-wavebar__svg kfs-wavebar__svg--back" viewBox="0 0 2880 80" preserveAspectRatio="none" focusable="false">
        <path class="kfs-wavebar__line2" d="M0 34 C240 52 480 52 720 34 S1200 16 1440 34 S1920 52 2160 34 S2640 16 2880 34" />
    </svg>
    @endif
    <svg class="kfs-wavebar__svg" viewBox="0 0 2880 80" preserveAspectRatio="none" focusable="false">
        @if ($fill !== 'none')
            <path class="kfs-wavebar__fill" style="fill: {{ $fill }}" d="M0 40 C240 10 480 10 720 40 S1200 70 1440 40 S1920 10 2160 40 S2640 70 2880 40 V80 H0Z" />
        @endif
        <path class="kfs-wavebar__line" d="M0 40 C240 10 480 10 720 40 S1200 70 1440 40 S1920 10 2160 40 S2640 70 2880 40" />
    </svg>
</div>
