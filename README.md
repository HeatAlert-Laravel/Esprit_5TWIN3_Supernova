# HeatAlert

`frontoffice/` is reserved for the resident website. `backoffice/` contains the Laravel admin dashboard.

## First time

Install PHP 8.3+ with SQLite, Composer, and Node.js 22, then run in PowerShell:

```powershell
cd backoffice
composer install
npm ci
Copy-Item .env.example .env
New-Item database/database.sqlite -ItemType File
php artisan key:generate
php artisan migrate
npm run build
```

## Start the app

From `backoffice/`, run:

```powershell
php -S localhost:8080 -t public
```

Open <http://localhost:8080/>. Press Ctrl+C to stop the server.
