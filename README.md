# HeatAlert

HeatAlert helps residents prepare for heatwaves and power outages, and gives administrators the tools to manage the related data.

It is **one Laravel 12 application** with the standard framework structure. Residents (`USER`) and administrators (`ADMIN`) use the same application, the same `users` table and the same login. The authentication and middleware decide what each role can open.

## Architecture

```text
HeatAlert/
├── app/
│   ├── Http/
│   │   ├── Controllers/      All controllers (admin CRUD + resident pages + authentication)
│   │   ├── Middleware/       EnsureAdmin ("admin" alias), SetLocale
│   │   └── Requests/         Form Request validation classes
│   ├── Models/               Eloquent models (User, Profile, TypeEquipement, SensitiveEquipment, ...)
│   ├── Providers/            AppServiceProvider (view composers)
│   ├── Support/              Small helpers used by views (Readiness, AdviceNavigation)
│   └── View/Components/      Blade component classes from the TailAdmin template
├── bootstrap/                app.php: routing, middleware alias, redirects
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/              DatabaseSeeder calls every module seeder, parents first
├── lang/en.json
├── public/
├── resources/
│   ├── css/                  app.css (resident), admin.css (admin), heatalert-theme.css (shared theme)
│   ├── js/                   app.js (resident), admin.js (admin) + small modules
│   └── views/                Blade views grouped by feature (see below)
├── routes/
│   ├── web.php               All HTTP routes
│   └── console.php
├── storage/
├── tests/                    Pest feature and unit tests
├── artisan
├── composer.json
├── package.json
├── phpunit.xml
└── vite.config.js
```

### Features

| Feature | Models | Admin area (`/admin`, ADMIN only) | Resident pages | Views |
| --- | --- | --- | --- | --- |
| Neighborhoods and weather alerts (Module 1) | `Quartier`, `AlerteMeteo` | `QuartierController`, `AlerteMeteoController` | `WeatherAlertController` (`/weather-alerts`) | `quartiers/`, `alertes-meteo/` |
| Outages and reports (Module 2) | `Coupure`, `Signalement` | `CoupureController`, `SignalementController` | `OutageController` (`/outages`, `/outages/report`) | `coupures/`, `signalements/` |
| Cooling points (Module 3) | `TypePoint`, `PointFraicheur` | `TypePointController`, `PointFraicheurController` | `CoolingPointController` (`/cooling-points`) | `type-points/`, `point-fraicheurs/` |
| Advice (Module 4) | `CategorieConseil`, `Conseil` | `CategorieConseilController`, `ConseilController` | `AdviceController`, `AdviceBookmarkController` (`/advice`) | `categorie-conseils/`, `conseils/` |
| Equipment types and sensitive equipment (Module 5) | `TypeEquipement`, `SensitiveEquipment`, `Profile` | `TypeEquipementController`, `SensitiveEquipmentController`, `ProfileController` | `ResidentProfileController`, `ResidentSensitiveEquipmentController` (`/my-profile`) | `type-equipements/`, `sensitive-equipments/`, `profiles/` |
| Dashboard and home | all of the above | `DashboardController` (`/admin`) | `HomeController` (`/`) | `dashboard/`, `home.blade.php` |
| Authentication and account security | `User` | — | Laravel Fortify (registration, login, password reset, email verification, 2FA) + `AccountController` (`/account`) | `auth/`, `account/` |

### Routes, roles and middleware

`routes/web.php` is grouped by access level:

1. **Public pages** for everyone: home, weather alerts, outages, cooling points, advice.
2. **Guest-only routes** (`guest` middleware): login, registration, forgotten password, password reset.
3. **Signed-in routes** (`auth` middleware): logout, *My profile*, the resident's own equipment, outage reports, saved advice and the admin preview of an article.
4. **Admin area**: `Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])`, mostly `Route::resource()`.

Access is checked on the server:

- `auth` sends guests to `/login`.
- `admin` (`App\Http\Middleware\EnsureAdmin`) returns **403** to any signed-in user whose role is not `ADMIN`.
- Registration always creates a `USER`. After login, `ADMIN` goes to `/admin` (or the admin page they asked for) and `USER` goes to `/my-profile`.
- Resident controllers load equipment through the signed-in user's profile, so a resident can only change their own records.

Login supports Laravel's *Remember me*. Password recovery uses Laravel's password broker. With `MAIL_MAILER=log`, reset links are written to `storage/logs/laravel.log`.

### Views and assets

The application has two visual shells:

- `layouts.app` for resident pages (navbar, footer);
- `layouts.admin` for the admin area (sidebar and topbar based on the TailAdmin template).

Each feature has one folder in `resources/views`. Admin pages use the resource names (`index`, `create`, `edit`, `show`, `_form`). Resident pages in the same folder start with `resident-`. Example: `conseils/index.blade.php` is the admin list and `conseils/resident-index.blade.php` is the public advice page. Shared pieces live in `layouts/`, `partials/` and `components/` (`x-ha.*` design components, `x-logo`, `x-advice.*`).

`vite.config.js` is the single asset pipeline. It builds `resources/css/app.css` + `resources/js/app.js` for `layouts.app`, and `resources/css/admin.css` (TailAdmin's Tailwind theme) + `resources/js/admin.js` (Alpine.js, charts, the Quill editor) for `layouts.admin`. `resources/css/heatalert-theme.css` is the HeatAlert design system and is loaded by both layouts.

`resources/views/tailadmin/` keeps the demo pages that came with the TailAdmin template. No route uses them.

## Requirements

- PHP 8.3 with the `pdo_mysql` extension
- Composer 2
- Node.js 22 (see `.nvmrc`) with npm
- MySQL or MariaDB (for example XAMPP)

## Installation

```powershell
git clone https://github.com/HeatAlert-Laravel/Esprit_5TWIN3_Supernova.git
cd Esprit_5TWIN3_Supernova

composer install
npm ci

copy .env.example .env
php artisan key:generate
```

Create the database once, for example from phpMyAdmin or the MySQL console:

```sql
CREATE DATABASE heatalert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Check the database settings in `.env` (XAMPP defaults shown):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=heatalert
DB_USERNAME=root
DB_PASSWORD=
```

Then create the tables, optionally load the demo data, and build the assets:

```powershell
php artisan migrate
php artisan db:seed      # optional demo data for every module
npm run build
```

To get a local administrator account, set `DEMO_ADMIN_EMAIL` and `DEMO_ADMIN_PASSWORD` in your `.env` before running `php artisan db:seed`. This only happens with `APP_ENV=local`. Accounts created through `/register` are always `USER`.

## Run

```powershell
php artisan serve
```

- Resident site: <http://127.0.0.1:8000/>
- Admin area: <http://127.0.0.1:8000/admin> (sign in with an `ADMIN` account)

While editing CSS or JavaScript, also run `npm run dev` in a second terminal.

## Tests

```powershell
php artisan test
```

Tests run on an in-memory SQLite database (configured in `phpunit.xml`), never on the `heatalert` MySQL database.

## Working with the database as a team

Each teammate has their own local `heatalert` database; migrations share the structure. After pulling, run `php artisan migrate` to apply only the pending migrations. For a schema change, add a **new migration** in `database/migrations/` and never edit one the team has already applied. Do not use `migrate:fresh`, `migrate:reset` or `db:wipe` on a database that holds real data.

`php artisan db:seed` runs every module seeder in parent-before-child order. The seeders can be rerun without duplicating unchanged examples.

## Module 5: TypeEquipement and SensitiveEquipment

The evaluated pair is `TypeEquipement` (parent) 1 -> N `SensitiveEquipment` (child). `Profile` is supporting functionality: it holds resident details and ownership (`User` 1 -> 1 `Profile`, `Profile` 1 -> N `SensitiveEquipment`).

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

**Admin area:** `Equipment types` CRUD at `/admin/type-equipements`. A type used by equipment cannot be deleted (flash message, no cascade). The equipment list filters by type, risk, heat-sensitive, outage-sensitive and resident search; filters persist across pagination. The dashboard shows aggregate equipment statistics from the types. **Resident pages:** residents pick a type, see risk and sensitivity badges, and get simple rule-based preparedness messages (no AI, no medical advice).

**Admin lists and filters.** Equipment, profile and equipment-type lists filter automatically through plain GET parameters (`resources/js/auto-filter.js`, opt-in with `data-auto-filter`): selects and checkboxes apply on change, text search after a 400 ms pause, empty values are dropped from the URL, and pagination keeps the active filters. "Reset filters" returns to the clean index route; without JavaScript a `<noscript>` Apply button submits the form. Admin screens show names (resident, type, equipment), never raw database IDs.

**Profile deletion rule.** An administrator cannot delete a profile that still owns sensitive equipment ("This profile still has X sensitive equipment records."). Delete or reassign the equipment first; the resident account itself is never deleted by this action.

## Module 4: CategorieConseil and Conseil

`CategorieConseil` is the parent (one category has many articles); `Conseil` belongs to exactly one category.

- `categorie_conseils`: `nom` (unique), optional `description`, and an icon selected from the existing HeatAlert icons.
- `conseils`: mandatory `categorie_conseil_id`, `titre`, `resume`, searchable plain-text `contenu`, nullable JSON `contenu_formate`, `public_cible` (`everyone`, `seniors`, `parents`, `caregivers`), `situation` (`heatwave`, `outage`, `both`), and `actif` (published, false by default).
- `conseil_user`: personal bookmarks, with a unique user/article pair and foreign keys that remove bookmarks when their user or article is deleted.

Administrators manage both entities at `/admin/conseils` and `/admin/categorie-conseils`. Articles start as drafts and can be previewed in the resident layout by administrators before publication. A category containing articles cannot be deleted; the database foreign key also enforces this rule. Article text is escaped, including pasted HTML.

The editor uses locally bundled Quill 2.0.3 with paragraphs, two section-heading levels, bold, italic, bullet/numbered lists, and undo/redo. It submits a JSON document; the server validates the allowed text formats and derives plain text for search and reading time. The admin review and the resident reading page render the same escaped HTML from that document, so their formatting matches. Images, links, colors, and embedded HTML are not supported. Legacy plain articles still display safely and can be upgraded by editing them. Validation errors retain the document. Without JavaScript, formatted documents remain preserved and plain articles have a text fallback.

Residents browse published articles at `/advice`, grouped by category, with search, audience, and heatwave/power-outage filters. Audience filters include general advice for everyone; situation filters include advice applying to both situations. Each article has a reading page and related published advice. Reading requires no account; drafts return 404 on public URLs. Preview requires an authenticated administrator.

Signed-in residents can save articles from cards or the reading page and find them at `/advice/saved`. Bookmarks belong only to their account. Guests get a sign-in prompt. Saving is never triggered by a GET request. Result counts update with filters; Back to results preserves filters and pagination, including when opening an article from saved advice.

### Article audio reader

Published article reading pages have a compact Gemini-powered reader on the metadata row, beside the updated date and above the summary. Listing cards have no audio controls. An icon toggles play/pause; stop and elapsed time appear once audio is ready. A small settings menu contains Kore (female) or Charon (male), playback speed and a seek slider. Changing voice stops the previous recording. Nothing plays on page load.

Set GEMINI_API_KEY in the ignored local .env. Optional settings are GEMINI_TTS_MODEL (default gemini-3.8-flash-lite-tts) and GEMINI_TTS_DAILY_LIMIT (default 30 new recordings in a rolling 24-hour period). No new package or database migration is required. After changing settings, run php artisan config:clear; after pulling frontend changes, run npm ci and npm run build from the repository root.

Only the article title, summary and plain-text content are sent to Google. Audio is cached privately under storage/app/private/advice-audio, separately for each voice and article version. Editing text invalidates the old recording; unpublishing an article immediately blocks its audio endpoint. GET requests only serve existing recordings; explicit POST requests generate audio, with CSRF protection, per-client throttling and a shared generation budget. The API key and upstream error details never reach the browser. Rate limits and account quotas still apply; cached audio can be reused without another generation request. Missing configuration and provider failures show reader-friendly messages while the article remains readable.

The free Gemini tier may use submitted content to improve Google's products. Keep draft/private content out of generation. Each teammate configures their own local key; do not commit .env, keys, generated audio or local database data.

Optional example content (4 categories, 11 published articles, 1 draft):

```powershell
php artisan db:seed --class=ConseilSeeder
```

Reseeding upgrades untouched sample articles to structured content while preserving administrator edits.

### Article usefulness votes

Published reading pages show a compact upvote / score / downvote control beside the bookmark in the article header. Each signed-in account has one vote per article; clicking the selected arrow removes it and choosing the opposite arrow switches it. The score is upvotes minus downvotes. Guests can see the score and sign in to vote. Administrators see both counts on the article detail page; drafts cannot receive votes.

Votes are stored in `conseil_votes`, with a unique user/article pair and cascading foreign keys. Teammates should run `php artisan migrate` after pulling, then rebuild assets with `npm ci` and `npm run build`. No vote seeder is needed: feedback comes from users, and each local database keeps its own votes.

The advice and saved-advice indexes offer “Most helpful” sorting across categories: net score descending, then upvote count, title and ID for stable pagination. Unrated articles have score zero; search and filters still apply, and returning from an article preserves the sort. The default grouped category view remains available.

The admin advice list shows separate vote counts and offers feedback filters for ranked voted articles, articles receiving downvotes, and published articles without votes. Its expandable feedback overview lists up to three positive-score articles and three articles with downvotes, matching the search, category and visibility filters. Downvotes invite review; they do not automatically unpublish content. Drafts are excluded from feedback filters and overview lists, while any historical votes remain visible on their table rows and detail pages. No additional migration is needed for sorting or insights.

### Article AI assistant

Published articles have a floating “Ask AI” button at the bottom right, opening a compact panel above it for summaries, interactive checklists and a conversation about that article. The panel fits small viewports, scrolls independently, and closes using its close button, launcher, an outside click or Escape. It does not block article interaction. No generation happens on page load. Generated text is rendered as text, never HTML. Checklist ticks stay only on the current page. Draft and admin preview pages do not offer the assistant; the endpoint rejects drafts before sending any content.

The assistant uses `GROQ_API_KEY` from the ignored `.env`. Optional settings: `GROQ_ASSISTANT_MODEL` (default `openai/gpt-oss-20b`), `GROQ_ASSISTANT_FALLBACK_MODELS` (comma-separated, default `openai/gpt-oss-120b`) and `GROQ_ASSISTANT_DAILY_LIMIT` (default 100 upstream requests per rolling 24-hour window). Narration still uses the independent Gemini settings. Run `php artisan config:clear` after configuration changes and rebuild frontend assets after pulling. No database migration or extra package is required.

The Laravel backend calls Groq's chat completions endpoint using bearer authentication and strict JSON schema output (`answer` and `points`). Configured models must support strict structured outputs; the defaults both do. Temporary connection failures, unavailable-model 404s and upstream 500/502/503/504 errors advance to the next model. The chain deduplicates names and allows at most three models, with a 15-second timeout per attempt. If fallbacks are explicitly empty, the primary gets one retry. Every attempt counts against the daily budget. Authentication, quota, validation, blocked-content and malformed-output responses stop without switching models. Persistent overload gets a specific busy message. All models receive the same article-only instructions. The cache includes the provider and model chain, keeping old Gemini results separate.

The current published article's title, summary and plain-text body, the submitted question, and up to six recent successful conversation messages are sent to Groq for follow-ups. Names and avatars stay in the UI. Summary/checklist generation does not use conversation context. Instructions require article-grounded responses and an explicit lack-of-context answer for unsupported questions; generated answers can still be mistaken and the UI identifies them as AI-generated. The chat displays named user/AI avatars, retains prior messages and checklist ticks while the page stays open, clears the submitted question immediately, and provides in-place retry for failed replies. Chat messages are held only in page memory; a reload starts a new thread. There is no database storage of conversations. The server validates a maximum of six user/assistant history entries and treats that history as untrusted context, never as system instructions. Summaries/checklists are cached privately for 24 hours by article content, provider, model chain and locale; questions are not cached. Editing the article invalidates the cache, and unpublishing blocks access even to cached results. The endpoint has CSRF protection, per-client throttling and a shared daily budget. Keys and upstream error details never reach the browser. API account quotas and Groq's data-processing terms apply.

## Credits

The admin layout is built on [TailAdmin Free Laravel](https://tailadmin.com/laravel) (MIT license, see `docs/TailAdmin-LICENSE.txt`).
