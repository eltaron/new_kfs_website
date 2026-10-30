/*!
 * KFS public site — global UI behaviour (header, mobile menu, search overlay,
 * date/time, scroll progress, back-to-top, reveal on scroll).
 * Accessibility toolkit lives in kfs-a11y.js, voice commands in kfs-voice.js.
 */
document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  var body = document.body;
  var root = document.documentElement;

  /* ---------------------------------------------------------- mobile menu */
  var toggler = document.getElementById("mobileMenuToggler");
  var panel = document.getElementById("mobileMenuPanel");
  var overlay = document.getElementById("mobileMenuOverlay");
  var closeBtn = document.getElementById("closeMobileMenu");
  var lastFocus = null;

  function menuFocusables() {
    return panel ? Array.prototype.slice.call(panel.querySelectorAll("a[href],button:not([disabled])")).filter(function (el) {
      return el.offsetParent !== null;
    }) : [];
  }

  function openMenu() {
    if (!panel || !overlay) return;
    lastFocus = document.activeElement;
    panel.classList.add("show");
    overlay.classList.add("show");
    panel.setAttribute("aria-hidden", "false");
    panel.removeAttribute("inert");
    body.classList.add("body-no-scroll");
    if (toggler) toggler.setAttribute("aria-expanded", "true");
    if (closeBtn) closeBtn.focus();
  }

  function closeMenu() {
    if (!panel || !overlay || !panel.classList.contains("show")) return;
    panel.classList.remove("show");
    overlay.classList.remove("show");
    panel.setAttribute("aria-hidden", "true");
    panel.setAttribute("inert", "");
    body.classList.remove("body-no-scroll");
    if (toggler) toggler.setAttribute("aria-expanded", "false");
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  if (panel) panel.setAttribute("inert", "");
  if (toggler) toggler.addEventListener("click", openMenu);
  if (closeBtn) closeBtn.addEventListener("click", closeMenu);
  if (overlay) overlay.addEventListener("click", closeMenu);
  if (panel) {
    panel.addEventListener("keydown", function (e) {
      if (e.key === "Escape") { closeMenu(); return; }
      if (e.key !== "Tab") return;
      var f = menuFocusables();
      if (!f.length) return;
      if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    });
  }

  document.querySelectorAll(".mobile-dropdown-trigger").forEach(function (trigger) {
    trigger.addEventListener("click", function (e) {
      e.preventDefault();
      var submenu = this.nextElementSibling;
      if (submenu && submenu.classList.contains("mobile-submenu")) {
        var open = submenu.classList.toggle("open");
        this.classList.toggle("open", open);
        this.setAttribute("aria-expanded", open ? "true" : "false");
      }
    });
  });

  /* ---------------------------------------------------------- desktop nested dropdown */
  document.querySelectorAll(".dropdown-submenu > a").forEach(function (el) {
    el.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var submenu = this.nextElementSibling;
      if (submenu) {
        var open = submenu.classList.toggle("show");
        this.setAttribute("aria-expanded", open ? "true" : "false");
      }
    });
  });

  /* ---------------------------------------------------------- search overlay */
  var searchTrigger = document.getElementById("searchTrigger");
  var searchOverlay = document.getElementById("searchOverlay");
  var closeSearch = document.getElementById("closeSearch");
  var searchInput = document.getElementById("searchInput");
  var searchReturn = null;

  function openSearch() {
    if (!searchOverlay) return;
    searchReturn = document.activeElement;
    searchOverlay.classList.add("active");
    searchOverlay.setAttribute("aria-hidden", "false");
    body.classList.add("body-no-scroll");
    setTimeout(function () { if (searchInput) searchInput.focus(); }, 150);
  }

  function hideSearch() {
    if (!searchOverlay || !searchOverlay.classList.contains("active")) return;
    searchOverlay.classList.remove("active");
    searchOverlay.setAttribute("aria-hidden", "true");
    body.classList.remove("body-no-scroll");
    if (searchReturn && searchReturn.focus) searchReturn.focus();
  }

  document.querySelectorAll("#searchTrigger, [data-search-open]").forEach(function (b) {
    b.addEventListener("click", function (e) { e.preventDefault(); openSearch(); });
  });
  if (closeSearch) closeSearch.addEventListener("click", hideSearch);
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") { hideSearch(); closeMenu(); }
  });

  /* ---------------------------------------------------------- header / progress / back-to-top */
  var header = document.querySelector("header.header-main");
  var progress = document.getElementById("kfsScrollProgress");
  var toTop = document.getElementById("kfsBackToTop");
  var bottomNav = document.getElementById("mobileBottomNav");
  var lastY = window.scrollY;
  var ticking = false;

  function onScroll() {
    var y = window.scrollY;
    var max = root.scrollHeight - root.clientHeight;
    if (header) header.classList.toggle("header-scrolled", y > 50);
    if (progress) progress.style.transform = "scaleX(" + (max > 0 ? Math.min(1, y / max) : 0) + ")";
    if (toTop) toTop.classList.toggle("is-visible", y > 600);
    if (bottomNav && window.innerWidth < 992) {
      bottomNav.classList.toggle("bottom-nav-hidden", y > lastY && y > 100);
    }
    lastY = y;
    ticking = false;
  }

  window.addEventListener("scroll", function () {
    if (!ticking) { ticking = true; window.requestAnimationFrame(onScroll); }
  }, { passive: true });
  onScroll();

  if (toTop) {
    toTop.addEventListener("click", function () {
      var reduce = root.classList.contains("kfs-a11y-noanim") ||
        (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
      window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" });
      var skipTarget = document.getElementById("main-content");
      if (skipTarget) skipTarget.focus({ preventScroll: true });
    });
  }

  /* ---------------------------------------------------------- reveal on scroll */
  // Handled by kfs-motion.js (data-reveal / data-stagger / data-draw / data-count),
  // which also honours «إيقاف الحركة» and prefers-reduced-motion.

  /* ---------------------------------------------------------- date & time (top bar) */
  var dateBox = document.getElementById("date-time-container");
  function updateDateTime() {
    if (!dateBox) return;
    var now = new Date();
    try {
      var greg = new Intl.DateTimeFormat("ar-EG", { weekday: "long", year: "numeric", month: "long", day: "numeric" }).format(now);
      var hijri = new Intl.DateTimeFormat("ar-SA-u-ca-islamic-civil", { year: "numeric", month: "long", day: "numeric" }).format(now);
      var time = new Intl.DateTimeFormat("ar-EG", { hour: "2-digit", minute: "2-digit", hour12: true }).format(now);
      dateBox.textContent = greg + " | " + hijri + " | " + time;
    } catch (e) { /* old browsers */ }
  }
  updateDateTime();
  setInterval(updateDateTime, 30000);

  var yearSpan = document.getElementById("copyright-year") || document.getElementById("footer-year");
  if (yearSpan) yearSpan.textContent = new Date().getFullYear();
});
