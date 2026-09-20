# Performance Baseline — Server-rendered Blade

Captured before introducing Vue/Livewire/Inertia, so the same measurements
can be re-run afterward for a like-for-like comparison. Re-run against the
same routes, same seeded data volume, same container (`laravel-app` via
`php artisan serve`, MySQL via `laravel-mysql`), and update this file with a
new dated section rather than overwriting this one.

**Captured:** 2026-09-20
**Stack:** Blade + Tailwind, no client-side framework, `php artisan serve` (single-threaded dev server)
**Data volume:** ~40 articles, 7 categories, 7 sources

## 1. Server response time (single request, TTFB + total)

Command (per route):

```sh
for i in 1 2 3 4 5; do
  curl -s -o /dev/null -w "  ttfb=%{time_starttransfer}s total=%{time_total}s\n" "http://localhost:8000<route>"
done
```

| Route | TTFB (avg) | Total (avg) |
|---|---|---|
| `/` | ~24ms | ~24ms |
| `/articles/3` | ~19ms | ~20ms |
| `/login` | ~18ms | ~19ms |
| `/register` | ~18ms | ~19ms |

## 2. Throughput under concurrency

Command:

```sh
ab -n 200 -c 10 http://localhost:8000/
ab -n 200 -c 10 http://localhost:8000/articles/3
```

| Route | Req/sec | Mean time/request | Failed |
|---|---|---|---|
| `/` | 34.32/sec | 291ms | 0 |
| `/articles/3` | 55.97/sec | 179ms | 0 |

Note: `php artisan serve` is single-threaded, so this measures the dev
server's serialization, not real concurrency headroom — still valid as a
*relative* baseline as long as the same server is used for the re-test.

## 3. Database queries per page

Command (`php artisan tinker`):

```php
DB::enableQueryLog();
app(App\Services\Contracts\ArticleServiceInterface::class)->listLatest();
count(DB::getQueryLog()); // + array_sum(array_column(..., 'time'))
```

| Page | Queries | DB time |
|---|---|---|
| Homepage (`listLatest`) | 4 | 4.89ms |
| Article detail (`loadDetails`) | 3 | 1.17ms |

## 4. Client-side load metrics (Navigation Timing API)

Captured via a headless-Chromium (Playwright) script hitting each route with
`waitUntil: 'load'` and reading `performance.getEntriesByType('navigation'/'resource')`.

| Route | TTFB | DOMContentLoaded | Load event | Resources | Total transfer |
|---|---|---|---|---|---|
| `/` | 25.7ms | 55.2ms | 57.6ms | 2 | 81.8 KB |
| `/articles/3` | 18.4ms | 32.3ms | 33.6ms | 2 | 46.1 KB |
| `/login` | 17.6ms | 31.5ms | 32.8ms | 2 | 46.3 KB |
| `/register` | 33.0ms | 47.4ms | 48.7ms | 2 | 47.3 KB |

"2 resources" = the Tailwind CSS bundle + the (empty) JS entry — there is
currently no client-side framework runtime, no hydration, no API round-trip
after the initial HTML. This is the number to watch: any Vue/Livewire/Inertia
setup will add JS payload weight and (for Livewire/Inertia) extra
request(s) per interaction that don't exist today.

## What to re-run later, unchanged

1. `for i in 1 2 3 4 5; do curl -s -o /dev/null -w "..." "http://localhost:8000<route>"; done`
2. `ab -n 200 -c 10 http://localhost:8000/` and `.../articles/3`
3. The tinker query-log snippet above (or the equivalent code path in the new stack)
4. The Playwright navigation-timing script (`perf.js` pattern above) against the same routes

Compare req/sec, TTFB, DOMContentLoaded/load timings, resource count, and
transfer bytes directly against the numbers in this section.

---

# Comparison — Livewire-powered homepage (live filter/search)

**Captured:** 2026-09-20 (same day, same container, same 40 articles/7
categories/7 sources — only the homepage's implementation changed)
**What changed:** `/` went from a plain controller passing `$articles` to a
static Blade loop, to a thin controller shell embedding
`<livewire:article-list />` — a Livewire component that owns its own
filtering (category/source/search/date range, all bound to the query
string) and re-renders itself over AJAX with no full page reload. Article
detail, login, register, dashboard are unchanged (still plain Blade), so
they aren't re-measured here.

## 1. Server response time — `/` (single request, 5 samples)

| | TTFB (avg) | Total (avg) |
|---|---|---|
| **Before** (static Blade) | ~24ms | ~24ms |
| **After** (Livewire shell) | ~30ms | ~30ms |

Slightly slower initial response — expected, since the request now also
mounts a Livewire component (snapshot serialization, `wire:id` generation)
on top of the same article query.

## 2. Throughput under concurrency — `/`

| | Req/sec | Mean time/request | Failed |
|---|---|---|---|
| **Before** | 34.32/sec | 291ms | 0 |
| **After** | 29.18/sec | 343ms | 0 |

~15% fewer req/sec on the same single-threaded dev server, consistent with
the added Livewire mount/serialization cost per request.

## 3. Database queries — homepage

| | Queries | DB time |
|---|---|---|
| **Before** (`listLatest()` alone) | 4 | 4.89ms |
| **After** (`listLatest()` alone, same call) | 4 | 4.17ms |
| **After** — full widget (`listLatest` + `availableCategories` + `availableSources`, the two new filter-dropdown queries) | 6 | 2.8ms |
| **After** — full HTTP request (adds the session-lookup query Livewire's mount needs) | 6 | 2.55ms |

The article query itself is unchanged; the +2 queries are the new
category/source filter-option lookups, each a simple indexed `EXISTS`
query — not the pagination count query multiplying (that's still 1).

## 4. Client-side load metrics — `/`

| | TTFB | DOMContentLoaded | Load event | Resources | Total transfer |
|---|---|---|---|---|---|
| **Before** | 25.7ms | 55.2ms | 57.6ms | 2 | 81.8 KB |
| **After** | 32.2ms | 100.4ms | 100.5ms | 3 | 719.0 KB |

This is the number that actually moved. The 3rd resource is Livewire's own
JS runtime (`livewire.js`), and on this dev server (`php artisan serve`,
no gzip) it transferred **609 KB uncompressed** — by far the largest single
asset on the page, dwarfing the 41.8 KB Tailwind CSS bundle. In production
behind a compressing web server this would shrink substantially (Livewire's
JS gzips to roughly 15-20% of that), but it's still a real, permanent
baseline cost that didn't exist before: every page using this layout now
pays for it, not just the homepage.

## 5. Cost of one filter interaction (did not exist before)

There was no equivalent measurement in the "before" baseline because
changing a filter used to mean a full page navigation (covered by section
1/2 above). Now:

| | Value |
|---|---|
| Network requests triggered | 1 (`POST /livewire-<hash>/update`) |
| Request payload | 631 bytes |
| Response payload | 63.3 KB |
| Wall-clock (select → DOM updated) | ~1.0s (includes no artificial debounce for `<select>`; search input adds its own 400ms debounce on top) |

The 63 KB response is the re-rendered HTML for the whole results grid
(12 article cards) sent back on every filter change — expected for a
server-rendered-fragment approach like Livewire, but worth watching if the
per-page count or card markup grows.

## Verdict

- **Initial page load got heavier** (~15% slower under load, +637 KB
  transferred, mostly Livewire's own runtime) — this is a fixed cost paid
  once per page load regardless of whether the user ever touches a filter.
- **Interactions that used to require a full navigation now cost ~63 KB
  and no full reload** — verified live (zero `document`-type network
  requests fired during category/search/date-range filtering in a real
  browser), which is the actual feature being traded for that fixed cost.
- **The article query itself did not get slower or more numerous** — the
  repository/service layer changes (adding `ArticleFilters`) added
  filtering capability without adding query overhead to the unfiltered
  case.
