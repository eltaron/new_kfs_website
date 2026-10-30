{{-- Estidama program card (index + programs listing). The whole card links to the program page,
     which shows the right action (apply / enroll / pay / continue). --}}
@php
    $status = [
        'open' => ['التسجيل متاح', 'es-badge--ok'],
        'closed' => ['التسجيل مغلق', 'es-badge--bad'],
    ][$program->status] ?? ['جاري التنفيذ', 'es-badge--info'];
@endphp
<article class="es-card es-program" data-es-status="{{ $program->status }}">
    <div class="es-program__media">
        <img src="{{ $program->imageUrl() }}" alt="" loading="lazy"
            onerror="this.onerror=null;this.src='{{ asset('images/bg/estidama.jpeg') }}'">
        <span class="es-badge {{ $status[1] }} es-program__status">{{ $status[0] }}</span>
        <span class="es-program__price">{{ $program->priceLabel() }}</span>
    </div>
    <div class="es-program__body">
        @if ($program->trainingCenter?->name)
            <span class="es-program__center"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> {{ $program->trainingCenter->name }}</span>
        @endif
        <h3 class="es-program__title"><a href="{{ route('estidama.program.show', $program) }}">{{ $program->title }}</a></h3>
        <p class="es-program__desc">{{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) $program->description))), 140) }}</p>
        <div class="es-program__foot">
            @if ($program->start_date)
                <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> يبدأ {{ $program->start_date->format('Y-m-d') }}</span>
            @else
                <span><i class="fa-solid fa-laptop" aria-hidden="true"></i> تعلّم إلكتروني</span>
            @endif
            <span class="es-program__go" aria-hidden="true">{{ $program->status === 'closed' ? 'التفاصيل' : 'التفاصيل والتسجيل' }} <i class="fa-solid fa-arrow-left"></i></span>
        </div>
    </div>
</article>
