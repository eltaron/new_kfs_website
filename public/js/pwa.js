/*!
 * KFS PWA bootstrap — تسجيل عامل الخدمة + إشعار التحديث + التثبيت
 * Loaded by layouts/app.blade.php:
 *   <script src="/js/pwa.js" data-sw="/sw.js?v=MTIME" data-scope="/" defer></script>
 */
(function () {
  'use strict';

  var script = document.currentScript || document.querySelector('script[data-sw]');
  var swUrl = script && script.getAttribute('data-sw');
  var scope = (script && script.getAttribute('data-scope')) || '/';

  function store(key, value) {
    try {
      if (value === undefined) return window.localStorage.getItem(key);
      window.localStorage.setItem(key, value);
    } catch (e) { return null; }
    return null;
  }

  function isAuthenticated() {
    var meta = document.querySelector('meta[name="kfs-auth"]');
    return !!(meta && meta.getAttribute('content') === '1');
  }

  function toast(message, icon) {
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        toast: true, position: 'bottom-start', icon: icon || 'info', title: message,
        showConfirmButton: false, timer: 3500, timerProgressBar: true,
      });
    }
  }

  /* ---------- connectivity feedback ---------- */
  window.addEventListener('offline', function () {
    document.documentElement.classList.add('kfs-offline');
    toast('أنت الآن غير متصل بالإنترنت', 'warning');
  });
  window.addEventListener('online', function () {
    document.documentElement.classList.remove('kfs-offline');
    toast('تمت استعادة الاتصال بالإنترنت', 'success');
  });

  /* ---------- install: native prompt (Chrome/Edge/Android) + iOS instructions + promo card ---------- */
  var deferredPrompt = null;
  var ua = navigator.userAgent || '';
  var isIOS = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isIOSSafari = isIOS && /safari/i.test(ua) && !/crios|fxios|edgios|opios/i.test(ua);
  var inPanel = /^\/(admin|gis|estidama_panal|filament|council|vendor|erp-governorate)(\/|$)/.test(location.pathname);
  var DISMISS_KEY = 'kfs-pwa-dismissed-at';
  var DISMISS_DAYS = 14;

  function isStandalone() {
    return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
  }
  function recentlyDismissed() {
    var at = parseInt(store(DISMISS_KEY) || '0', 10);
    return at && (Date.now() - at) < DISMISS_DAYS * 864e5;
  }
  function showInstallButtons() {
    document.querySelectorAll('[data-pwa-install]').forEach(function (btn) { btn.hidden = false; });
  }
  function hideInstallButtons() {
    document.querySelectorAll('[data-pwa-install]').forEach(function (btn) { btn.hidden = true; });
  }

  var card = null;
  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }
  function icon(cls) { var i = el('i', cls); i.setAttribute('aria-hidden', 'true'); return i; }

  function buildCard(mode) {
    if (card) card.remove();
    card = el('section', 'kfs-pwa-card');
    card.setAttribute('role', 'dialog');
    card.setAttribute('aria-labelledby', 'kfsPwaTitle');
    var close = el('button', 'kfs-pwa-card__close'); close.type = 'button'; close.setAttribute('aria-label', 'إغلاق'); close.append(icon('fa-solid fa-xmark'));
    var head = el('div', 'kfs-pwa-card__head');
    var img = el('img', 'kfs-pwa-card__icon'); img.src = '/images/pwa/icon-192.png'; img.alt = ''; img.width = 56; img.height = 56;
    var txt = el('div');
    var h = el('h2', 'kfs-pwa-card__title', 'ثبّت تطبيق بوابة كفر الشيخ'); h.id = 'kfsPwaTitle';
    var p = el('p', 'kfs-pwa-card__text', 'وصول أسرع للخدمات والأخبار من شاشتك الرئيسية — بدون متجر تطبيقات ولا مساحة كبيرة.');
    txt.append(h, p); head.append(img, txt);
    card.append(close, head);

    if (mode === 'ios') {
      var steps = el('ol', 'kfs-pwa-card__steps');
      var s1 = el('li'); s1.append(document.createTextNode('اضغط زر المشاركة '), icon('fa-solid fa-arrow-up-from-bracket'), document.createTextNode(' أسفل المتصفح'));
      var s2 = el('li'); s2.append(document.createTextNode('اختر «إضافة إلى الشاشة الرئيسية» '), icon('fa-regular fa-square-plus'));
      var s3 = el('li', null, 'اضغط «إضافة» — وسيظهر التطبيق على شاشتك');
      steps.append(s1, s2, s3);
      card.append(steps);
      var ok = el('button', 'kfs-pwa-card__btn kfs-pwa-card__btn--ghost', 'فهمت'); ok.type = 'button';
      ok.addEventListener('click', function () { dismiss(); });
      var actions = el('div', 'kfs-pwa-card__actions'); actions.append(ok); card.append(actions);
    } else {
      var actions2 = el('div', 'kfs-pwa-card__actions');
      var install = el('button', 'kfs-pwa-card__btn kfs-pwa-card__btn--primary'); install.type = 'button';
      install.append(icon('fa-solid fa-download'), document.createTextNode(' تثبيت التطبيق'));
      install.addEventListener('click', function () { runPrompt(); });
      var later = el('button', 'kfs-pwa-card__btn kfs-pwa-card__btn--ghost', 'لاحقاً'); later.type = 'button';
      later.addEventListener('click', function () { dismiss(); });
      actions2.append(install, later); card.append(actions2);
    }
    close.addEventListener('click', function () { dismiss(); });
    document.body.append(card);
    requestAnimationFrame(function () { card.classList.add('is-open'); });
    document.addEventListener('keydown', function onKey(e) { if (e.key === 'Escape' && card) { dismiss(); document.removeEventListener('keydown', onKey); } });
  }
  function closeCard() {
    if (!card) return;
    var c = card; card = null;
    c.classList.remove('is-open');
    setTimeout(function () { c.remove(); }, 300);
  }
  function dismiss() { store(DISMISS_KEY, String(Date.now())); closeCard(); }

  function runPrompt() {
    if (!deferredPrompt) { if (isIOS) buildCard('ios'); return; }
    var prompt = deferredPrompt;
    deferredPrompt = null;
    prompt.prompt();
    prompt.userChoice.then(function (choice) {
      if (choice && choice.outcome === 'accepted') { hideInstallButtons(); closeCard(); }
      else { dismiss(); }
    }).catch(function () {});
  }

  function scheduleCard(mode) {
    if (inPanel || isStandalone() || recentlyDismissed() || store('kfs-pwa-installed') === '1') return;
    setTimeout(function () { if (!card && !isStandalone()) buildCard(mode); }, 6000);
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    if (!inPanel) { showInstallButtons(); scheduleCard('prompt'); }
  });
  // iOS Safari never fires beforeinstallprompt: show the buttons + instructions instead.
  if (isIOSSafari && !isStandalone() && !inPanel) {
    document.addEventListener('DOMContentLoaded', showInstallButtons);
    if (document.readyState !== 'loading') showInstallButtons();
    scheduleCard('ios');
  }
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-pwa-install]');
    if (!btn) return;
    e.preventDefault();
    if (deferredPrompt) runPrompt();
    else if (isIOS) buildCard('ios');
  });
  window.addEventListener('appinstalled', function () {
    store('kfs-pwa-installed', '1');
    hideInstallButtons();
    closeCard();
    toast('تم تثبيت تطبيق بوابة كفر الشيخ', 'success');
  });

  if (!swUrl || !('serviceWorker' in navigator)) return;
  // Never register from inside the admin / GIS / Estidama panels.
  if (/^\/(admin|gis|estidama_panal|filament)(\/|$)/.test(location.pathname)) return;

  /* ---------- auth state → SW (no HTML caching for logged-in users) ---------- */
  function postAuthState(target) {
    if (!target) return;
    var auth = isAuthenticated();
    var last = store('kfs-pwa-auth');
    var changed = last !== null && last !== String(auth);
    store('kfs-pwa-auth', String(auth));
    try { target.postMessage({ type: 'AUTH_STATE', auth: auth, changed: changed }); } catch (e) { /* ignore */ }
  }

  /* ---------- update flow (user-confirmed skipWaiting) ---------- */
  var refreshing = false;
  navigator.serviceWorker.addEventListener('controllerchange', function () {
    if (refreshing) return;
    refreshing = true;
    window.location.reload();
  });

  function promptUpdate(worker) {
    if (!worker || promptUpdate.shown) return;
    promptUpdate.shown = true;
    var apply = function () { worker.postMessage({ type: 'SKIP_WAITING' }); };
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        toast: true,
        position: 'bottom-start',
        icon: 'info',
        title: 'يتوفر إصدار أحدث من البوابة',
        text: 'هل تريد التحديث الآن؟',
        showConfirmButton: true,
        confirmButtonText: 'تحديث',
        showCancelButton: true,
        cancelButtonText: 'لاحقاً',
        timer: undefined,
      }).then(function (r) { if (r.isConfirmed) apply(); });
    } else if (window.confirm('يتوفر إصدار أحدث من البوابة. هل تريد التحديث الآن؟')) {
      apply();
    }
  }

  window.addEventListener('load', function () {
    navigator.serviceWorker.register(swUrl, { scope: scope }).then(function (reg) {
      postAuthState(navigator.serviceWorker.controller || reg.active);
      navigator.serviceWorker.ready.then(function (r) { postAuthState(r.active); });

      if (reg.waiting && navigator.serviceWorker.controller) promptUpdate(reg.waiting);
      reg.addEventListener('updatefound', function () {
        var nw = reg.installing;
        if (!nw) return;
        nw.addEventListener('statechange', function () {
          if (nw.state === 'installed' && navigator.serviceWorker.controller) promptUpdate(nw);
        });
      });
      // Check for updates when the tab becomes visible again.
      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') reg.update().catch(function () {});
      });
    }).catch(function () { /* SW unsupported / blocked: site works normally */ });
  });

  // Logout links/forms: tell the SW to forget cached pages right away.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form && form.action && /\/logout(\?|$)/.test(form.action) && navigator.serviceWorker.controller) {
      navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_PAGES' });
      store('kfs-pwa-auth', 'false');
    }
  }, true);
})();
