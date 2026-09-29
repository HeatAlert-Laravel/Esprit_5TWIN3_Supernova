# HeatAlert Module 1

The repository has two Laravel 12 applications:

```text
HeatAlert/
├── frontoffice/  Resident pages, authentication, profile, and own equipment
├── backoffice/   Admin dashboard, profile CRUD, and equipment CRUD
└── shared/       Eloquent models, factories, seeders, and migrations
```

`User`, `Profile`, and `SensitiveEquipment` have one canonical definition in `shared/app/Models/`. Both Composer autoloaders map those classes and the database factories/seeders to `shared/`. Both service providers load the same migration files. The single local SQLite file is `backoffice/database/database.sqlite`; it is ignored by Git. The Front Office has no separate database file.

## Local setup

Use PHP 8.3, Composer 2, and Node.js/npm. From each application directory, run `composer install` and `npm ci`, then `npm run build`. No additional packages are required.

Copy each `.env.example` to its app's ignored `.env` on a fresh checkout. Set both `DB_DATABASE` values to the **same existing SQLite file**, preferably its absolute path with forward slashes on Windows, for example `D:/laravelprojet/HeatAlert/backoffice/database/database.sqlite`. The Front Office example also includes a working relative path when commands run from `frontoffice/`. Set both `APP_KEY` values to the same key and keep `SESSION_DRIVER=database` and `SESSION_COOKIE=heatalert_session` in both. `APP_URL`, `FRONTOFFICE_URL`, and `BACKOFFICE_URL` in the examples use `127.0.0.1` on ports 8080 and 8081. Hostnames must match for shared browser cookies.

On a fresh installation only, create the one SQLite file under `backoffice/database/` and run `php artisan migrate` once from `backoffice/`. Existing installations must retain their file and migration history. Run `php artisan migrate:status` to inspect the schema. Demo admin credentials belong only in the ignored local `.env`.

## Run

In separate terminals:

```powershell
cd D:\laravelprojet\HeatAlert\frontoffice
php artisan serve --host=127.0.0.1 --port=8080
```

```powershell
cd D:\laravelprojet\HeatAlert\backoffice
php artisan serve --host=127.0.0.1 --port=8081
```

Front Office: <http://127.0.0.1:8080/>. Back Office: <http://127.0.0.1:8081/admin>. Each app serves its own built Vite assets. Run `npm run dev` in the corresponding app directory when editing frontend assets.

## Responsibilities and security

Resident routes, controllers, Blade layouts, and Vite assets live in `frontoffice/`. The resident equipment controller loads equipment through the signed-in user's profile relation. Admin routes, controllers, middleware, Blade layouts, and TailAdmin assets live in `backoffice/`. The Back Office login accepts only `ADMIN` credentials, and the `admin` middleware checks every admin route. Registration always creates a `USER`. Both apps use the same app key, session cookie, session table, and database so a local browser session works across ports.

Tests use in-memory SQLite and additive migrations, leaving the local demo database intact:

```powershell
cd D:\laravelprojet\HeatAlert\frontoffice
php artisan test
cd D:\laravelprojet\HeatAlert\backoffice
php artisan test
```
