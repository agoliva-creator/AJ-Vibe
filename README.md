# Commerce Desk

A Laravel 13 marketplace with a Bootstrap 5 storefront, admin and vendor workspaces, persistent customer carts, transactional checkout, coupons, reviews, printable invoices, PDF downloads, and a Sanctum REST API.

See [ARCHITECTURE.md](ARCHITECTURE.md) for the domain model, authorization matrix, transaction boundaries, API contract, migration strategy, and deployment considerations.

## Stack

- Laravel 13.35.0, PHP 8.4 in this workspace (Laravel 13 requires PHP 8.3+).
- Sanctum 4.3.3 for expiring bearer tokens.
- Bootstrap 5.3.8, locally bundled with Vite.
- barryvdh/laravel-dompdf 3.1.2 for PDF invoices.
- Pest feature tests, Laravel Pint, database sessions, and queued Mailables.

The Laravel version was resolved against Composer's stable Laravel 13 releases. Commit and deploy the lockfiles so production uses the verified dependency versions.

## Features and roles

| Role | Capabilities |
| --- | --- |
| Admin | Manage users, roles, categories, all products, coupons, orders, settings, and review deletion |
| Vendor | Create/update/archive owned products, see owned order lines and delivered sales, process owned fulfillment |
| Customer | Browse/search/filter products, maintain a cart, place orders, view owned history/invoices, cancel before shipping, review delivered purchases |

Registration always creates a customer. An administrator promotes accounts to vendors. Vendor ownership cannot be supplied by a vendor in a product payload. The five CRUD modules are users, categories, products, orders, and coupons; order creation uses checkout, and deletion archives cancelled orders.

## Install locally

1. Install PHP 8.3+ with PDO SQLite (or your chosen PDO driver), mbstring, fileinfo, DOM/XML, and GD; install Composer and a Node version supported by Vite. This workspace uses PHP 8.4 and Node 24.
2. In the application root, install the locked dependencies:

```sh
composer install
npm ci
```

On Windows PowerShell, use `npm.cmd` if the `npm.ps1` execution policy prevents running `npm`.

3. Create a local environment file only if one does not already exist:

```powershell
Copy-Item .env.example .env
```

On Linux/macOS, use `cp .env.example .env`. Preserve an existing environment file and its application key.

4. Set `APP_URL` to your actual Herd domain, choose your database, and provide credentials. For a new installation only, generate the encryption key:

```sh
php artisan key:generate
```

5. For local sample data, set `APP_ENV=local` and `SEED_DEMO=true`. For a real administrator, set `ADMIN_EMAIL` and a private `ADMIN_PASSWORD` of at least 12 characters with uppercase, lowercase, and digits. The seeder never overwrites an existing password.

6. Prepare the database and frontend:

```sh
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
```

Migrations are additive and preserve the earlier application's records. Do not run `migrate:fresh` on an existing database. Legacy products need a vendor assigned through the admin product editor before they appear in the public catalog.

7. Laravel Herd already serves this project; open its configured domain. Use `herd sites` to inspect your local mapping. The shop is at `/`, sign-in at `/login`, and the workspace at `/dashboard`.

8. Start a queue worker in a separate terminal to process transactional mail:

```sh
php artisan queue:work --tries=3 --timeout=60 --backoff=10
```

Without a running worker, queued messages wait in the jobs table.

## Local demo accounts

Demo seeding is available only in local/testing environments and only with `SEED_DEMO=true`.

| Email | Role |
| --- | --- |
| admin@commerce.test | Admin |
| vendor@commerce.test | Vendor |
| customer@commerce.test | Customer |

Password for all three: `CommerceDemo123!`. The sample coupon is `WELCOME10`. Demo identities belong only in local development. Disable demo seeding for deployment. Earlier demo accounts are preserved; the old Staff role is migrated to Vendor.

## Mail configuration

The default log mailer writes mail into `storage/logs/laravel.log` when the worker processes it. To deliver messages, configure your SMTP provider:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=orders@your-domain.example
MAIL_FROM_NAME="Your Store"
QUEUE_CONNECTION=database
```

SMTP STARTTLS negotiation is handled by the transport when supported. Use your provider's required settings; for implicit TLS use its SMTPS scheme/port. Store credentials privately. Set `APP_URL` to your public HTTPS origin so queued email links are correct.

Welcome messages are queued after registration. Confirmation messages are queued after checkout commits. Admin low-stock messages are queued on a sale crossing the configured threshold. Mailables retry with backoff. The order page also allows a buyer or admin to queue another confirmation.

## Images

Product images and avatars accept JPEG, PNG, or WebP up to 2 MB. Product images are limited to 6000 × 6000 pixels and avatars to 3000 × 3000. Filenames are generated by Laravel; SVG and other active formats are rejected. Replacing an image removes the previous file after the new database reference is saved.

The default `COMMERCE_IMAGE_DISK=public` needs the storage symlink. Soft-deleted records retain image files to preserve history. For S3, install Laravel's Flysystem S3 adapter, configure the standard AWS filesystem values, and use `COMMERCE_IMAGE_DISK=s3`; public object access or an appropriate URL strategy must be configured. That optional S3 integration is not exercised by the default local tests.

## Use the API

The browser application uses sessions and CSRF-protected forms. External clients use bearer tokens; the API is not configured as a cookie-authenticated SPA.

Create a token with `POST /api/v1/tokens`:

```json
{"email":"customer@commerce.test","password":"CommerceDemo123!"}
```

Use the returned token in `Authorization: Bearer TOKEN` and request JSON with `Accept: application/json`. Tokens expire after seven days. Revoke the current token with `DELETE /api/v1/tokens/current`.

Example cart synchronization, `PUT /api/v1/cart`:

```json
{"items":[{"product_id":1,"quantity":2}]}
```

Example checkout, `POST /api/v1/orders`:

```json
{
  "shipping_address":"123 Main Street, Manila, Philippines",
  "coupon_code":"WELCOME10",
  "idempotency_key":"d129260d-3d87-4f52-9405-655a3dcdb04e"
}
```

Use a fresh UUID for each intended purchase. Keep the same UUID when retrying that purchase after a timeout. Prices and totals are calculated by the server. This implementation uses payment due on delivery; it does not charge a payment provider.

The complete endpoint reference is in [ARCHITECTURE.md](ARCHITECTURE.md) and the authenticated `/api-guide` page.

## Verify

```sh
php artisan test --compact
vendor/bin/pint --format agent
npm run build
php artisan route:list --except-vendor
```

Tests use an isolated in-memory SQLite database and fake mail/storage where applicable. They cover authorization, management CRUD, uploads, checkout arithmetic and rollback, retries, coupons, cancellation, review eligibility, token access, disabled users, and real PDF output. Browser rendering and real SMTP/S3 delivery need validation in your target environment.

## Deploy

1. Provision a transactional MySQL/PostgreSQL database and back it up. Use that engine for concurrency tests before production; SQLite does not validate row-lock behavior.
2. Supply a private production environment: `APP_ENV=production`, `APP_DEBUG=false`, correct HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, database credentials, SMTP credentials, `SEED_DEMO=false`, and the initial admin credentials. Keep the existing `APP_KEY` on upgrades.
3. Build an artifact with `composer install --no-dev --optimize-autoloader`, `npm ci`, and `npm run build`.
4. Point the web server document root to `public/`; allow writes to `storage/` and `bootstrap/cache/`. Serve uploaded files as static content and prevent script execution in upload locations.
5. Run `php artisan migrate --force`, then `php artisan db:seed --force` for initial administrator/settings creation. Remove the bootstrap admin password from the environment after initial provisioning if it is no longer needed.
6. Run `php artisan storage:link`, `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
7. Supervise queue workers with a process manager. Set the queue reservation/retry interval longer than the 60-second worker timeout (the database queue defaults to 90 seconds). Restart workers with `php artisan queue:restart` after each deployment.
8. Configure cron to execute `php artisan schedule:run` every minute. The scheduler prunes expired Sanctum tokens.
9. Verify checkout, cancellation, ownership restrictions, invoices, email delivery, backups, and failed-job monitoring with production-like data.

A local passing suite is evidence of application behavior, not certification of deployment capacity or infrastructure. See the operating limits in the architecture guide.

## References

- [Laravel 13 documentation](https://laravel.com/framework/docs/13.x)
- [Laravel Sanctum](https://laravel.com/framework/docs/13.x/sanctum)
- [Queued mail](https://laravel.com/framework/docs/13.x/mail)
- [Bootstrap 5](https://getbootstrap.com/docs/5.3/getting-started/introduction/)
- [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf)
