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

  /* ---------- install prompt ---------- */
  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    document.querySelectorAll('[data-pwa-install]').forEach(function (btn) {
      btn.hidden = false;
    });
  });
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-pwa-install]');
    if (!btn || !deferredPrompt) return;
    e.preventDefault();
    deferredPrompt.prompt();
    deferredPrompt.userChoice.finally(function () {
      deferredPrompt = null;
      btn.hidden = true;
    });
  });
  window.addEventListener('appinstalled', function () {
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
