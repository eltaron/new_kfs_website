<x-filament-panels::page>
    @once
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/ar.global.min.js"></script>
    @endonce

    <div class="svc-top">
        <div class="svc-stats">
            <div class="svc-stat" style="--c:#00788A"><b>{{ $stats['today'] }}</b><span>مواعيد اليوم</span></div>
            <div class="svc-stat" style="--c:#5B5BD6"><b>{{ $stats['week'] }}</b><span>هذا الأسبوع</span></div>
            <div class="svc-stat" style="--c:#A63A32"><b>{{ $stats['overdue'] }}</b><span>معاينات متأخرة</span></div>
        </div>
        @if ($canSeeAll)
            <div class="svc-scope" role="group" aria-label="نطاق المواعيد">
                <button type="button" wire:click="setScope('mine')" class="{{ $scope === 'mine' ? 'is-on' : '' }}" aria-pressed="{{ $scope === 'mine' ? 'true' : 'false' }}">مواعيدي</button>
                <button type="button" wire:click="setScope('all')" class="{{ $scope === 'all' ? 'is-on' : '' }}" aria-pressed="{{ $scope === 'all' ? 'true' : 'false' }}">كل المواعيد</button>
            </div>
        @endif
    </div>

    <div class="svc-card">
        <div wire:ignore x-data="svAppointmentsCalendar()" class="svc-cal-wrap">
            <div x-ref="cal"></div>
        </div>
        <div class="svc-legend">
            @foreach ($statusHex as $key => $hex)
                <span><i style="background: {{ $hex }}"></i>{{ $statusShort[$key] }}</span>
            @endforeach
            <span><i class="is-overdue"></i>معاينة متأخرة</span>
            <span class="svc-hint">اسحب الموعد لتغييره (لمن يملك صلاحية تحديد المواعيد) — اضغط عليه لفتح الطلب.</span>
        </div>
    </div>

    <style>
        .svc-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
        .svc-stats { display: flex; flex-wrap: wrap; gap: .5rem; }
        .svc-stat { min-width: 8.5rem; padding: .6rem .9rem; border-radius: .75rem; border: 1px solid color-mix(in srgb, var(--c) 30%, transparent);
            border-inline-start: 4px solid var(--c); background: color-mix(in srgb, var(--c) 8%, #fff); color: var(--c); }
        .svc-stat b { display: block; font-size: 1.5rem; font-weight: 800; line-height: 1.1; }
        .svc-stat span { font-size: .78rem; font-weight: 700; }
        .dark .svc-stat { background: color-mix(in srgb, var(--c) 18%, #18181b); color: color-mix(in srgb, var(--c) 45%, #fff); }
        .svc-scope { display: inline-flex; padding: .25rem; border-radius: 999px; background: rgba(0,0,0,.06); gap: .25rem; }
        .dark .svc-scope { background: rgba(255,255,255,.08); }
        .svc-scope button { padding: .4rem 1rem; border-radius: 999px; font-weight: 700; font-size: .85rem; }
        .svc-scope button.is-on { background: #00788A; color: #fff; }
        .svc-card { background: #fff; border-radius: .75rem; padding: 1rem; outline: 1px solid rgba(0,0,0,.06); box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .dark .svc-card { background: rgb(24 24 27); outline-color: rgba(255,255,255,.08); }
        .svc-legend { display: flex; flex-wrap: wrap; gap: .4rem 1rem; margin-top: .75rem; font-size: .78rem; color: rgb(113 113 122); align-items: center; }
        .svc-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-inline-end: .35rem; vertical-align: middle; }
        .svc-legend i.is-overdue { background: #fff; border: 2px solid #A63A32; }
        .svc-hint { margin-inline-start: auto; }

        /* FullCalendar بهوية اللوحة */
        .svc-cal-wrap { --fc-border-color: rgba(0,0,0,.08); --fc-today-bg-color: rgba(216,176,62,.12); --fc-page-bg-color: transparent;
            --fc-button-bg-color: #26302D; --fc-button-border-color: #26302D; --fc-button-hover-bg-color: #1D2422; --fc-button-hover-border-color: #1D2422;
            --fc-button-active-bg-color: #00788A; --fc-button-active-border-color: #00788A; --fc-event-text-color: #fff; font-size: .85rem; }
        .dark .svc-cal-wrap { --fc-border-color: rgba(255,255,255,.1); --fc-neutral-bg-color: rgba(255,255,255,.04); --fc-list-event-hover-bg-color: rgba(255,255,255,.06); color: #e4e4e7; }
        .svc-cal-wrap .fc .fc-toolbar-title { font-size: 1.15rem; font-weight: 800; }
        .svc-cal-wrap .fc .fc-button { font-weight: 700; font-size: .8rem; padding: .35rem .7rem; border-radius: .5rem; }
        .svc-cal-wrap .fc-event { border-radius: .4rem; padding: 1px 3px; cursor: pointer; }
        .svc-cal-wrap .fc-daygrid-event { white-space: normal; }
        .svc-cal-wrap .fc-daygrid-block-event .fc-event-main-frame { flex-direction: column; align-items: flex-start; }
        .svc-cal-wrap .fc-daygrid-block-event .fc-event-time { font-weight: 800; opacity: .9; margin: 0; overflow: visible; }
        .svc-cal-wrap .fc-daygrid-block-event .fc-event-title { font-weight: 600; line-height: 1.45; }
        .svc-cal-wrap .fc-event.sv-ev-overdue { border-width: 2px; box-shadow: 0 0 0 2px rgba(166,58,50,.35); }
        .svc-cal-wrap .fc-event.sv-ev-overdue .fc-event-title::before { content: "⚠ "; }
        .svc-cal-wrap .fc-list-event-title a { color: inherit; }
        .svc-tip { position: fixed; z-index: 70; max-width: 280px; padding: .6rem .75rem; border-radius: .6rem; background: #1D2422; color: #fff; font-size: .78rem;
            line-height: 1.6; box-shadow: 0 10px 28px rgba(0,0,0,.3); pointer-events: none; }
        .svc-tip b { color: #D8B03E; }
    </style>

    <script>
        function svAppointmentsCalendar() {
            return {
                cal: null, tip: null,
                init() {
                    const boot = () => window.FullCalendar ? this.build() : setTimeout(boot, 80);
                    boot();
                    this.$wire.$on('sv-calendar-refetch', () => this.cal && this.cal.refetchEvents());
                },
                build() {
                    if (this.cal) return;
                    const wire = this.$wire;
                    this.cal = new FullCalendar.Calendar(this.$refs.cal, {
                        locale: 'ar', direction: 'rtl', timeZone: 'local', firstDay: 6,
                        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
                        headerToolbar: { start: 'prev,next today', center: 'title', end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
                        buttonText: { today: 'اليوم', month: 'شهر', week: 'أسبوع', day: 'يوم', list: 'قائمة' },
                        height: 'auto', nowIndicator: true, dayMaxEvents: 4, eventDisplay: 'block',
                        slotMinTime: '07:00:00', slotMaxTime: '20:00:00', allDaySlot: false,
                        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
                        events: (info, ok, fail) => wire.events(info.startStr, info.endStr).then(ok).catch(fail),
                        eventStartEditable: true, eventDurationEditable: false,
                        eventAllow: (span, ev) => ev.extendedProps && ev.startEditable !== false,
                        eventDrop: info => {
                            if (!confirm('تعديل موعد ' + info.event.title + ' إلى ' + info.event.start.toLocaleString('ar-EG', { dateStyle: 'full', timeStyle: 'short' }) + '؟')) { info.revert(); return; }
                            wire.reschedule(Number(info.event.id), info.event.start.toISOString()).then(r => { if (!r || !r.ok) info.revert(); else this.cal.refetchEvents(); });
                        },
                        eventDidMount: info => {
                            const p = info.event.extendedProps;
                            info.el.setAttribute('aria-label', info.event.title + ' — ' + p.status);
                            info.el.addEventListener('mouseenter', e => this.showTip(e, info.event));
                            info.el.addEventListener('mouseleave', () => this.hideTip());
                        },
                    });
                    this.cal.render();
                },
                showTip(e, ev) {
                    const p = ev.extendedProps, esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                    this.hideTip();
                    this.tip = document.createElement('div'); this.tip.className = 'svc-tip'; this.tip.dir = 'rtl';
                    this.tip.innerHTML = `<b>${esc(ev.title)}</b><br>${esc(p.service)}<br>📍 ${esc(p.place)}<br>📞 <span dir="ltr">${esc(p.phone)}</span><br>`
                        + `الحالة: ${esc(p.status)}${p.overdue ? ' — <b>متأخرة</b>' : ''}<br>المعاينة: الجيومكاني ${p.geoDone ? '✓' : '—'} · المهندس ${p.engDone ? '✓' : '—'}`;
                    document.body.appendChild(this.tip);
                    const r = e.currentTarget.getBoundingClientRect();
                    this.tip.style.top = Math.min(window.innerHeight - this.tip.offsetHeight - 8, r.bottom + 6) + 'px';
                    this.tip.style.left = Math.max(8, Math.min(window.innerWidth - this.tip.offsetWidth - 8, r.left)) + 'px';
                },
                hideTip() { this.tip?.remove(); this.tip = null; },
            };
        }
    </script>
</x-filament-panels::page>
