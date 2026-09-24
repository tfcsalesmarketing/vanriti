# VANRITI - E-Commerce Store

VANRITI is a production-ready e-commerce web application for a beauty / holistic wellness brand (natural skincare, herbal teas and wellness essentials). It ships with a fully functional storefront and an admin panel for managing products, orders, customers, content and settings.

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **MySQL** 5.7+ / 8.0
- **Bootstrap 5** (static assets, **no Node.js / build step required**)
- **Razorpay** integration (optional; COD always available)
- Session / Cache / Queue all use the **database** driver by default (shared-hosting friendly, no Redis needed)

---

## Features

- Storefront: home, shop, category browsing, product detail pages (with gallery lightbox, variant switcher, Buy Now), cart, wishlist, checkout, order tracking (with status timeline), blog, FAQs, contact, newsletter, custom pages, and dedicated info pages (`/about`, `/privacy-policy`, `/terms`, `/shipping`).
- Mobile-first storefront UX: offcanvas navigation drawer, sticky buy/add bar on product pages, AJAX add-to-cart with toast + cart badge, and order-status progress steppers.
- Admin panel: dashboards, products (incl. variants, images, attributes), categories, brands, orders, customers, coupons, banners, blogs, pages, FAQs, reviews, settings.
- Payments: **Cash on Delivery** always available; optional **Razorpay** (create order + verify signature).
- Reviews with verified-purchase badges, returns handling, discount coupons.

---

## Local Setup (XAMPP on Windows)

Prerequisites: [XAMPP](https://www.apachefriends.org/) with **Apache**, **MySQL**, and **PHP 8.2+**.

1. Clone / copy the project into `C:\xampp\htdocs\vanriti`.

2. Install PHP dependencies with composer:

   ```bash
   cd C:\xampp\htdocs\vanriti
   composer install
   ```

3. Create the environment file and generate an app key:

   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. Edit `.env` and set your MySQL credentials:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=vanriti
   DB_USERNAME=root
   DB_PASSWORD=
   APP_URL=http://localhost/vanriti
   ```

   > Keep `APP_URL` set to the exact base URL the site is reachable at. Route helpers (`route()`, `url()`) use it, which keeps the sitemap and canonical links correct.

5. Create the database `vanriti` in phpMyAdmin / MySQL, then run migrations **and** seeders:

   ```bash
   php artisan migrate --seed
   ```

   - The `RolesAndPermissionsSeeder` creates the first **admin** user. It reads `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env` (see `.env.example`). Set `ADMIN_PASSWORD` to a strong value **before** seeding.
   - Sample products, categories, banners, coupons, FAQs, blog posts and settings are seeded.

6. Link the storage directory (product images uploaded via admin):

   ```bash
   php artisan storage:link
   ```

7. Serve the app. With the default XAMPP setup the site is reachable **without** `/public` — the root `.htaccess` rewrites into the `public/` folder and the root `index.php` bootstraps Laravel for `/` (make sure `mod_rewrite` is enabled):

   ```
   http://localhost/vanriti
   ```

   > The old `http://localhost/vanriti/public` URL keeps working as a fallback. Alternatively use `php artisan serve`.

---

## Roles & Permissions

The admin panel uses Laravel authorization. Permissions are seeded and assigned to roles (`admin`, `manager`, `staff`, etc.).

| Module | Example permission names |
| ------ | ------------------------ |
| Dashboard | `view-dashboard` |
| Products | `view-products`, `create-products`, `edit-products`, `delete-products` |
| Categories | `view-categories`, `create-categories`, `edit-categories`, `delete-categories` |
| Brands | `view-brands`, `create-brands`, `edit-brands`, `delete-brands` |
| Orders | `view-orders`, `edit-orders`, `cancel-orders`, `delete-orders` |
| Customers | `view-customers`, `edit-customers`, `delete-customers` |
| Coupons | `view-coupons`, `create-coupons`, `edit-coupons`, `delete-coupons` |
| Banners | `view-banners`, `create-banners`, `edit-banners`, `delete-banners` |
| Blog | `view-blog`, `create-blog`, `edit-blog`, `delete-blog` |
| Pages | `view-pages`, `create-pages`, `edit-pages`, `delete-pages` |
| FAQs | `view-faqs`, `create-faqs`, `edit-faqs`, `delete-faqs` |
| Reviews | `view-reviews`, `edit-reviews`, `delete-reviews` |
| Settings | `view-settings`, `edit-settings` |
| Roles & Admins | `view-roles`, `create-roles`, `edit-roles`, `delete-roles` |

To add a manager: log in as admin, go to **Settings -> Admins/Roles**, create a role or user, and assign the relevant permissions. Use the exact permission names above where a permission list is required.

---

## Payments

- **COD (Cash on Delivery)** is enabled by default for every order.
- **Razorpay** is optional. To enable it:
  1. Create an account at https://razorpay.com and generate an API key pair (Key ID + Key Secret) at **Settings -> API Keys**.
  2. Add them to `.env`:
     ```env
     RAZORPAY_KEY_ID=rzp_live_XXXXXXXX
     RAZORPAY_KEY_SECRET=your_secret
     ```
  3. Leave both blank if you only want COD.
- What is implemented: creating a Razorpay order from the cart total, rendering the payment form on checkout, and **verifying the payment signature** server-side before marking the order paid. Orders not paid are tracked as COD/pending as configured.

---

## Branding & Design System

- **Palette:** deep evergreen `#263D25`, leaf green `#6F8736`, ivory `#F7F4EA`, plus neutral grays and white. Keep these official brand colors; avoid arbitrary accent colors.
- **Typography:** headings use the **Playfair Display** serif; UI and body copy use **Inter** (both Google Fonts, loaded in the storefront and admin layouts).
- **Tagline:** `PURE BY NATURE` (stored in settings as `store_tagline`).
- **Logo:** place the official logo at `public/images/logo.{svg,png,webp,jpg}` or set the `store_logo` setting — the header, footer, admin sidebar, and JSON-LD + OG markup auto-detect it and fall back to the styled wordmark otherwise.
- **Storefront CSS/JS:** `public/css/storefront.css` (design system: `.vr-*` utilities) and `public/js/storefront.js` (AJAX quick-add, toasts, cart badge, gallery, lightbox, qty steppers). No build step required.

---

## Shipping, Coupons & Returns Settings

These are managed from the **admin Settings** screens (no code changes needed):

- **Shipping**: free-shipping threshold (`free_shipping_threshold`), shipping charge settings.
- **Coupons**: create discount codes (fixed or percentage) with usage limits and expiry from the admin panel.
- **Returns**: return window in days (`return_window_days`) and the return policy text (`return_policy`) shown on product pages and the returns page. Customers can submit returns from their account order page.

---

## Running Tests

77 feature tests cover the storefront and admin flows (204 assertions).

```bash
php artisan test
# or
vendor\bin\phpunit
```

---

## Deployment to Hostinger (shared hosting)

### 1. Upload files

Upload the entire project (except `storage/framework/cache` contents and local `node_modules`) to your hosting account, e.g. to `public_html/` or a subdirectory.

### 2. Point the document root to `/public`

Choose **one** option:

- **Best (recommended):** In hPanel set the document root to the project's `public` folder (e.g. `public_html/vanriti/public`). No root `.htaccess` is involved.
- **Alternative (this repo's default):** keep files at the site root and use the included root `.htaccess` — `vanriti.com` serves the app directly without `/public`:

  ```apache
  RewriteEngine On

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^(.*)$ public/$1 [L]
  ```

  Two supporting files make this work:
  - the root `index.php` bootstraps `public/index.php` for requests to `/`;
  - `public/index.php` normalizes `SCRIPT_NAME`/`PHP_SELF` from Apache's `REDIRECT_URL` so Laravel's base-path detection is correct under the rewrite (mount-agnostic, works on `vanriti.com` and in subfolders alike).

  Ensure `public/.htaccess` is the standard Laravel default (it already is).

### 3. Create the database & user

In hPanel create a MySQL database and a user, note the credentials.

### 4. Configure `.env`

Copy `.env.example` to `.env` on the server and set production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vanriti.com
APP_KEY=            # run: php artisan key:generate
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
ADMIN_EMAIL=you@your-domain.com
ADMIN_PASSWORD=StrongPassword!   # set BEFORE seeding
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=you@your-domain.com
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=ssl
```

### 5. Migrate & seed

On the server (via SSH terminal, or a one-time script) run:

```bash
php artisan migrate --force --seed
```

> `--force` bypasses the confirmation prompt in production. **Make sure `ADMIN_PASSWORD` is set** in `.env` before seeding so the admin user gets a known strong password.

Also update the sitemap URL in `public/robots.txt` to the live domain (`Sitemap: https://vanriti.com/sitemap.xml`).

### 6. Storage link

Link the storage directory so uploaded images are served from `public/storage`. If SSH is available:

```bash
php artisan storage:link
```

If you cannot run PHP on the server, create the `public/storage` symlink via the file manager pointing to `../storage/app/public`.

> If you uploaded files instead of cloning, run `composer install --no-dev --optimize-autoloader` on the server (or upload `vendor/`).

### 7. Cache config & routes

After `.env` is finalized, cache the configuration and routes for performance:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> `php artisan config:cache` bakes `.env` values into a cached config file and disables `env()` calls inside config. **Never use `env()` directly outside `config/*.php` files** once caching is enabled.

### 8. Queue worker (notifications)

Notifications/emails are queued using the **database** queue driver. On Hostinger shared hosting there is no long-lived `queue:work`, so schedule a cron job to run the worker with a timeout:

```cron
* * * * * /path/to/php /home/USER/domains/YOURDOMAIN/public_html/artisan queue:work --stop-when-empty --tries=3 --timeout=90
```

---

## Production Caching Notes

- Use `php artisan config:cache` and `php artisan view:cache` after each deploy (see above).
- **Never** run with `APP_ENV=local` and `APP_DEBUG=true` on a production server — these expose stack traces and config details.
- Keep every `env()` call inside `config/*.php` files only (never in controllers/blades), so cached config stays correct.
- Stay on the **`file` / `database`** drivers (defaults) for `CACHE_STORE`, `SESSION_DRIVER`, and `QUEUE_CONNECTION`. These work out of the box on shared hosting and need no Redis.

---

## Troubleshooting

- **500 error right after upload:** usually storage permissions or a missing storage link. Ensure `storage/` and `bootstrap/cache/` are writable and run `php artisan storage:link`.
- **Broken image/product uploads:** the `public/storage` symlink is missing or stale — re-run `php artisan storage:link`.
- **Pages/URLs point to the wrong host after moving domains:** the sitemap, canonical tags and generated links use `APP_URL`. Update `.env`'s `APP_URL`, then run `php artisan config:clear` and `php artisan config:cache`.
- **Login / OTP / phone:** this app uses **email + password** authentication only; there is no phone/OTP flow.
- **Emails not sending:** default `MAIL_MAILER=log` writes to `storage/logs` instead of sending. Configure SMTP (`MAIL_MAILER=smtp`, Hostinger `MAIL_HOST=mail.yourdomain.com` / `smtp.hostinger.com`) for real delivery.
- **`php artisan test` fails after changing `.env`:** run `php artisan config:clear` first.
- **Razorpay not appearing:** keys are blank in `.env` (COD-only mode), or payment keys are wrong — verify `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET`.
