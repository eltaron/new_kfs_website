<x-filament-widgets::widget class="kgw">
    {{-- ===== Welcome strip ===== --}}
    <section class="kgw-hero">
        <div class="kgw-hero__text">
            <span class="kgw-hero__date">
                <x-filament::icon icon="heroicon-o-calendar-days" class="kgw-i" /> {{ $today }}
            </span>
            <h2 class="kgw-hero__title">{{ $greeting }}، {{ $name }}</h2>
            <p class="kgw-hero__sub">منظومة حوكمة قرارات الإزالة — تابع سير القرارات من الإنشاء حتى التنفيذ.</p>
            <div class="kgw-hero__chips">
                @if ($role)
                    <span class="kgw-chip"><x-filament::icon icon="heroicon-o-identification" class="kgw-i" /> {{ $role }}</span>
                @endif
                <span class="kgw-chip kgw-chip--gold">
                    <x-filament::icon icon="heroicon-o-map-pin" class="kgw-i" /> {{ $center ?: 'كل المراكز' }}
                </span>
            </div>
        </div>
        @if ($links->isNotEmpty())
            <nav class="kgw-hero__links" aria-label="روابط سريعة">
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" class="kgw-hero__link">
                        <x-filament::icon :icon="$link['icon']" class="kgw-i" />
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
    </section>

    {{-- ===== KPIs ===== --}}
    <section class="kgw-kpis" aria-label="مؤشرات القرارات">
        @foreach ($kpis as $kpi)
            @php $tag = !empty($kpi['url']) ? 'a' : 'div'; @endphp
            <{{ $tag }} @if ($tag === 'a') href="{{ $kpi['url'] }}" @endif class="kgw-kpi kgw-kpi--{{ $kpi['tone'] }}">
                <span class="kgw-kpi__icon"><x-filament::icon :icon="$kpi['icon']" class="kgw-i" /></span>
                <span class="kgw-kpi__body">
                    <strong class="kgw-kpi__value">{{ number_format($kpi['value']) }}</strong>
                    <span class="kgw-kpi__label">{{ $kpi['label'] }}</span>
                </span>
            </{{ $tag }}>
        @endforeach
    </section>

    {{-- ===== Workflow pipeline ===== --}}
    <section class="kgw-flow" aria-labelledby="kgwFlowTitle">
        <header class="kgw-flow__head">
            <div>
                <h3 class="kgw-flow__title" id="kgwFlowTitle">مسار القرارات</h3>
                <p class="kgw-flow__sub">عدد القرارات في كل مرحلة — اضغط على المرحلة لعرض قراراتها.</p>
            </div>
            @if ($busiest)
                <span class="kgw-flow__alert">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="kgw-i" />
                    أكثر مرحلة ازدحاماً: <strong>{{ $busiest['label'] }}</strong> ({{ $busiest['count'] }})
                </span>
            @endif
        </header>

        <ol class="kgw-steps">
            @foreach ($stages as $stage)
                @php
                    $tag = $stage['url'] ? 'a' : 'div';
                    $isDone = $stage['key'] === \Modules\RemovalGovernance\Models\RemovalOrder::STAGE_COMPLETED;
                    $fill = $isDone ? 100 : (int) round($stage['count'] / $maxCount * 100);
                    $isBusiest = $busiest && $busiest['key'] === $stage['key'];
                @endphp
                <li class="kgw-step {{ $stage['count'] ? 'has-items' : 'is-empty' }} {{ $isDone ? 'is-done' : '' }} {{ $isBusiest ? 'is-busiest' : '' }}">
                    <{{ $tag }} @if ($tag === 'a') href="{{ $stage['url'] }}" @endif class="kgw-step__card">
                        <span class="kgw-step__num">{{ $loop->iteration }}</span>
                        <span class="kgw-step__icon"><x-filament::icon :icon="$stage['icon']" class="kgw-i" /></span>
                        <span class="kgw-step__label">{{ $stage['label'] }}</span>
                        <strong class="kgw-step__count">{{ number_format($stage['count']) }}</strong>
                        <span class="kgw-step__bar"><span style="width: {{ $fill }}%"></span></span>
                    </{{ $tag }}>
                </li>
            @endforeach
        </ol>
    </section>
</x-filament-widgets::widget>
