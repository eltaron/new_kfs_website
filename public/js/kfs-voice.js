/*!
 * KFS Voice Commands — الأوامر الصوتية العربية لبوابة محافظة كفر الشيخ
 * Web Speech API (SpeechRecognition) — ar-EG with ar-SA fallback. No external services.
 * Route map is injected by the layout:
 *   <script type="application/json" id="kfsVoiceRoutes">{"home":"...", ...}</script>
 * Toggle: #kfsVoiceBtn or Alt+V.
 */
(function () {
  'use strict';

  var Recognition = window.SpeechRecognition || window.webkitSpeechRecognition || null;
  var routes = {};
  try {
    var el = document.getElementById('kfsVoiceRoutes');
    if (el) routes = JSON.parse(el.textContent || '{}') || {};
  } catch (e) { routes = {}; }

  /* ------------------------------------------------------------ normalization */
  function normalize(text) {
    return String(text || '')
      .replace(/[ً-ٰٟۖ-ۭ]/g, '') // diacritics
      .replace(/ـ/g, '')                            // tatweel
      .replace(/[أإآٱ]/g, 'ا')
      .replace(/ى/g, 'ي')
      .replace(/ة/g, 'ه')
      .replace(/ؤ/g, 'و')
      .replace(/ئ/g, 'ي')
      .replace(/[٠-٩]/g, function (d) { return String(d.charCodeAt(0) - 0x0660); })
      .replace(/[.,!?؟،؛:"'()\[\]{}«»\-_/\\]/g, ' ')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();
  }

  // Strip the Arabic definite article / prefixes for looser matching.
  function stem(word) {
    return word.replace(/^(و|ف)?(بال|لل|ال|بـ)/, '');
  }

  /* ------------------------------------------------------------ vocabulary */
  var TARGETS = [
    { key: 'home', label: 'الرئيسية', words: ['الرئيسيه', 'الصفحه الرئيسيه', 'الرئيسي', 'البدايه', 'الهوم', 'الصفحه الاولي'] },
    { key: 'services', label: 'الخدمات', words: ['الخدمات', 'خدمات', 'الخدمات الالكترونيه', 'الخدمه'] },
    { key: 'news', label: 'الأخبار', words: ['الاخبار', 'اخبار', 'الخبر', 'الاخبار والفعاليات'] },
    { key: 'complaints', label: 'الشكاوى', words: ['الشكاوي', 'شكاوي', 'الشكوي', 'شكوي', 'البلاغات', 'بلاغ', 'تقديم شكوي'] },
    { key: 'projects', label: 'المشروعات', words: ['المشروعات', 'المشاريع', 'مشروعات', 'مشاريع'] },
    { key: 'investments', label: 'الاستثمار', words: ['الاستثمار', 'استثمار', 'الفرص الاستثماريه'] },
    { key: 'tourism', label: 'السياحة', words: ['السياحه', 'سياحه', 'المعالم', 'المعالم السياحيه', 'الاماكن السياحيه'] },
    { key: 'estidama', label: 'استدامة', words: ['استدامه', 'الاستدامه', 'برنامج استدامه'] },
    { key: 'contact', label: 'تواصل معنا', words: ['تواصل معنا', 'التواصل', 'اتصل بنا', 'اتصال', 'الدعم الفني', 'تواصل'] },
    { key: 'map', label: 'الخريطة', words: ['الخريطه', 'خريطه', 'خريطه المحافظه', 'الخريطه التفاعليه'] },
    { key: 'store', label: 'المتجر', words: ['المتجر', 'متجر', 'السوق', 'المتجر الالكتروني'] },
    { key: 'dashboard', label: 'لوحة التحكم', words: ['لوحه التحكم', 'لوحه تحكم', 'حسابي', 'الداشبورد'] },
    { key: 'about', label: 'عن المحافظة', words: ['عن المحافظه', 'المحافظه', 'نبذه عن المحافظه'] },
    { key: 'directory', label: 'دليل الهاتف', words: ['دليل الهاتف', 'دليل التليفونات', 'ارقام الهواتف'] },
    { key: 'login', label: 'تسجيل الدخول', words: ['تسجيل الدخول', 'الدخول', 'دخول'] },
  ];

  var NAV_VERBS = '(?:افتح(?:لي)?|افتحي|روح(?:لي)?|اذهب|انتقل|وديني|ودني|خدني|اعرض(?:لي)?|اظهر|عايز|عاوز|اريد|صفحه)';

  var HELP = [
    ['افتح الرئيسية / الخدمات / الأخبار / الشكاوى / المشروعات / الاستثمار / السياحة / استدامة / تواصل معنا / الخريطة / المتجر / لوحة التحكم', 'الانتقال إلى الصفحة'],
    ['ابحث عن ...', 'البحث في البوابة'],
    ['انزل / اطلع / أول الصفحة / آخر الصفحة', 'التمرير داخل الصفحة'],
    ['رجوع', 'العودة للصفحة السابقة'],
    ['كبر الخط / صغر الخط', 'تغيير حجم الخط'],
    ['تباين عالي / وقف الحركة', 'أدوات الإتاحة'],
    ['اقرأ الصفحة / وقف القراءة', 'القراءة الصوتية'],
    ['الوضع الليلي / الوضع النهاري / تبديل الوضع', 'تغيير ألوان البوابة'],
    ['اضغط على ...', 'الضغط على رابط أو زر ظاهر'],
    ['ساعدني', 'عرض قائمة الأوامر'],
    ['توقف', 'إيقاف الاستماع'],
  ];

  /* ------------------------------------------------------------ feedback */
  function announce(msg) {
    if (window.KFSA11y && window.KFSA11y.announce) { window.KFSA11y.announce(msg); return; }
    var live = document.getElementById('kfsLiveRegion');
    if (live) { live.textContent = ''; setTimeout(function () { live.textContent = msg; }, 60); }
  }

  var statusEl, btn;
  function showStatus(text, kind) {
    if (!statusEl) return;
    statusEl.hidden = !text;
    statusEl.textContent = text || '';
    statusEl.setAttribute('data-kind', kind || 'info');
  }

  function feedback(msg, kind) {
    showStatus(msg, kind);
    announce(msg);
    clearTimeout(feedback.t);
    feedback.t = setTimeout(function () { if (!listening) showStatus(''); }, 4000);
  }

  /* ------------------------------------------------------------ actions */
  function go(key) {
    var url = routes[key];
    if (!url) { feedback('هذه الصفحة غير متاحة حالياً', 'error'); return true; }
    var t = TARGETS.filter(function (x) { return x.key === key; })[0];
    feedback('جارٍ فتح ' + (t ? t.label : 'الصفحة'), 'ok');
    setTimeout(function () { window.location.href = url; }, 350);
    return true;
  }

  function findTarget(phrase) {
    var p = normalize(phrase).replace(/^(?:ل|الي|علي|الى|على)\s+/, '').replace(/^(?:صفحه)\s+/, '');
    if (!p) return null;
    var best = null;
    TARGETS.forEach(function (t) {
      t.words.forEach(function (w) {
        var nw = normalize(w);
        if (p === nw || p === stem(nw) || stem(p) === stem(nw)) {
          if (!best || nw.length > best.len) best = { key: t.key, len: nw.length };
        }
      });
    });
    return best ? best.key : null;
  }

  function scrollByPage(dir) {
    var reduce = document.documentElement.classList.contains('kfs-a11y-noanim') ||
      (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    window.scrollBy({ top: dir * window.innerHeight * 0.8, behavior: reduce ? 'auto' : 'smooth' });
  }

  function scrollToEdge(bottom) {
    var reduce = document.documentElement.classList.contains('kfs-a11y-noanim');
    window.scrollTo({ top: bottom ? document.documentElement.scrollHeight : 0, behavior: reduce ? 'auto' : 'smooth' });
  }

  function isVisible(el) {
    if (!el || !el.getClientRects().length) return false;
    var st = window.getComputedStyle(el);
    return st.visibility !== 'hidden' && st.display !== 'none' && !el.closest('[hidden],[aria-hidden="true"]');
  }

  function similarity(a, b) {
    if (!a || !b) return 0;
    if (a === b) return 1;
    if (b.indexOf(a) !== -1) return 0.9 * (a.length / b.length) + 0.1;
    if (a.indexOf(b) !== -1) return 0.8 * (b.length / a.length);
    var ta = a.split(' ').map(stem), tb = b.split(' ').map(stem);
    var hit = ta.filter(function (w) { return w.length > 1 && tb.indexOf(w) !== -1; }).length;
    var tokenScore = hit / Math.max(ta.length, 1);
    // Levenshtein ratio on whole strings
    var m = a.length, n = b.length;
    if (m > 60 || n > 60) return tokenScore * 0.85;
    var d = [];
    for (var i = 0; i <= m; i++) { d[i] = [i]; }
    for (var j = 1; j <= n; j++) { d[0][j] = j; }
    for (i = 1; i <= m; i++) {
      for (j = 1; j <= n; j++) {
        d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
      }
    }
    var lev = 1 - d[m][n] / Math.max(m, n);
    return Math.max(tokenScore * 0.85, lev * 0.8);
  }

  function clickByText(phrase) {
    var q = normalize(phrase);
    if (!q) return false;
    var candidates = document.querySelectorAll('a[href], button, [role="button"], input[type="submit"], input[type="button"], summary');
    var best = null, bestScore = 0;
    Array.prototype.forEach.call(candidates, function (el) {
      if (el.closest('#kfsVoiceHelp') || !isVisible(el)) return;
      var label = normalize(el.getAttribute('aria-label') || el.innerText || el.value || el.getAttribute('title') || '');
      if (!label) return;
      var s = similarity(q, label);
      if (s > bestScore) { bestScore = s; best = el; }
    });
    if (best && bestScore >= 0.55) {
      feedback('تم الضغط على: ' + (best.getAttribute('aria-label') || best.innerText || best.value || '').trim().slice(0, 60), 'ok');
      best.focus({ preventScroll: false });
      setTimeout(function () { best.click(); }, 250);
      return true;
    }
    feedback('لم أجد رابطاً أو زراً باسم «' + phrase + '»', 'error');
    return true;
  }

  function search(text) {
    var q = String(text || '').trim();
    if (!q) { feedback('قل: ابحث عن ثم كلمة البحث', 'error'); return true; }
    if (!routes.search) { feedback('البحث غير متاح حالياً', 'error'); return true; }
    feedback('جارٍ البحث عن: ' + q, 'ok');
    var url = routes.search + (routes.search.indexOf('?') === -1 ? '?' : '&') + 'query=' + encodeURIComponent(q);
    setTimeout(function () { window.location.href = url; }, 350);
    return true;
  }

  function a11y(fn, arg, msg) {
    if (!window.KFSA11y) { feedback('أدوات الإتاحة غير متاحة', 'error'); return true; }
    window.KFSA11y[fn](arg);
    if (msg) feedback(msg, 'ok');
    return true;
  }

  function theme(mode) {
    if (!window.KFSTheme) { feedback('تبديل الوضع غير متاح', 'error'); return true; }
    var t = mode === 'toggle' ? window.KFSTheme.toggle() : window.KFSTheme.set(mode);
    feedback(t === 'dark' ? 'تم تفعيل الوضع الليلي' : 'تم تفعيل الوضع النهاري', 'ok');
    return true;
  }

  /* ------------------------------------------------------------ grammar */
  function execute(raw) {
    var original = String(raw || '').trim();
    var t = normalize(original);
    if (!t) return false;
    var m;

    if (/^(ساعدني|مساعده|الاوامر|ايه الاوامر|الاوامر المتاحه|مساعدة)$/.test(t)) { openHelp(); return true; }
    if (/^(توقف|اقف|وقف الاستماع|اسكت الميكروفون|خلاص|الغاء)$/.test(t)) { stop(); feedback('تم إيقاف الاستماع', 'ok'); return true; }

    // Search
    m = t.match(/^(?:ابحث|دور|دوري|بحث|فتش)(?:\s+(?:لي|لنا))?(?:\s+(?:عن|علي|على))?\s+(.+)$/);
    if (m) {
      // keep the user's original wording for the query (minus the command words)
      var words = original.split(/\s+/);
      var drop = t.split(' ').length - m[1].split(' ').length;
      return search(words.slice(drop).join(' ') || m[1]);
    }

    // Scrolling
    if (/^(انزل|نزل|انزل لتحت|لتحت|تحت|اسفل|مرر لاسفل)$/.test(t)) { scrollByPage(1); feedback('تم التمرير لأسفل', 'ok'); return true; }
    if (/^(اطلع|طلع|اطلع فوق|لفوق|فوق|اعلي|مرر لاعلي)$/.test(t)) { scrollByPage(-1); feedback('تم التمرير لأعلى', 'ok'); return true; }
    if (/^(اول الصفحه|بدايه الصفحه|راس الصفحه|اعلي الصفحه)$/.test(t)) { scrollToEdge(false); feedback('أول الصفحة', 'ok'); return true; }
    if (/^(اخر الصفحه|نهايه الصفحه|اسفل الصفحه|اخر الصفحه خالص)$/.test(t)) { scrollToEdge(true); feedback('آخر الصفحة', 'ok'); return true; }

    // History
    if (/^(رجوع|ارجع|الرجوع|ارجع للخلف|رجعني|الصفحه السابقه)$/.test(t)) { feedback('رجوع للصفحة السابقة', 'ok'); setTimeout(function () { history.back(); }, 250); return true; }

    // Accessibility
    if (/^(كبر|كبر الخط|تكبير الخط|كبر الكلام|خط اكبر)$/.test(t)) return a11y('fontStep', 1, 'تم تكبير الخط');
    if (/^(صغر|صغر الخط|تصغير الخط|خط اصغر)$/.test(t)) return a11y('fontStep', -1, 'تم تصغير الخط');
    if (/^(تباين عالي|التباين العالي|تباين|وضع التباين)$/.test(t)) return a11y('toggle', 'contrast');
    if (/^(وقف الحركه|ايقاف الحركه|اوقف الحركه|بدون حركه)$/.test(t)) return a11y('toggle', 'noanim');
    if (/^(اقرا الصفحه|اقرالي الصفحه|اقرا|اقرا المحتوي|اقرا لي الصفحه)$/.test(t)) return a11y('readPage');
    if (/^(اقرا المحدد|اقرا النص المحدد)$/.test(t)) return a11y('readSelection');
    if (/^(وقف القراءه|وقف القرايه|ايقاف القراءه|اسكت|كفايه قراءه|بطل قراءه)$/.test(t)) return a11y('stopReading', undefined, 'تم إيقاف القراءة');
    if (/^(ادوات الاتاحه|الاتاحه|افتح ادوات الاتاحه)$/.test(t)) return a11y('open');

    // Colour theme (window.KFSTheme from layouts/app.blade.php)
    if (/^((الوضع|وضع) (الليلي|المظلم|الداكن)|ليلي|الليلي|دارك|دارك مود|شغل الوضع الليلي|فعل الوضع الليلي)$/.test(t)) return theme('dark');
    if (/^((الوضع|وضع) (النهاري|الفاتح|العادي)|نهاري|النهاري|لايت|لايت مود|شغل الوضع النهاري|فعل الوضع النهاري)$/.test(t)) return theme('light');
    if (/^(تبديل الوضع|بدل الوضع|غير الوضع|تغيير الوضع|تبديل الالوان)$/.test(t)) return theme('toggle');

    // Click on visible link / button
    m = t.match(/^(?:اضغط|دوس|انقر|اختار|اختر)(?:\s+(?:علي|على))?\s+(.+)$/);
    if (m) return clickByText(m[1]);

    // Navigation: "افتح الخدمات", "روح للأخبار", or the bare target name
    m = t.match(new RegExp('^' + NAV_VERBS + '\\s+(.+)$'));
    var phrase = m ? m[1] : t;
    var key = findTarget(phrase);
    if (key) return go(key);
    if (m) return clickByText(m[1]); // "افتح <link text>"

    feedback('لم أفهم الأمر «' + original + '». قل «ساعدني» لعرض الأوامر.', 'error');
    return false;
  }

  /* ------------------------------------------------------------ help dialog */
  var helpDialog;
  function openHelp() {
    if (!helpDialog) {
      helpDialog = document.createElement('div');
      helpDialog.id = 'kfsVoiceHelp';
      helpDialog.className = 'kfs-voice-help';
      helpDialog.setAttribute('role', 'dialog');
      helpDialog.setAttribute('aria-modal', 'true');
      helpDialog.setAttribute('aria-labelledby', 'kfsVoiceHelpTitle');
      helpDialog.hidden = true;
      var rows = HELP.map(function (r) {
        return '<li><strong>«' + r[0] + '»</strong><span>' + r[1] + '</span></li>';
      }).join('');
      helpDialog.innerHTML =
        '<div class="kfs-voice-help__box">' +
          '<div class="kfs-voice-help__head"><h2 id="kfsVoiceHelpTitle"><i class="fa-solid fa-microphone-lines" aria-hidden="true"></i> الأوامر الصوتية</h2>' +
          '<button type="button" class="kfs-a11y-close" data-voice-help-close aria-label="إغلاق قائمة الأوامر"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>' +
          '<p>اضغط زر الميكروفون أو <kbd>Alt</kbd>+<kbd>V</kbd> ثم انطق أحد الأوامر التالية باللغة العربية:</p>' +
          '<ul>' + rows + '</ul>' +
        '</div>';
      document.body.appendChild(helpDialog);
      helpDialog.addEventListener('click', function (e) {
        if (e.target === helpDialog || e.target.closest('[data-voice-help-close]')) closeHelp();
      });
      helpDialog.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { e.preventDefault(); closeHelp(); }
        if (e.key === 'Tab') { e.preventDefault(); helpDialog.querySelector('[data-voice-help-close]').focus(); }
      });
    }
    helpDialog._return = document.activeElement;
    helpDialog.hidden = false;
    helpDialog.querySelector('[data-voice-help-close]').focus();
    announce('قائمة الأوامر الصوتية مفتوحة');
  }
  function closeHelp() {
    if (!helpDialog) return;
    helpDialog.hidden = true;
    if (helpDialog._return && helpDialog._return.focus) helpDialog._return.focus();
  }

  /* ------------------------------------------------------------ recognition */
  var recognition = null, listening = false, langIndex = 0;
  var LANGS = ['ar-EG', 'ar-SA'];
  try { if (localStorage.getItem('kfs-voice-lang') === 'ar-SA') langIndex = 1; } catch (e) { /* ignore */ }

  function setUi(on) {
    listening = on;
    document.documentElement.classList.toggle('kfs-voice-listening', on);
    if (btn) {
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.setAttribute('aria-label', on ? 'إيقاف الأوامر الصوتية' : 'تشغيل الأوامر الصوتية (Alt+V)');
    }
  }

  function create() {
    var r = new Recognition();
    r.lang = LANGS[langIndex];
    r.interimResults = true;
    r.continuous = false;
    r.maxAlternatives = 3;
    r.onstart = function () { setUi(true); showStatus('جارٍ الاستماع… تكلّم الآن', 'listening'); announce('جارٍ الاستماع'); };
    r.onresult = function (event) {
      var interim = '', finals = [];
      for (var i = event.resultIndex; i < event.results.length; i++) {
        var res = event.results[i];
        if (res.isFinal) {
          for (var k = 0; k < res.length; k++) finals.push(res[k].transcript);
        } else {
          interim += res[0].transcript;
        }
      }
      if (interim) showStatus('«' + interim.trim() + '»', 'listening');
      if (finals.length) {
        // Try alternatives until one is understood.
        var ok = false;
        for (var a = 0; a < finals.length && !ok; a++) {
          ok = a === finals.length - 1 ? execute(finals[a]) : tryQuiet(finals[a]);
        }
      }
    };
    r.onerror = function (e) {
      if (e.error === 'language-not-supported' && langIndex === 0) {
        langIndex = 1;
        try { localStorage.setItem('kfs-voice-lang', 'ar-SA'); } catch (x) { /* ignore */ }
        recognition = null;
        setTimeout(start, 100);
        return;
      }
      var msgs = {
        'not-allowed': 'لم يتم السماح باستخدام الميكروفون. فعّل الإذن من إعدادات المتصفح.',
        'service-not-allowed': 'خدمة التعرف على الصوت غير مسموحة في هذا المتصفح.',
        'no-speech': 'لم أسمع شيئاً، حاول مرة أخرى.',
        'audio-capture': 'لم يتم العثور على ميكروفون.',
        'network': 'تعذّر الوصول لخدمة التعرف على الصوت، تحقّق من الاتصال.',
      };
      if (e.error !== 'aborted') feedback(msgs[e.error] || 'حدث خطأ في التعرف على الصوت', 'error');
    };
    r.onend = function () { setUi(false); if (statusEl && statusEl.getAttribute('data-kind') === 'listening') showStatus(''); };
    return r;
  }

  // Run a candidate silently: only commit when it matches a known command.
  function tryQuiet(text) {
    var t = normalize(text);
    var known = /^(ساعدني|ابحث|دور|بحث|انزل|اطلع|اول الصفحه|اخر الصفحه|رجوع|ارجع|كبر|صغر|تباين|وقف|اقرا|اضغط|دوس|انقر)/.test(t) ||
      !!findTarget(t.replace(new RegExp('^' + NAV_VERBS + '\\s+'), ''));
    return known ? execute(text) : false;
  }

  function unsupported() {
    var msg = 'متصفحك لا يدعم الأوامر الصوتية. استخدم Google Chrome أو Microsoft Edge أو Safari الحديث.';
    if (window.Swal && window.Swal.fire) {
      window.Swal.fire({ icon: 'info', title: 'الأوامر الصوتية غير مدعومة', text: msg, confirmButtonText: 'حسناً' });
    }
    feedback(msg, 'error');
  }

  function start() {
    if (!Recognition) { unsupported(); return; }
    if (!window.isSecureContext) { feedback('الأوامر الصوتية تتطلب اتصالاً آمناً (HTTPS).', 'error'); return; }
    if (window.KFSA11y && window.KFSA11y.stopReading) window.KFSA11y.stopReading();
    try {
      if (!recognition) recognition = create();
      recognition.lang = LANGS[langIndex];
      recognition.start();
    } catch (e) { /* already started */ }
  }

  function stop() {
    if (recognition && listening) { try { recognition.stop(); } catch (e) { /* ignore */ } }
    setUi(false);
  }

  function toggle() { listening ? stop() : start(); }

  /* ------------------------------------------------------------ init */
  function init() {
    btn = document.getElementById('kfsVoiceBtn');
    statusEl = document.getElementById('kfsVoiceStatus');
    if (btn) {
      btn.setAttribute('aria-pressed', 'false');
      if (!Recognition) btn.classList.add('is-unsupported');
      btn.addEventListener('click', toggle);
    }
    document.querySelectorAll('[data-voice-help]').forEach(function (b) {
      b.addEventListener('click', function (e) { e.preventDefault(); openHelp(); });
    });
    document.addEventListener('keydown', function (e) {
      if (e.altKey && !e.ctrlKey && !e.metaKey && (e.code === 'KeyV' || (e.key || '').toLowerCase() === 'v' || e.key === 'ر')) {
        e.preventDefault();
        toggle();
      }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  window.KFSVoice = { start: start, stop: stop, toggle: toggle, execute: execute, normalize: normalize, help: openHelp, supported: !!Recognition };
})();
