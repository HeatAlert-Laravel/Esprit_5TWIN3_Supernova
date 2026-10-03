# HeatAlert Module 1

The repository has two Laravel 12 applications:

```text
HeatAlert/
├── frontoffice/  Resident pages, authentication, profile, and own equipment
├── backoffice/   Admin dashboard, profile CRUD, and equipment CRUD
└── shared/       Eloquent models, factories, seeders, and migrations
```

`User`, `Profile`, `TypeEquipement`, and `SensitiveEquipment` have one canonical definition in `shared/app/Models/`. Both Composer autoloaders map those classes and the database factories/seeders to `shared/`. Both service providers load the same migration files. Both apps share one local MySQL/MariaDB database named `heatalert` (XAMPP). Neither app has its own database.

## Local setup

Use PHP 8.3, Composer 2, and Node.js/npm. From each application directory, run `composer install` and `npm ci`, then `npm run build`. No additional packages are required.

1. Start **Apache** and **MySQL** in the XAMPP Control Panel (phpMyAdmin at <http://localhost/phpmyadmin> is optional, for inspection only). PHP needs the `pdo_mysql` extension enabled.
2. Create the database once: `CREATE DATABASE heatalert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Copy each `.env.example` to its ignored `.env`. Both must use `DB_CONNECTION=mysql`, `DB_DATABASE=heatalert`, and the same `DB_USERNAME`/`DB_PASSWORD` (XAMPP default: `root`, empty). Set both `APP_KEY` values to the same key and keep `SESSION_DRIVER=database` and `SESSION_COOKIE=heatalert_session`. Hostnames must match (`127.0.0.1`) for shared browser cookies.
4. Cache is `file`, the queue is `sync`, and sessions use the database (`SESSION_DRIVER=database`). Run `php artisan migrate` once from either app (migrations live in `shared/`). Demo admin credentials belong only in the ignored local `.env`.
5. `shared/` has nothing to launch.

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

The Front Office login offers Laravel's native Remember Me and password recovery. With `MAIL_MAILER=log`, password reset links are written to the ignored `frontoffice/storage/logs/laravel.log` for local use. The reset token is stored in the shared `password_reset_tokens` table and consumed after a successful reset.

Tests run on isolated SQLite in-memory storage (configured in each `phpunit.xml`), never on the `heatalert` MySQL database:

```powershell
cd D:\laravelprojet\HeatAlert\frontoffice
php artisan test
cd D:\laravelprojet\HeatAlert\backoffice
php artisan test
```

## Module 5: TypeEquipement and SensitiveEquipment

The evaluated pair is `TypeEquipement` (parent) 1 -> N `SensitiveEquipment` (child). `Profile` stays as supporting functionality (resident details and ownership: `Profile` 1 -> N `SensitiveEquipment`).

```php
TypeEquipement::sensitiveEquipments()  // hasMany(SensitiveEquipment::class)
SensitiveEquipment::typeEquipement()   // belongsTo(TypeEquipement::class)
```

**One source of truth.** `type_equipements` holds `name`, `sensitive_to_heat`, `sensitive_to_outage` and `risk_level` (`low`, `medium`, `high`, `critical`). `sensitive_equipments` keeps only `profile_id`, `type_equipement_id`, `name` and `description`. The former free-text `type` and per-row `priority_level` columns were removed, so nothing duplicates a type's name, sensitivities or risk. Column names are English like the rest of the schema; the `type_equipements` table name follows the official module guide.

**Migrations (additive, run with a plain `php artisan migrate`):**

1. `create_type_equipements_table` creates the table and the seven canonical types (Refrigerator, Medical equipment, Aquarium, Freezer, Fan, Air conditioner, Other).
2. `add_type_equipement_id_to_sensitive_equipments_table` adds a nullable foreign key (`restrictOnDelete`) and maps every existing row.
3. `finalize_sensitive_equipment_type_normalization` aborts if any row is unmapped, then drops `type` and `priority_level` and makes the key mandatory. Its `down()` restores both columns from the type.

Mapping rule for existing rows (first match on the equipment name, then the legacy `type`): medical, respirator, oxygen, cpap, insulin, dialysis or nebuliser -> Medical equipment; freezer -> Freezer; refrigerator or fridge -> Refrigerator; aquarium -> Aquarium; air conditioner -> Air conditioner; fan -> Fan; legacy type `medical` -> Medical equipment; anything else -> Other. Old per-row priorities were demo values, so risk now comes from the type (for example a refrigerator that was `low` is now `high`).

**Back Office:** `Equipment types` CRUD at `/admin/type-equipements`. A type used by equipment cannot be deleted (flash message, no cascade). The equipment list filters by type, risk, heat-sensitive, outage-sensitive and resident search; filters persist across pagination. The dashboard shows aggregate equipment statistics from the types. **Front Office:** residents pick a type, see risk and sensitivity badges, and get simple rule-based preparedness messages (no AI, no medical advice).
