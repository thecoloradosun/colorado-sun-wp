/**
 * Colorado Sun → Umami cookie sync script
 * ──────────────────────────────────────────────────────────────────────────
 * Reads the `coloradosun_member_data` cookie set by the membership embed
 * component, then calls window.umami.identify() with safe, non-PII fields
 * (member, tier, subscription count) so the Umami dashboard can segment
 * by membership status.
 *
 * Also fires scroll-depth events (25/50/75/100%) and a single time-on-page
 * event on pagehide/beforeunload for every page view.
 *
 * Dependency: sun-analytics tracker must load first (provides window.umami).
 *
 * Load via Perfmatters footer custom script with defer, AFTER sun-analytics.
 * Only works on coloradosun.com (cookie domain: .coloradosun.com).
 * ──────────────────────────────────────────────────────────────────────────
 * Umami site ID: 1aca18ea-f836-48df-a6f6-fbee86637815
 * Custom events use event_type=2, don't inflate pageviews.
 */

(function () {
  'use strict';

  /* ── 1. Wait for window.umami (retry 500ms × 5, then silently give up) ── */
  var attempts = 0;
  var MAX_ATTEMPTS = 5;
  var RETRY_DELAY = 500;

  /* ── 2. Read & parse coloradosun_member_data cookie ──────────────────── */
  function getCookie(name) {
    var encoded = encodeURIComponent(name).replace(/[-.+*]/g, '\\$&');
    var match = document.cookie.match(new RegExp('(?:^|; )' + encoded + '=([^;]*)'));
    return match ? match[1] : null;
  }

  function parseCookieData() {
    var raw = getCookie('coloradosun_member_data');
    if (!raw) return null;
    try {
      return JSON.parse(decodeURIComponent(raw));
    } catch (e) {
      return null;
    }
  }

  /* ── 3. Identify call ────────────────────────────────────────────────── */
  function doIdentify() {
    var data = parseCookieData();
    if (!data) return;

    /* Count active subscriptions (s.subscribed === true) */
    var subs = 0;
    if (typeof data.subscriptions === 'object' && data.subscriptions !== null) {
      var keys = Object.keys(data.subscriptions);
      for (var i = 0; i < keys.length; i++) {
        var s = data.subscriptions[keys[i]];
        if (s && s.subscribed === true) subs++;
      }
    }

    try {
      window.umami.identify({
        member: data.member === true || data.member === 'true',
        tier: typeof data.tier === 'string' ? data.tier : 'none',
        subscriptions: subs,
        source: 'coloradosun.com'
      });
    } catch (e) {
      /* silent */
    }
  }

  /* ── 4. Scroll-depth events (25/50/75/100%, once per pageview) ────────── */
  var SCROLL_MILESTONES = [25, 50, 75, 100];
  var scrollFired = {};
  var scrollListenerAttached = false;

  function getScrollTop() {
    return window.pageYOffset !== undefined
      ? window.pageYOffset
      : (document.documentElement.scrollTop || document.body.scrollTop || 0);
  }

  function getScrollableHeight() {
    return document.documentElement.scrollHeight - document.documentElement.clientHeight;
  }

  function checkScroll() {
    var scrollTop = getScrollTop();
    var scrollable = getScrollableHeight();

    /* Edge case: no scrollable content — fire 100% once */
    if (scrollable <= 0) {
      if (!scrollFired[100]) {
        scrollFired[100] = true;
        fireTrack('scroll', { depth: 100 });
      }
      return;
    }

    var pct = Math.min(100, Math.round((scrollTop / scrollable) * 100));
    for (var i = 0; i < SCROLL_MILESTONES.length; i++) {
      var m = SCROLL_MILESTONES[i];
      if (pct >= m && !scrollFired[m]) {
        scrollFired[m] = true;
        fireTrack('scroll', { depth: m });
      }
    }
  }

  /* ── 5. Time-on-page (pagehide/beforeunload ONLY — no setInterval) ──── */
  var pageStart = Date.now();

  function reportTimeOnPage() {
    var seconds = Math.floor((Date.now() - pageStart) / 1000);
    fireTrack('time-on-page', { seconds: seconds });
  }

  /* ── 6. Safe tracking wrapper — checks umami exists, catches errors ──── */
  function fireTrack(event, data) {
    if (typeof window.umami === 'undefined' || typeof window.umami.track !== 'function') return;
    try {
      window.umami.track(event, data);
    } catch (e) {
      /* silent — never throw from analytics */
    }
  }

  /* ── 7. Init — called immediately, retries up to MAX_ATTEMPTS ──────────── */
  function tryInit() {
    if (typeof window.umami !== 'undefined') {
      doIdentify();
      checkScroll();

      /* Attach scroll listener once (no orphaned intervals) */
      if (!scrollListenerAttached) {
        scrollListenerAttached = true;
        window.addEventListener('scroll', checkScroll);
      }

      /* Time-on-page: ONLY on pagehide/beforeunload — no setInterval */
      window.addEventListener('pagehide', reportTimeOnPage);
      window.addEventListener('beforeunload', reportTimeOnPage);

      return;
    }

    attempts++;
    if (attempts < MAX_ATTEMPTS) {
      setTimeout(tryInit, RETRY_DELAY);
    }
    /* else: silently give up — umami never loaded */
  }

  tryInit();
})();