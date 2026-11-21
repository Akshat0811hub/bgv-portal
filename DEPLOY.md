# Deploying Orbit Verify to Hostinger

Laravel 13 + PHP 8.4 or newer, MySQL, authenticated SMTP email, documents stored on the private
local disk. No Node, Redis, or queue worker is required on the server.

When replacing an existing portal, follow [the migration checklist](docs/hostinger-migration.md).
Preserve the old application/database and verify local backups before switching the domain.

---

## 0. Before you start (do this locally)

The repository includes the built Vite assets in `public/build`. If your hosting plan has
no Node/npm, rebuild changed frontend assets locally and ship them with the PHP app:

```bash
cd orbit-verify-laravel
npm install
npm run build          # creates public/build/
```

You will upload `public/build/` to the server (step 3).

---

## 1. hPanel setup

1. **PHP version** → Advanced → PHP Configuration → select **8.4** (or newer).
   Enable extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, `ctype`, `bcmath`.
2. **Database** → Databases → MySQL → create a database + user.
   Note the **DB name, user, password** (host is usually `localhost`/`127.0.0.1`).
3. **SSL** → enable the free SSL certificate for the domain (Brevo + secure cookies need HTTPS).
4. **SSH access** → enable it (Business/Cloud plans). You'll need SSH for `artisan` commands.

---

## 2. Get the code on the server

Pick ONE:

**A. Git (preferred)** — hPanel → Advanced → Git, or via SSH:
```bash
cd ~
git clone <your-repo-url> orbitverify
```

**B. Upload a ZIP** — zip the project locally (exclude `node_modules`), upload via
File Manager to `~/orbitverify`, and extract.

> Put the app **outside** `public_html` (e.g. `~/orbitverify`). We point the web
> root at its `public/` folder in step 6.

---

## 3. Install dependencies & upload the build

Via SSH in `~/orbitverify`:
```bash
composer install --no-dev --optimize-autoloader
```
> If Composer isn't available on your plan, run `composer install --no-dev -o`
> locally and upload the resulting `vendor/` folder.

Upload your locally-built **`public/build/`** folder into `~/orbitverify/public/build/`
(File Manager or `scp`). Confirm `public/build/manifest.json` exists on the server.

---

## 4. Environment file

```bash
cp .env.hostinger.example .env
php artisan key:generate         # writes APP_KEY into .env
```
Then edit `.env` and set:
- `APP_URL=https://your-domain.com`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (from step 1)
- Confirm `APP_ENV=production`, `APP_DEBUG=false`
- Keep `AUTO_INITIALIZE_DATABASE=false`; import existing accounts instead of seeding defaults.

---

## 5. Database, permissions, caches

```bash
# writable dirs
chmod -R 775 storage bootstrap/cache

# schema + validated import into the new, empty database
php artisan migrate --force
php artisan portal:import-backup /PRIVATE/PATH/source-bgv-backup.json --validate-only
php artisan portal:import-backup /PRIVATE/PATH/source-bgv-backup.json

# production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Documents are saved to `storage/app/private` and served only through the
> authorized `/documents/{id}/view` route — **never** publicly. You do **not**
> need `php artisan storage:link`.

---

## 6. Point the domain at `public/`

**Preferred:** hPanel → Website → set the domain's **Document Root** to
`~/orbitverify/public`.

**Fallback (if you can't change the document root):** keep `public_html` as root —
copy the contents of `~/orbitverify/public/*` into `public_html/`, then edit
`public_html/index.php` so the two `require` paths point up to the app:
```php
require __DIR__.'/../orbitverify/vendor/autoload.php';
$app = require_once __DIR__.'/../orbitverify/bootstrap/app.php';
```
Make sure `public_html/.htaccess` (Laravel's) is present for pretty URLs.

---

## 7. Authenticated SMTP email — final check

- Configure the actual sender and mail provider in admin email settings. Verify
  SPF/DKIM/DMARC for that sender's domain. Inbox placement is not guaranteed.
- Test after deploy:
  ```bash
  php artisan tinker --execute="Mail::raw('Orbit Verify test', fn(\$m)=>\$m->to('you@example.com')->subject('Test'));"
  ```

---

## 8. Smoke test (after go-live)

- [ ] `https://your-domain.com` redirects to `/login`
- [ ] Admin login at `/admin/login` (master admin from step 4)
- [ ] Create a client user → credentials email received
- [ ] Create a case (both "send link" and "enter details now"); check fields appear
- [ ] Candidate upload link works and submitted data + files save
- [ ] Download PDF report
- [ ] Client login → dashboard, profile, MIS CSV export

---

## 9. Shipping updates later

```bash
cd ~/orbitverify
git pull                      # or upload changed files
composer install --no-dev -o  # only if composer.lock changed
# upload a fresh public/build if assets changed
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Notes / gotchas

- **Always re-run `config:cache` after editing `.env`** — cached config ignores
  later `.env` changes.
- If you see a 500, inspect private application logs with `APP_DEBUG=false`.
- Emails send **synchronously** (no queue worker needed). If volume grows, switch
  the Mailables to `ShouldQueue` and run `php artisan queue:work` via a cron/daemon.
- Timezone is set to `Asia/Kolkata` for correct TAT/date display.
