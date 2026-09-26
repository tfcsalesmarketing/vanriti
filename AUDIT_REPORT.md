# VANRITI — Complete Audit Report

Status: **Pre-implementation baseline** · Generated 2026-09-26 · Scope: storefront + admin + models + migrations + webhook/payment flows + views/JS/CSS + deployment config.

6 parallel deep audits + live HTTP probes + direct verification of criticals. Baseline suite: 969 tests, 0 failures, 5 benign risky.

## Executive Summary

- **Deployment is the #1 issue (Critical, confirmed live):** the document root serves the whole repo — `.env`, `.env.production`, `.git/` (full history), `composer.json`, `artisan`, `storage/logs/laravel.log` (4.1 MB) and two unauthenticated executable smoke scripts. `APP_DEBUG=true`. Every credential is exposed; the leaked `APP_KEY` decrypts all `password`-type settings, DB sessions, and signed URLs.
- **Stored XSS:** the only HTML sanitizer `clean_html()` is escapable via its "unwrap unknown tag" path. Admin-authored CMS content reaches blog/product/page views.
- **Financial logic gaps:** coupon limits race (TOCTOU), order-number `TEMP` insert collides under concurrency, ShipMojo webhook can downgrade a *delivered* order to *cancelled*, `RefundService::complete()` can pay out a rejected/pending refund, admin can mark payments paid with no reconciliation, non-super-admin can grant the super-admin role.
- **Auth gaps:** phone-path reset token never expires, reset logs in suspended accounts, reset token enters GTM/Meta pageview data, `/auth/validate` is a credential oracle, OTP send is IP-only throttled, Dadi LLM is unauthenticated, guest coupon limits bypassed.
- **Front-end function bugs:** cart quantity changes not persisted, `cardRemoveItem()` references undefined vars, checkout address prefill broken (double-encoded JSON), drawer totals ignore coupon, admin "Saving…" spinner sticks.
- **UI/UX:** broken image everywhere (no `placeholder.png`), no focus/disabled states, content invisible without JS (`opacity:0` + no IO fallback), `time()` cache-buster defeats HTTP caching.

---

## CRITICAL

| # | Locale | Issue |
|---|---|---|
| **S1** | project-root `.htaccess:1-7` | Document root = project root. `.env`, `.env.production`, `.git/`, `.gitignore`, `composer.json`, `artisan` all served public (verified live: HTTP 200 & byte-identical). |
| **S2** | `storage/app/final_smoke.php`, `final_smoke2.php` | Unauthenticated scripts that bootstrap the app, INSERT orders, render a PDF. Reachable & executed live. |
| **S3** | `storage/logs/laravel.log` + `.env:4` | 4.1 MB debug log public; `APP_DEBUG=true`, `LOG_LEVEL=debug`; logs customer emails/phones, Meta error bodies, Razorpay signatures. |
| **S4** | — | Rotate all credentials after S1-S3; APP_KEY (same in `.env`/`.env.production`) decrypts all `password`-type settings + DB sessions + signed URLs. |
| **OG1** | `app/Support/helpers.php:168-186,241` | `clean_html()` "unwrap" promotes children before sanitizing -> `<foo><script>`, `<svg onload>`, `onclick`, `javascript:` survive. Stored XSS in CMS content (blog `:97`, product, page `:32`). |
| **OG2** | `app/Support/helpers.php:111` + 5 views | Default `images/placeholder.png` does not exist -> broken images on cart, checkout, wishlist, product grid. Favicon is 663 KB WebP; `favicon.ico` is 0 bytes. |

## HIGH

### Auth / Security

| Locale | Issue |
|---|---|
| `Auth/AuthController.php:407-416` | Phone-path reset uses `getRepository()->exists()` -> token never expires (email path via broker does). |
| `Auth/AuthController.php:418,439` | Reset logs in inactive/suspended accounts (no `isActive()` check). |
| `layouts/app.blade.php:79-102` | Reset token rides in URL query string on a page loading GTM + Meta Pixel -> token in analytics/history/logs. |
| `Auth/AuthController.php:245-252,268` | `/auth/validate` different error key for existing vs missing account (oracle); register uses `unique:` rule (enumeration). |
| `OtpController.php:47-55,118-120,170-172` | OTP send/verify return "User does not exist" (enumeration); throttles IP-only, target arbitrary -> unlimited WhatsApp spam. |
| `routes/web.php:51` | `POST /dadi/message` unauthenticated, only `throttle:20,1` -> unmetered paid-LLM spend. |
| `Admin/RoleController.php:44-63` | `update()` has no `is_system` guard -> `manage-roles` holder can rewrite super-admin role. |
| `Admin/AdminUserController.php:28-46` | `manage-admins` holder can attach super-admin to own account. |
| `Admin/OrderController.php:264-288`, `routes/admin.php:62` | `manage-orders` can flip `payment_status`->paid, no reconciliation/amount check/audit. |
| `SecurityHeaders.php:35` | CSP `script-src` has `'unsafe-inline'` and `'unsafe-eval'`. |
| `bootstrap/app.php:47-52` | `trustHosts(subdomains:true)` empty list -> host validation off. |
| `bootstrap/app.php:36` | `checkout/verify` CSRF-exempt (posts `_token` already). |
| `helpers.php:24-33` | `secret_setting()` fails open: returns raw ciphertext on decrypt failure. |
| `routes/admin.php:157-160` | `shipmojo/ping|warehouses|rates` require only `admin.auth`. |
| `Admin/ProductController.php` export() | CSV formula injection. |
| `Admin/ProductController.php:350` | Import: unchecked `ValueError`, unvalidated MRP/stock, `updateOrCreate` overwrites by SKU, no dry-run/audit. |

### Business-logic bugs

| Locale | Issue |
|---|---|
| `Services/OrderService.php:183,209` | Order number inserts `'TEMP'` first; unique column -> concurrent checkouts collide. |
| `Services/CouponService.php:34,49-53,77`, `models/Coupon.php:79` | Limits checked unlocked outside order transaction (TOCTOU); empty guest branch; product/category restrictions never enforced in discount math. |
| `Services/ShipMojoService.php:564-574,648-650,864-883` | No state machine: late `rto`/`failed`/`returning` webhook downgrades delivered order to cancelled, fires stock release/notifications. |
| `Services/RefundService.php:101-112` | `complete()` only checks not-completed -> rejected/pending refund can still be paid out. |
| `Services/ShipMojoService.php:203`, `jobs/PushOrderToShipMojo.php` | `firstOrNew([])` with no unique key -> retries create duplicate Shipments / carrier orders. |
| `ShopController.php:45` | Inactive/draft categories render public listings. |
| `AccountController.php:144-181` | Reviews on unpaid/undelivered orders, hardcoded `is_verified_purchase=true`. |
| `AccountController.php:70-141` | `return_window_days` never enforced server-side; duplicate returns allowed. |
| `NewsletterController.php:18-35` | No normalize; empty `catch (QueryException)` reports "subscribed" on failure; no unsubscribe. |
| `CartService.php:115-128,250-255` | Foreign cartItem id -> uncaught TypeError/500. |
| `CartService.php:164-236` | Logged-in cart stranded on logout; `mergeUserCartIntoSession()` dead + inverted. |

## MEDIUM (representative set)

- Money in float, no `decimal:2` casts (`Order`, `Coupon`, `Payment`, `Refund`, `Shipment`, `CartItem`).
- `total_sold` never written -> "Popular" ordering frozen; review accessors shadow columns and N+1-load every review.
- ENUM statuses; missing order_status/payment_status indexes (full scans).
- `morphs('owner')` no FK (orphans); nullable columns in unique keys don't collide in MySQL.
- `Category::descendants()` recursive, N+1, cycle-unsafe.
- `getAvailableStock()` sums inactive variants; `getPrimaryImage()` ignores eager loads.
- Queue PII: notifications serialize whole models into jobs table; Meta CAPI token in request URL; ShipMojo logs consignee PII.
- Views: JSON without HEX flags at `layouts/app:204`; double-encoded `data-address`; innerHTML sinks; `data-auto-qty` typo blocks qty persistence; `cardRemoveItem` undefined vars; coupon ignored in drawer; addresses spinner sticks; `time()` cache-buster; no-JS invisible content; `blog-content` class absent; blog paginator skip + blank-state; checkout/failed retry creates new order; wishlist remove = toggle; bare `fbq()` can throw; track N+1; silent 422s.
- Admin JS: global `novalidate`; `textarea[required]` skipped; modal Enter from anywhere; unbounded MutationObserver; `DataTransfer` throws in Safari; no dialog a11y.

## LOW (brief)

Sequential order numbers; `welcome.blade.php` dead `@vite` + missing `public/build`; `InfoController`/`PageController::returnPolicy()`/`checkout/guest.blade.php` unreachable; `F-45` brands migration only creates categories; order_status ENUM drift; two address UIs; `bi-*`/`ri-*` drift; 363 inline styles; missing `405.blade.php`; `robots.txt` hardcoded domain; `admin/inventory` raw `statusBadge`; `Admin/OrderController::update()` no-op stub; Dadi fully built but unlinked.

## VERIFIED CLEAN

No `dd()`/`dump()`/`var_dump()`; zero `$request->all()`->model writes; all SQL parameter-bound; no IDOR on user-scoped resources; session regenerate on all logins; OTPs hashed/capped/TTL'd; uploads validated; `composer audit` clean; error pages leak nothing; admin remember-me/reset revoke old tokens.

---

## Implementation Plan (Critical + High)

- **Phase A — Deployment firewall:** vhost DocumentRoot -> `public/`; hardening `.htaccess` deny rules; delete smoke scripts; `APP_DEBUG=false`, `LOG_LEVEL=warning`; rotate credentials + `key:generate` + re-encrypt settings.
- **Phase B — XSS/response hardening:** `clean_html()` unwrap fix; drop CSP `unsafe-eval` (+`report-uri`); JSON HEX flags; `secret_setting()` fail-closed.
- **Phase C — Auth/security:** reset expiry + isActive + token-in-body + analytics suppression; `/auth/validate` uniform response; OTP generic responses + phone-keyed rate limits; Dadi auth + spend cap; super-admin role enforcement; `manage-payments` permission; `trustHosts` allow-list; remove `checkout/verify` CSRF exempt.
- **Phase D — Business logic:** order-number pre-insert; coupon atomic locking + guest limits + restriction enforcement; RefundService state guard; ShipMojo forward-only state machine + idempotency; ShopController active filter; server-side review/return gates; newsletter normalize + unsubscribe; cart item scoping + logout release.
- **Phase E — Verification:** new/updated tests; full suite; lint; curl checks.

**Decisions:** Dadi ships (auth + gating added). Guest checkout stays (guest coupon limits fixed properly).