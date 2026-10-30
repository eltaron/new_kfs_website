/*!
 * KFS Motion — بوابة محافظة كفر الشيخ (vanilla, no dependencies)
 * ---------------------------------------------------------------
 * Scroll reveal (data-reveal / data-stagger / data-img-reveal / data-draw),
 * counters (data-count), parallax-light (data-parallax / data-speed),
 * page-load progress, back-to-top ring, ripple, card shine, ticker,
 * lightbox (data-lightbox), toasts (KFSMotion.toast), shimmer images.
 *
 * Motion is ON only while <html> has `kfs-motion` — which the <head>
 * bootstrap sets when JS runs, «إيقاف الحركة» (kfs-a11y-noanim) is off
 * and prefers-reduced-motion is not "reduce". This file keeps that class
 * in sync live, so toggling the accessibility option stops everything.
 */
(function () {
  'use strict';

  var root = document.documentElement;
  var mq = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var isRTL = function () { return (root.getAttribute('dir') || 'rtl') === 'rtl'; };

  function motionOK() {
    return !root.classList.contains('kfs-a11y-noanim') && !(mq && mq.matches);
  }

  function syncMotionClass() {
    var ok = motionOK();
    root.classList.toggle('kfs-motion', ok);
    if (!ok) settleEverything();
    toggleAutoplay(ok);
    syncToggleButtons();
    return ok;
  }

  /* ------------------------------------------------------------ helpers */
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function belowFold(el) {
    var r = el.getBoundingClientRect();
    return r.top > (window.innerHeight || root.clientHeight) * 0.92;
  }
  var SKIP = '.swiper, .carousel, .leaflet-container, .modal, .dropdown-menu, .mobile-menu-panel, .search-overlay, [data-no-motion], .kfs-a11y-panel, form';

  /* ------------------------------------------------------------ auto-tag inner pages */
  function autoTag() {
    var main = document.getElementById('main-content');
    if (!main) return;
    // Grids of cards → stagger (only those not yet on screen, so nothing visible ever blinks)
    $$('.row', main).forEach(function (row) {
      if (row.closest('[data-reveal],[data-stagger],' + SKIP)) return;
      if (row.querySelector('[data-reveal],[data-stagger]')) return;
      var cols = $$(':scope > [class*="col"]', row);
      if (cols.length < 2 || cols.length > 24) return;
      if (!belowFold(row)) return;
      row.setAttribute('data-stagger', '');
    });
    $$('.card, .content-block, .sidebar-widget, .post-content-card, .accordion, .table-responsive, .kfs-empty, .directory-category-section, .support-wrapper, .official-main-card', main)
      .forEach(function (el) {
        if (el.closest('[data-reveal],[data-stagger],' + SKIP)) return;
        if (!belowFold(el)) return;
        el.setAttribute('data-reveal', '');
      });
  }

  /* ------------------------------------------------------------ legacy hero waves */
  function enhanceHeroes() {
    $$('.page-header:not(.kfs-page-hero), .post-header, .about-hero, .support-header, .official-header, .mkt-hero').forEach(function (h) {
      if (h.querySelector('.kfs-wavebar')) return;
      h.classList.add('kfs-has-wave');
      var cs = window.getComputedStyle(h);
      if (cs.position === 'static') h.style.position = 'relative';
      h.insertAdjacentHTML('beforeend',
        '<div class="kfs-wavebar is-animated" aria-hidden="true">' +
        '<svg class="kfs-wavebar__svg kfs-wavebar__svg--back" viewBox="0 0 2880 80" preserveAspectRatio="none" focusable="false"><path class="kfs-wavebar__line2" d="M0 34 C240 52 480 52 720 34 S1200 16 1440 34 S1920 52 2160 34 S2640 16 2880 34"/></svg>' +
        '<svg class="kfs-wavebar__svg" viewBox="0 0 2880 80" preserveAspectRatio="none" focusable="false"><path class="kfs-wavebar__fill" style="fill:var(--kfs-page-bg, var(--kfs-ivory))" d="M0 40 C240 10 480 10 720 40 S1200 70 1440 40 S1920 10 2160 40 S2640 70 2880 40 V80 H0Z"/><path class="kfs-wavebar__line" d="M0 40 C240 10 480 10 720 40 S1200 70 1440 40 S1920 10 2160 40 S2640 70 2880 40"/></svg>' +
        '</div>');
    });
  }

  /* ------------------------------------------------------------ reveal observer */
  var io = null;
  function reveal(el) {
    el.classList.add('is-revealed');
    if (el.hasAttribute('data-count')) runCounter(el);
    $$('[data-count]', el).forEach(runCounter);
  }
  function initReveal() {
    $$('[data-stagger]').forEach(function (g) {
      Array.prototype.forEach.call(g.children, function (c, i) { c.style.setProperty('--i', Math.min(i, 10)); });
    });
    $$('[data-delay]').forEach(function (el) {
      var d = parseFloat(el.getAttribute('data-delay'));
      if (!isNaN(d)) el.style.setProperty('--kfs-delay', (d > 10 ? d / 1000 : d * 0.08) + 's');
    });
    var targets = $$('[data-reveal], [data-stagger], [data-img-reveal], [data-draw], [data-count]');
    if (!('IntersectionObserver' in window) || !motionOK()) { targets.forEach(function (t) { t.classList.add('is-revealed'); }); return; }
    io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { reveal(en.target); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    targets.forEach(function (t) {
      if (t.hasAttribute('data-count') && !t.closest('[data-reveal],[data-stagger]')) {
        // stand-alone counters: start from 0 only if not visible yet
        if (belowFold(t)) t.textContent = formatNum(0, t);
      }
      io.observe(t);
    });
  }
  function settleEverything() {
    $$('[data-reveal], [data-stagger], [data-img-reveal], [data-draw]').forEach(function (t) { t.classList.add('is-revealed'); });
    $$('[data-count]').forEach(function (c) { c.textContent = formatNum(parseFloat(c.getAttribute('data-count')) || 0, c); c.setAttribute('data-counted', '1'); });
    $$('[data-parallax]').forEach(function (p) { p.style.removeProperty('--kfs-py'); });
  }

  /* ------------------------------------------------------------ counters */
  function formatNum(v, el) {
    var dec = parseInt(el.getAttribute('data-decimals') || '0', 10) || 0;
    try { return v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }
    catch (e) { return v.toFixed(dec); }
  }
  function runCounter(el) {
    if (el.getAttribute('data-counted')) return;
    el.setAttribute('data-counted', '1');
    var target = parseFloat(el.getAttribute('data-count')) || 0;
    if (!motionOK() || target === 0) { el.textContent = formatNum(target, el); return; }
    var dur = Math.min(2400, 1200 + Math.log10(Math.max(10, target)) * 220);
    var t0 = null;
    el.setAttribute('aria-label', formatNum(target, el));
    function step(ts) {
      if (!t0) t0 = ts;
      var p = Math.min(1, (ts - t0) / dur);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = formatNum(p < 1 ? target * eased : target, el);
      if (p < 1 && motionOK()) window.requestAnimationFrame(step);
      else el.textContent = formatNum(target, el);
    }
    el.textContent = formatNum(0, el);
    window.requestAnimationFrame(step);
  }

  /* ------------------------------------------------------------ scroll: parallax + back-to-top ring */
  var parallaxEls = [];
  var ringFg = null;
  var ticking = false;
  function onScroll() {
    ticking = false;
    var y = window.scrollY || window.pageYOffset;
    if (ringFg) {
      var max = root.scrollHeight - root.clientHeight;
      ringFg.style.strokeDashoffset = String(100 - (max > 0 ? Math.min(1, y / max) : 0) * 100);
    }
    if (!parallaxEls.length || !motionOK()) return;
    var vh = window.innerHeight;
    parallaxEls.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.bottom < -100 || r.top > vh + 100) return;
      var speed = parseFloat(el.getAttribute('data-speed') || '0.2');
      var center = r.top + r.height / 2 - vh / 2;
      el.style.setProperty('--kfs-py', (center * -speed).toFixed(1) + 'px');
    });
  }
  function initScroll() {
    parallaxEls = $$('[data-parallax]');
    var btt = document.getElementById('kfsBackToTop');
    if (btt && !btt.querySelector('.kfs-btt-ring')) {
      btt.insertAdjacentHTML('afterbegin',
        '<svg class="kfs-btt-ring" viewBox="0 0 40 40" aria-hidden="true" focusable="false">' +
        '<circle class="ring-bg" cx="20" cy="20" r="18"/><circle class="ring-fg" cx="20" cy="20" r="18" pathLength="100"/></svg>');
      ringFg = btt.querySelector('.ring-fg');
    }
    window.addEventListener('scroll', function () {
      if (!ticking) { ticking = true; window.requestAnimationFrame(onScroll); }
    }, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    onScroll();
  }

  /* ------------------------------------------------------------ page-load progress */
  function initPageProgress() {
    var bar = document.getElementById('kfsPageProgress');
    if (!bar) return;
    function start() {
      if (!motionOK()) return;
      bar.classList.remove('is-done');
      void bar.offsetWidth;
      bar.classList.add('is-active');
    }
    function done() {
      bar.classList.add('is-done');
      setTimeout(function () { bar.classList.remove('is-active', 'is-done'); }, 900);
    }
    if (document.readyState !== 'complete') {
      start();
      window.addEventListener('load', done);
    }
    window.addEventListener('pageshow', function (e) { if (e.persisted) { bar.classList.remove('is-active', 'is-done'); } });
    document.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target.closest && e.target.closest('a[href]');
      if (!a || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-lightbox') || a.hasAttribute('data-fslightbox')) return;
      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
      if (a.origin !== location.origin) return;
      if (a.pathname === location.pathname && a.search === location.search && a.hash) return;
      start();
    });
  }

  /* ------------------------------------------------------------ ripple */
  var RIPPLE = '.btn-kfs, .btn-kfs-outline, .btn-kfs-cyan, .cta-button, .btn-gold-lg, .btn-modern-gold, .modal-submit-btn, .btn-primary, .btn-success, .btn-dark, .btn-info, .kfs-qs';
  function initRipple() {
    document.addEventListener('pointerdown', function (e) {
      if (!motionOK()) return;
      var b = e.target.closest && e.target.closest(RIPPLE);
      if (!b) return;
      var r = b.getBoundingClientRect();
      var size = Math.max(r.width, r.height) * 2.2;
      var s = document.createElement('span');
      s.className = 'kfs-ripple';
      s.setAttribute('aria-hidden', 'true');
      s.style.width = s.style.height = size + 'px';
      s.style.left = (e.clientX - r.left - size / 2) + 'px';
      s.style.top = (e.clientY - r.top - size / 2) + 'px';
      if (window.getComputedStyle(b).position === 'static') b.style.position = 'relative';
      b.appendChild(s);
      setTimeout(function () { s.remove(); }, 700);
    }, { passive: true });
  }

  /* ------------------------------------------------------------ card shine */
  var SHINE = '.service-card, .kfs-news-card, .news-card, .landmark-card, .investment-card, .project-split-card, .event-card, .investment-archive-card, .kfs-qs, .kfs-tcard, .kfs-icard, .kfs-ncard, .kfs-timeline__card';
  function initShine() {
    $$(SHINE).forEach(function (c) {
      if (c.querySelector(':scope > .kfs-shine-el')) return;
      c.classList.add('kfs-has-shine');
      var s = document.createElement('span');
      s.className = 'kfs-shine-el';
      s.setAttribute('aria-hidden', 'true');
      c.appendChild(s);
    });
  }

  /* ------------------------------------------------------------ shimmer images */
  function initShimmer() {
    $$('.kfs-shimmer').forEach(function (w) {
      var img = w.querySelector('img');
      if (!img) { w.classList.add('is-loaded'); return; }
      var ok = function () { w.classList.add('is-loaded'); };
      if (img.complete) ok(); else { img.addEventListener('load', ok); img.addEventListener('error', ok); }
    });
  }

  /* ------------------------------------------------------------ ticker */
  function initTickers() {
    $$('.kfs-ticker').forEach(function (t) {
      var track = t.querySelector('.kfs-ticker__track');
      if (!track || track.classList.contains('is-ready')) return;
      var items = Array.prototype.slice.call(track.children);
      if (!items.length) return;
      // Duplicate the list for a seamless loop; clones are hidden from AT and keyboard.
      items.forEach(function (li) {
        var c = li.cloneNode(true);
        c.classList.add('is-clone');
        c.setAttribute('aria-hidden', 'true');
        $$('a, button', c).forEach(function (a) { a.setAttribute('tabindex', '-1'); });
        track.appendChild(c);
      });
      var w = track.scrollWidth / 2;
      track.style.setProperty('--kfs-ticker-dur', Math.max(20, Math.round(w / 55)) + 's');
      track.classList.add('is-ready');
      var btn = t.querySelector('.kfs-ticker__pause');
      if (btn) btn.addEventListener('click', function () {
        var paused = t.classList.toggle('is-paused');
        btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
        btn.setAttribute('aria-label', paused ? 'تشغيل شريط الأخبار' : 'إيقاف شريط الأخبار مؤقتاً');
        btn.innerHTML = '<i class="fa-solid ' + (paused ? 'fa-play' : 'fa-pause') + '" aria-hidden="true"></i>';
      });
    });
  }

  /* ------------------------------------------------------------ lightbox */
  var lb = null, lbItems = [], lbIndex = 0, lbReturn = null;
  function buildLightbox() {
    lb = document.createElement('div');
    lb.className = 'kfs-lightbox';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.setAttribute('aria-label', 'عرض الصورة');
    lb.hidden = true;
    lb.innerHTML =
      '<button type="button" class="kfs-lightbox__btn kfs-lightbox__close" aria-label="إغلاق"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
      '<button type="button" class="kfs-lightbox__btn kfs-lightbox__prev" aria-label="الصورة السابقة"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>' +
      '<figure class="kfs-lightbox__figure"><img class="kfs-lightbox__img" alt=""><figcaption class="kfs-lightbox__cap"></figcaption></figure>' +
      '<button type="button" class="kfs-lightbox__btn kfs-lightbox__next" aria-label="الصورة التالية"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>';
    document.body.appendChild(lb);
    lb.addEventListener('click', function (e) {
      if (e.target === lb || e.target.closest('.kfs-lightbox__close')) closeLightbox();
      else if (e.target.closest('.kfs-lightbox__prev')) showLb(lbIndex - 1);
      else if (e.target.closest('.kfs-lightbox__next')) showLb(lbIndex + 1);
    });
    lb.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeLightbox();
      else if (e.key === 'ArrowLeft') showLb(lbIndex + (isRTL() ? 1 : -1));
      else if (e.key === 'ArrowRight') showLb(lbIndex + (isRTL() ? -1 : 1));
      else if (e.key === 'Tab') {
        var f = $$('button', lb).filter(function (b) { return !b.hidden; });
        var i = f.indexOf(document.activeElement);
        e.preventDefault();
        f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
      }
    });
  }
  function showLb(i) {
    if (!lbItems.length) return;
    lbIndex = (i + lbItems.length) % lbItems.length;
    var a = lbItems[lbIndex];
    var img = lb.querySelector('.kfs-lightbox__img');
    var thumb = a.querySelector('img');
    img.src = a.getAttribute('href');
    img.alt = (thumb && thumb.alt) || a.getAttribute('data-caption') || '';
    lb.querySelector('.kfs-lightbox__cap').textContent = a.getAttribute('data-caption') || (thumb && thumb.alt) || '';
    var multi = lbItems.length > 1;
    lb.querySelector('.kfs-lightbox__prev').hidden = !multi;
    lb.querySelector('.kfs-lightbox__next').hidden = !multi;
  }
  function openLightbox(a) {
    if (!lb) buildLightbox();
    var group = a.getAttribute('data-lightbox');
    lbItems = group ? $$('a[data-lightbox="' + group.replace(/"/g, '') + '"]') : [a];
    lbReturn = a;
    lb.hidden = false;
    showLb(lbItems.indexOf(a));
    document.body.classList.add('body-no-scroll');
    requestAnimationFrame(function () { lb.classList.add('is-open'); lb.querySelector('.kfs-lightbox__close').focus(); });
  }
  function closeLightbox() {
    if (!lb) return;
    lb.classList.remove('is-open');
    document.body.classList.remove('body-no-scroll');
    setTimeout(function () { lb.hidden = true; }, motionOK() ? 350 : 0);
    if (lbReturn && lbReturn.focus) lbReturn.focus();
  }
  function initLightbox() {
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[data-lightbox]');
      if (!a || e.ctrlKey || e.metaKey) return;
      e.preventDefault();
      openLightbox(a);
    });
  }

  /* ------------------------------------------------------------ toasts */
  function toast(msg, type, timeout) {
    var box = document.getElementById('kfsToasts');
    if (!box) {
      box = document.createElement('div');
      box.id = 'kfsToasts';
      box.className = 'kfs-toasts';
      box.setAttribute('role', 'status');
      box.setAttribute('aria-live', 'polite');
      document.body.appendChild(box);
    }
    var icons = { success: 'fa-circle-check', warning: 'fa-triangle-exclamation', danger: 'fa-circle-exclamation', info: 'fa-circle-info' };
    type = icons[type] ? type : 'info';
    var t = document.createElement('div');
    t.className = 'kfs-toast kfs-toast--' + type;
    t.innerHTML = '<i class="fa-solid ' + icons[type] + '" aria-hidden="true"></i><span></span>' +
      '<button type="button" class="kfs-toast__close" aria-label="إغلاق التنبيه"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>';
    t.querySelector('span').textContent = msg;
    box.appendChild(t);
    var kill = function () {
      if (!t.parentNode) return;
      t.classList.add('is-leaving');
      setTimeout(function () { t.remove(); }, motionOK() ? 320 : 0);
    };
    t.querySelector('.kfs-toast__close').addEventListener('click', kill);
    setTimeout(kill, timeout || 5000);
    return t;
  }

  /* ------------------------------------------------------------ autoplay (swiper / carousel / video) */
  function toggleAutoplay(on) {
    $$('.swiper').forEach(function (s) {
      var sw = s.swiper;
      if (sw && sw.autoplay && sw.params && sw.params.autoplay && sw.params.autoplay.delay) {
        try { on ? sw.autoplay.start() : sw.autoplay.stop(); } catch (e) {}
      }
    });
    if (window.bootstrap && window.bootstrap.Carousel) {
      $$('.carousel').forEach(function (c) {
        var inst = window.bootstrap.Carousel.getInstance(c);
        if (inst) { try { on ? inst.cycle() : inst.pause(); } catch (e) {} }
      });
    }
    $$('video[autoplay]').forEach(function (v) {
      if (v.closest('#signLangPlayer')) return;
      try { on ? v.play().catch(function () {}) : v.pause(); } catch (e) {}
    });
  }

  /* ------------------------------------------------------------ «إيقاف الحركة» buttons */
  function syncToggleButtons() {
    var off = !root.classList.contains('kfs-motion');
    $$('[data-motion-toggle]').forEach(function (b) {
      b.setAttribute('aria-pressed', root.classList.contains('kfs-a11y-noanim') ? 'true' : 'false');
      var lbl = b.querySelector('.lbl');
      if (lbl) lbl.textContent = root.classList.contains('kfs-a11y-noanim') ? 'تشغيل الحركة' : 'إيقاف الحركة';
      var ic = b.querySelector('i');
      if (ic) ic.className = 'fa-solid ' + (off ? 'fa-circle-play' : 'fa-circle-pause');
    });
  }
  function initToggleButtons() {
    document.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-motion-toggle]');
      if (!b) return;
      e.preventDefault();
      if (window.KFSA11y && window.KFSA11y.toggle) {
        window.KFSA11y.toggle('noanim');
      } else {
        root.classList.toggle('kfs-a11y-noanim');
        try {
          var s = JSON.parse(localStorage.getItem('kfs-a11y-v1') || '{}');
          s.noanim = root.classList.contains('kfs-a11y-noanim');
          localStorage.setItem('kfs-a11y-v1', JSON.stringify(s));
        } catch (err) { /* storage blocked */ }
      }
    });
  }

  /* ------------------------------------------------------------ boot */
  function boot() {
    enhanceHeroes();
    if (motionOK()) autoTag();
    initReveal();
    initScroll();
    initPageProgress();
    // v2: ripple + gold shine sweep retired (calm motion); functions kept for API compatibility.
    initShimmer();
    initTickers();
    initLightbox();
    initToggleButtons();
    syncToggleButtons();

    // Keep the motion class in sync with the a11y toggle and the OS setting.
    if ('MutationObserver' in window) {
      var last = root.classList.contains('kfs-a11y-noanim');
      new MutationObserver(function () {
        var now = root.classList.contains('kfs-a11y-noanim');
        if (now !== last) { last = now; syncMotionClass(); }
      }).observe(root, { attributes: true, attributeFilter: ['class'] });
    }
    if (mq) {
      var h = function () { syncMotionClass(); };
      if (mq.addEventListener) mq.addEventListener('change', h); else if (mq.addListener) mq.addListener(h);
    }
    // Swipers/carousels are created by other scripts on DOMContentLoaded — honour the setting after them.
    setTimeout(function () { if (!motionOK()) toggleAutoplay(false); }, 600);

    window.addEventListener('offline', function () { toast('انقطع الاتصال بالإنترنت — يمكنك متابعة تصفح الصفحات المحفوظة.', 'warning', 6000); });
    window.addEventListener('online', function () { toast('تمت استعادة الاتصال بالإنترنت.', 'success', 3500); });
  }

  // Expose early so the <head> safety timer knows the motion engine loaded.
  window.KFSMotion = { toast: toast, ok: motionOK, refresh: function () { initReveal(); }, lightbox: openLightbox };
  root.classList.toggle('kfs-motion', motionOK());

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
