/*!
 * KFS Accessibility Toolkit — أدوات الإتاحة لبوابة محافظة كفر الشيخ
 * - Accessible floating button + dialog panel (keyboard & screen-reader operable)
 * - Font size (rem scaling), high contrast, dyslexia spacing, grayscale,
 *   highlight links, big cursor, stop animations, reading guide, dark mode (via window.KFSTheme),
 *   text-to-speech (ar-EG), sign-language video, reset.
 * - Settings persisted in localStorage (guarded with try/catch).
 * Public API: window.KFSA11y
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'kfs-a11y-v1';
  var root = document.documentElement;
  var FONT_STEPS = [0.9, 1, 1.1, 1.2, 1.35, 1.5, 1.65];
  var TOGGLES = {
    contrast: { cls: 'kfs-a11y-contrast', label: 'تباين عالٍ', icon: 'fa-circle-half-stroke' },
    dyslexia: { cls: 'kfs-a11y-dyslexia', label: 'تباعد مريح للقراءة', icon: 'fa-text-width' },
    grayscale: { cls: 'kfs-a11y-grayscale', label: 'تدرج رمادي', icon: 'fa-droplet-slash' },
    links: { cls: 'kfs-a11y-links', label: 'إبراز الروابط', icon: 'fa-link' },
    cursor: { cls: 'kfs-a11y-cursor', label: 'مؤشر كبير', icon: 'fa-arrow-pointer' },
    noanim: { cls: 'kfs-a11y-noanim', label: 'إيقاف الحركة', icon: 'fa-circle-pause' },
    guide: { cls: 'kfs-a11y-guide', label: 'دليل القراءة', icon: 'fa-ruler-horizontal' },
  };

  var state = load();

  /* ------------------------------------------------------------------ storage */
  function load() {
    var s = { font: 1 };
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      if (raw) {
        var parsed = JSON.parse(raw);
        if (parsed && typeof parsed === 'object') s = Object.assign(s, parsed);
      }
    } catch (e) { /* private mode / blocked storage */ }
    if (FONT_STEPS.indexOf(s.font) === -1) s.font = 1;
    return s;
  }

  function save() {
    try { window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) { /* ignore */ }
  }

  /* ------------------------------------------------------------------ live region */
  function announce(msg) {
    var live = document.getElementById('kfsLiveRegion');
    if (!live) return;
    live.textContent = '';
    window.setTimeout(function () { live.textContent = msg; }, 60);
  }

  /* ------------------------------------------------------------------ apply */
  function apply() {
    Object.keys(TOGGLES).forEach(function (k) {
      root.classList.toggle(TOGGLES[k].cls, !!state[k]);
    });
    root.style.fontSize = state.font === 1 ? '' : (state.font * 100) + '%';
    updateGuide();
    syncPanel();
  }

  function setToggle(name, value) {
    if (!TOGGLES[name]) return false;
    state[name] = value === undefined ? !state[name] : !!value;
    save();
    apply();
    announce(TOGGLES[name].label + (state[name] ? ': مفعّل' : ': متوقف'));
    return state[name];
  }

  function fontStep(dir) {
    var i = FONT_STEPS.indexOf(state.font);
    if (i === -1) i = 1;
    i = Math.max(0, Math.min(FONT_STEPS.length - 1, i + dir));
    state.font = FONT_STEPS[i];
    save();
    apply();
    announce('حجم الخط ' + Math.round(state.font * 100) + '٪');
  }

  function reset() {
    state = { font: 1 };
    save();
    stopReading();
    toggleSignLanguage(false);
    apply();
    announce('تمت إعادة ضبط إعدادات الإتاحة');
  }

  /* ------------------------------------------------------------------ reading guide */
  var guideEl = null;
  function onGuideMove(e) {
    if (!guideEl) return;
    var y = e.touches ? e.touches[0].clientY : e.clientY;
    guideEl.style.transform = 'translateY(' + (y - 18) + 'px)';
  }
  function updateGuide() {
    if (state.guide && !guideEl) {
      guideEl = document.createElement('div');
      guideEl.className = 'kfs-reading-guide';
      guideEl.setAttribute('aria-hidden', 'true');
      document.body.appendChild(guideEl);
      document.addEventListener('mousemove', onGuideMove, { passive: true });
      document.addEventListener('touchmove', onGuideMove, { passive: true });
    } else if (!state.guide && guideEl) {
      guideEl.remove();
      guideEl = null;
      document.removeEventListener('mousemove', onGuideMove);
      document.removeEventListener('touchmove', onGuideMove);
    }
  }

  /* ------------------------------------------------------------------ text to speech */
  var synth = window.speechSynthesis || null;
  var reading = false;

  function arabicVoice() {
    if (!synth) return null;
    var voices = synth.getVoices() || [];
    return voices.find(function (v) { return /^ar[-_]EG/i.test(v.lang); }) ||
      voices.find(function (v) { return /^ar/i.test(v.lang); }) || null;
  }

  function chunks(text) {
    var clean = String(text || '').replace(/\s+/g, ' ').trim();
    if (!clean) return [];
    var parts = clean.match(/[^.!?؟،\n]{1,220}[.!?؟،]?/g) || [clean];
    return parts.map(function (p) { return p.trim(); }).filter(Boolean);
  }

  function speak(text) {
    if (!synth || typeof window.SpeechSynthesisUtterance === 'undefined') {
      announce('القراءة الصوتية غير مدعومة في هذا المتصفح');
      return false;
    }
    var list = chunks(text);
    if (!list.length) { announce('لا يوجد نص للقراءة'); return false; }
    synth.cancel();
    reading = true;
    root.classList.add('kfs-tts-active');
    var voice = arabicVoice();
    list.forEach(function (part, idx) {
      var u = new SpeechSynthesisUtterance(part);
      u.lang = voice ? voice.lang : 'ar-EG';
      if (voice) u.voice = voice;
      u.rate = 0.95;
      if (idx === list.length - 1) {
        u.onend = function () { reading = false; root.classList.remove('kfs-tts-active'); syncPanel(); };
      }
      synth.speak(u);
    });
    syncPanel();
    return true;
  }

  function readPage() {
    var main = document.getElementById('main-content') || document.querySelector('main') || document.body;
    var clone = main.cloneNode(true);
    clone.querySelectorAll('script,style,noscript,svg,[aria-hidden="true"],.visually-hidden,.sr-only,iframe,video')
      .forEach(function (n) { n.remove(); });
    var title = document.querySelector('h1');
    var text = (title && !main.contains(title) ? title.innerText + '. ' : '') + (clone.innerText || clone.textContent);
    if (speak(text)) announce('جارٍ قراءة الصفحة');
  }

  function readSelection() {
    var sel = window.getSelection ? String(window.getSelection()) : '';
    if (!sel.trim()) { announce('حدّد نصاً أولاً ثم اختر قراءة النص المحدد'); return; }
    if (speak(sel)) announce('جارٍ قراءة النص المحدد');
  }

  function stopReading() {
    if (synth) synth.cancel();
    reading = false;
    root.classList.remove('kfs-tts-active');
    syncPanel();
  }

  if (synth && typeof synth.onvoiceschanged !== 'undefined') {
    synth.onvoiceschanged = function () { /* warm-up voice list */ };
  }

  /* ------------------------------------------------------------------ sign language video */
  function toggleSignLanguage(force) {
    var player = document.getElementById('signLangPlayer');
    if (!player) return;
    var show = force === undefined ? player.hidden : !!force;
    player.hidden = !show;
    var vid = document.getElementById('signLangVideo');
    if (vid) {
      if (show) { vid.play().catch(function () {}); } else { vid.pause(); }
    }
    state.sign = show;
    save();
    syncPanel();
  }

  /* ------------------------------------------------------------------ panel UI */
  var fab, panel, lastFocus;

  function buildPanel() {
    panel = document.createElement('div');
    panel.id = 'kfsA11yPanel';
    panel.className = 'kfs-a11y-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-labelledby', 'kfsA11yTitle');
    panel.hidden = true;

    var toggles = Object.keys(TOGGLES).map(function (k) {
      return '<button type="button" class="kfs-a11y-opt" data-a11y-toggle="' + k + '" aria-pressed="false">' +
        '<i class="fa-solid ' + TOGGLES[k].icon + '" aria-hidden="true"></i><span>' + TOGGLES[k].label + '</span></button>';
    }).join('');

    panel.innerHTML =
      '<div class="kfs-a11y-head">' +
        '<h2 id="kfsA11yTitle"><i class="fa-solid fa-universal-access" aria-hidden="true"></i> أدوات الإتاحة</h2>' +
        '<button type="button" class="kfs-a11y-close" data-a11y-close aria-label="إغلاق أدوات الإتاحة"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
      '</div>' +
      '<div class="kfs-a11y-body">' +
        '<div class="kfs-a11y-group" role="group" aria-labelledby="kfsA11yFontLbl">' +
          '<span id="kfsA11yFontLbl" class="kfs-a11y-label">حجم الخط</span>' +
          '<div class="kfs-a11y-font">' +
            '<button type="button" data-a11y-font="-1" aria-label="تصغير الخط"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>' +
            '<output id="kfsA11yFontVal" aria-live="polite">100٪</output>' +
            '<button type="button" data-a11y-font="1" aria-label="تكبير الخط"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>' +
          '</div>' +
        '</div>' +
        '<div class="kfs-a11y-grid">' + toggles +
          '<button type="button" class="kfs-a11y-opt" data-a11y-theme aria-pressed="false"><i class="fa-solid fa-moon" aria-hidden="true"></i><span>الوضع الليلي</span></button>' +
          '<button type="button" class="kfs-a11y-opt" data-a11y-sign aria-pressed="false"><i class="fa-solid fa-hands-asl-interpreting" aria-hidden="true"></i><span>مترجم لغة الإشارة</span></button>' +
        '</div>' +
        '<div class="kfs-a11y-group" role="group" aria-labelledby="kfsA11yTtsLbl">' +
          '<span id="kfsA11yTtsLbl" class="kfs-a11y-label">القراءة الصوتية</span>' +
          '<div class="kfs-a11y-tts">' +
            '<button type="button" data-a11y-read="page"><i class="fa-solid fa-volume-high" aria-hidden="true"></i> اقرأ الصفحة</button>' +
            '<button type="button" data-a11y-read="selection"><i class="fa-solid fa-highlighter" aria-hidden="true"></i> اقرأ المحدد</button>' +
            '<button type="button" data-a11y-read="stop" disabled><i class="fa-solid fa-stop" aria-hidden="true"></i> إيقاف</button>' +
          '</div>' +
        '</div>' +
        '<button type="button" class="kfs-a11y-reset" data-a11y-reset><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> إعادة ضبط الإعدادات</button>' +
        '<p class="kfs-a11y-hint">اختصارات: <kbd>Alt</kbd>+<kbd>1</kbd> لفتح الأدوات، <kbd>Alt</kbd>+<kbd>V</kbd> للأوامر الصوتية، <kbd>Esc</kbd> للإغلاق.</p>' +
      '</div>';
    document.body.appendChild(panel);

    panel.addEventListener('click', function (e) {
      var t = e.target.closest('button');
      if (!t) return;
      if (t.hasAttribute('data-a11y-close')) return close();
      if (t.hasAttribute('data-a11y-toggle')) return setToggle(t.getAttribute('data-a11y-toggle'));
      if (t.hasAttribute('data-a11y-font')) return fontStep(parseInt(t.getAttribute('data-a11y-font'), 10));
      if (t.hasAttribute('data-a11y-sign')) return toggleSignLanguage();
      if (t.hasAttribute('data-a11y-theme')) { if (window.KFSTheme) window.KFSTheme.toggle(); return syncPanel(); }
      if (t.hasAttribute('data-a11y-reset')) return reset();
      var r = t.getAttribute('data-a11y-read');
      if (r === 'page') return readPage();
      if (r === 'selection') return readSelection();
      if (r === 'stop') return stopReading();
    });
    // Keep the current text selection when pressing "read selection".
    panel.addEventListener('mousedown', function (e) {
      if (e.target.closest('[data-a11y-read="selection"]')) e.preventDefault();
    });
    panel.addEventListener('keydown', trapFocus);
  }

  function syncPanel() {
    if (!panel) return;
    panel.querySelectorAll('[data-a11y-toggle]').forEach(function (b) {
      b.setAttribute('aria-pressed', state[b.getAttribute('data-a11y-toggle')] ? 'true' : 'false');
    });
    var sign = panel.querySelector('[data-a11y-sign]');
    var player = document.getElementById('signLangPlayer');
    if (sign) sign.setAttribute('aria-pressed', player && !player.hidden ? 'true' : 'false');
    var themeBtn = panel.querySelector('[data-a11y-theme]');
    if (themeBtn) {
      var dark = !!(window.KFSTheme && window.KFSTheme.get() === 'dark');
      themeBtn.setAttribute('aria-pressed', dark ? 'true' : 'false');
      themeBtn.title = dark ? 'تفعيل الوضع النهاري' : 'تفعيل الوضع الليلي';
    }
    var out = panel.querySelector('#kfsA11yFontVal');
    if (out) out.textContent = Math.round(state.font * 100) + '٪';
    var stop = panel.querySelector('[data-a11y-read="stop"]');
    if (stop) stop.disabled = !reading;
  }

  function focusables() {
    return Array.prototype.slice.call(panel.querySelectorAll('button:not([disabled]),[href],input,select,textarea,[tabindex]:not([tabindex="-1"])'))
      .filter(function (el) { return el.offsetParent !== null; });
  }

  function trapFocus(e) {
    if (e.key === 'Escape') { e.preventDefault(); close(); return; }
    if (e.key !== 'Tab') return;
    var f = focusables();
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }

  function open() {
    if (!panel) buildPanel();
    lastFocus = document.activeElement;
    panel.hidden = false;
    root.classList.add('kfs-a11y-open');
    if (fab) fab.setAttribute('aria-expanded', 'true');
    syncPanel();
    var first = panel.querySelector('[data-a11y-font="-1"]');
    if (first) first.focus();
  }

  function close() {
    if (!panel || panel.hidden) return;
    panel.hidden = true;
    root.classList.remove('kfs-a11y-open');
    if (fab) fab.setAttribute('aria-expanded', 'false');
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  /* ------------------------------------------------------------------ enhancements */
  function enhanceForms() {
    // Associate validation messages with their fields for screen readers.
    var n = 0;
    document.querySelectorAll('.is-invalid, [aria-invalid="true"]').forEach(function (field) {
      field.setAttribute('aria-invalid', 'true');
      var container = field.closest('.mb-3, .form-group, .field, .col, [class*="col-"]') || field.parentElement;
      if (!container) return;
      var msg = container.querySelector('.invalid-feedback, .text-danger, .error, .field-error');
      if (!msg) return;
      if (!msg.id) msg.id = 'kfs-err-' + (++n);
      var ids = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
      if (ids.indexOf(msg.id) === -1) ids.push(msg.id);
      field.setAttribute('aria-describedby', ids.join(' '));
    });
    // Required fields.
    document.querySelectorAll('input[required],select[required],textarea[required]').forEach(function (el) {
      el.setAttribute('aria-required', 'true');
    });
  }

  function enhanceIconButtons() {
    document.querySelectorAll('a, button').forEach(function (el) {
      if (el.hasAttribute('aria-label') || el.hasAttribute('aria-labelledby')) return;
      if ((el.textContent || '').trim()) return;
      var t = el.getAttribute('title');
      if (t) el.setAttribute('aria-label', t);
    });
    document.querySelectorAll('i.fa, i.fas, i.far, i.fab, i.fa-solid, i.fa-regular, i.fa-brands').forEach(function (i) {
      if (!i.hasAttribute('aria-hidden')) i.setAttribute('aria-hidden', 'true');
    });
    document.querySelectorAll('img:not([alt])').forEach(function (img) { img.setAttribute('alt', ''); });
  }

  /* ------------------------------------------------------------------ init */
  function init() {
    fab = document.getElementById('kfsA11yFab');
    if (fab) {
      fab.setAttribute('aria-expanded', 'false');
      fab.setAttribute('aria-controls', 'kfsA11yPanel');
      fab.addEventListener('click', function () { (panel && !panel.hidden) ? close() : open(); });
    }
    document.querySelectorAll('[data-a11y-open]').forEach(function (b) {
      b.addEventListener('click', function (e) { e.preventDefault(); open(); });
    });
    var signClose = document.getElementById('signLangClose');
    if (signClose) signClose.addEventListener('click', function () { toggleSignLanguage(false); });
    var signToggle = document.getElementById('signLangToggle');
    if (signToggle) signToggle.addEventListener('click', function () {
      var vid = document.getElementById('signLangVideo');
      if (vid) { if (vid.paused) { vid.play().catch(function () {}); } else { vid.pause(); } }
    });
    if (state.sign) toggleSignLanguage(true);

    document.addEventListener('keydown', function (e) {
      if (e.altKey && !e.ctrlKey && !e.metaKey && (e.key === '1' || e.code === 'Digit1')) {
        e.preventDefault();
        (panel && !panel.hidden) ? close() : open();
      } else if (e.key === 'Escape' && panel && !panel.hidden) {
        close();
      }
    });
    document.addEventListener('click', function (e) {
      if (!panel || panel.hidden) return;
      if (!panel.contains(e.target) && (!fab || !fab.contains(e.target))) close();
    });

    // Landmark: views that don't render their own <main> get one on the wrapper.
    var mc = document.getElementById('main-content');
    if (mc && !mc.querySelector('main') && !document.querySelector('main')) mc.setAttribute('role', 'main');

    document.addEventListener('kfs:themechange', syncPanel);

    enhanceForms();
    enhanceIconButtons();
    apply();
  }

  // Apply visual state as early as possible (before DOMContentLoaded).
  Object.keys(TOGGLES).forEach(function (k) { root.classList.toggle(TOGGLES[k].cls, !!state[k]); });
  if (state.font !== 1) root.style.fontSize = (state.font * 100) + '%';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.KFSA11y = {
    open: open,
    close: close,
    toggle: setToggle,
    fontStep: fontStep,
    readPage: readPage,
    readSelection: readSelection,
    stopReading: stopReading,
    speak: speak,
    reset: reset,
    announce: announce,
    state: function () { return Object.assign({}, state); },
  };
})();
