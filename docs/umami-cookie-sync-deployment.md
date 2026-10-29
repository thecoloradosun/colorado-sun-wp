# Umami Cookie Sync — Deployment Guide

## Overview

Syncs Colorado Sun membership cookie data into Umami analytics as visitor properties, plus fires scroll-depth and time-on-page custom events.

**Script:** `assets/js/umami-cookie-sync.min.js` (1.5 KB minified)
**Source:** `src/js/umami/umami-cookie-sync.js`

## Dependency

This script depends on `window.umami`, provided by the **sun-analytics** tracker. The sun-analytics script MUST load first — this script silently retries up to 5× (500ms intervals) waiting for it, then gives up.

## Cookie Domain Restriction

The `coloradosun_member_data` cookie is set on `.coloradosun.com`. This script **only** works when loaded on `coloradosun.com` (not on `newsroom.cosun.xyz` or other staging/review domains). On unsupported domains `getCookie()` will return `null` and `doIdentify()` exits without calling Umami.

## Installation

### Via Perfmatters (recommended)

1. In the WordPress admin, go to **Perfmatters → Assets → Footer**.
2. Add a new **Custom JavaScript** asset.
3. Set **Method** to **URL** (NOT inline) and enter:
   ```
   /wp-content/plugins/newsroomos/assets/js/umami-cookie-sync.min.js
   ```
4. Enable **Defer**.
5. Set the **Location** to **Footer**.
6. Ensure this script's position in the list is **after** the sun-analytics tracker asset.

> The script is an IIFE — it runs on load and waits for `window.umami` to become available. Defer is safe. Loading by URL is cleaner and avoids dumping the full script text into the Perfmatters editor.

### Via theme template

Insert before the closing `</body>` tag, after the sun-analytics tracker:

```html
<script defer src="/wp-content/plugins/newsroomos/assets/js/umami-cookie-sync.min.js"></script>
```

## What It Does

### 1. Cookie → Identify (`window.umami.identify()`)

Reads the `coloradosun_member_data` cookie (URL-encoded JSON), parses it, and sends:

| Property        | Type    | Source                                     |
|-----------------|---------|--------------------------------------------|
| `member`        | boolean | `data.member` (truthy or `'true'` string)  |
| `tier`          | string  | `data.tier` (defaults to `'none'`)         |
| `subscriptions` | number  | Count of subs where `s.subscribed === true` |
| `source`        | string  | Always `'coloradosun.com'`                  |

### 2. Scroll Depth Events

Fires `umami.track('scroll', {depth: N})` at 25%, 50%, 75%, and 100% scroll progress. Each milestone fires **once per page view**. Handles the edge case where a page has zero scrollable content (fires 100% immediately).

Detected via `window.addEventListener('scroll', ...)` — no polling interval.

### 3. Time-on-Page Event

Fires `umami.track('time-on-page', {seconds: N})` **only** on:

- `window.addEventListener('pagehide', ...)`
- `window.addEventListener('beforeunload', ...)`

No `setInterval` is used. This avoids inflating time-on-page with zero-value or mid-page events. The event fires once, at the end of the visit, with total elapsed seconds.

## Umami Configuration

- **Site ID:** `1aca18ea-f836-48df-a6f6-fbee86637815`
- **Custom events** (`scroll`, `time-on-page`) use **event_type=2** — they do **not** inflate pageview counts.
- The `identify()` call associates visitor properties with the current session. These appear in Umami's visitor sidebar and can be used for filtering segments.

## Safety

All calls to `window.umami` are guarded:

```js
// Every fireTrack() call checks before invoking
if (typeof window.umami === 'undefined' || typeof window.umami.track !== 'function') return;
```

All interactions are wrapped in `try/catch` — the script never throws, never blocks page rendering, and never creates orphaned intervals.

## Files

| Path                               | Purpose                  |
|------------------------------------|--------------------------|
| `src/js/umami/umami-cookie-sync.js` | Unminified source        |
| `assets/js/umami-cookie-sync.min.js`| Minified production file |

## Changelog

- **Initial release:** Self-hosted Umami cookie sync with identify, scroll-depth, time-on-page.