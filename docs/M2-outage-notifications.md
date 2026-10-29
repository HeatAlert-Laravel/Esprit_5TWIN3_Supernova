# M2 — Outage notifications

## What was added

Laravel database notifications now connect the existing ADMIN outage workflow to a notification bell in the verified USER navbar. There are no emails, external services, new dependencies or queue requirements. All interface text is English.

## Recipients and location fallback

`Profile.neighborhood` is free text. There is no reliable foreign key from a user/profile to `Quartier`, and no account-active/disabled field. Guessing an area from names or addresses could target the wrong residents.

The requested fallback therefore sends alerts to every existing account whose role is `USER`, excluding ADMIN accounts. Unverified USER accounts also receive database records, but see the bell and notification endpoints only after email verification, matching the existing resident access rules. The message identifies the affected neighborhood rather than claiming it necessarily affects the recipient's area.

## Trigger rules

The existing `CoupureController` now wraps ADMIN creation and updates in database transactions:

- Create with `statut = active`: **New Outage Alert**.
- Create with `statut = résolue`: no alert.
- Change `résolue` to `active`: **New Outage Alert**.
- Change `active` to `résolue`: **Outage Resolved**.
- Edit without a status transition, including repeated saves of the same status: no additional alert.

An update locks and re-reads the current database row, so a stale request cannot duplicate the same transition. Outage and synchronous database-notification writes share the same transaction: both commit together or both roll back. Notification delivery is chunked in batches of 200 USER accounts.

Payloads contain an outage ID, event, English title/message/type/status, affected neighborhood/city and start date, using the existing fields. No user email or address is included.

These triggers belong to the ADMIN controller workflow. Direct SQL changes, seeders and model writes outside that workflow do not broadcast notifications. No historical notifications are generated during installation.

## USER experience

- A bell appears for authenticated, verified USER accounts only.
- The badge shows the actual number of unread outage notifications, capped visually at `99+`.
- The dropdown lists the latest ten outage notifications, with separate read/unread styling, event icons, area, type and date.
- Opening an entry submits a CSRF-protected POST, marks that owned notification as read and redirects to resident outage details.
- Individual **Mark as read** and **Mark all as read** controls update the dropdown and count. Only the current user's outage notifications are affected.
- The inbox refreshes when opened, once per minute while the tab is visible, and when the tab becomes visible again. No WebSocket service is needed.
- Basic opening and read actions also work through standard forms without JavaScript.
- A deleted outage produces a friendly redirect to Outages instead of a broken detail link.

The new resident detail view contains public outage data only, never the private reports associated with it. Active outages are accessible to verified USER accounts; resolved outage details require an owned notification or an owned report for that outage. Existing ADMIN details remain separate.

## Migration

Created and applied locally:

`database/migrations/2026_10_29_000004_create_notifications_table.php`

This creates Laravel's standard UUID/morphic `notifications` table and an unread lookup index. Existing data is preserved. On another checkout, apply migrations before opening USER pages:

```powershell
php artisan migrate
npm run build
php artisan serve
```

The existing User model already uses `Notifiable`; no change was needed there.

## Routes

All new routes use the existing `auth` and `verified` middleware. Controllers additionally require role `USER`.

| Method | Route | Name | Purpose |
| --- | --- | --- | --- |
| GET | `/outage-notifications` | `outage-notifications.index` | Current user's dropdown HTML and unread count |
| PATCH | `/outage-notifications/{notification}/read` | `outage-notifications.read` | Read one owned notification |
| PATCH | `/outage-notifications/read-all` | `outage-notifications.read-all` | Read all owned outage notifications |
| POST | `/outage-notifications/{notification}/open` | `outage-notifications.open` | Read and open an owned notification |
| GET | `/outages/{coupure}` | `outages.show` | Safe resident outage details |

UUID routes reject invalid identifiers. Foreign notifications return 404. JSON inbox/read responses use `Cache-Control: no-store`. URLs are generated on the server; no arbitrary redirect URL is accepted.

## Files created

- `database/migrations/2026_10_29_000004_create_notifications_table.php`
- `app/Notifications/OutageStatusNotification.php`
- `app/Services/OutageNotifications.php`
- `app/Support/OutageNotificationInbox.php`
- `app/Http/Controllers/OutageNotificationController.php`
- `app/Http/Controllers/ResidentOutageController.php`
- `resources/views/partials/outage-notifications.blade.php`
- `resources/views/partials/outage-notification-items.blade.php`
- `resources/views/coupures/resident-show.blade.php`
- `resources/js/outage-notifications.js`
- `resources/css/outage-notifications.css`
- `tests/Feature/OutageNotificationTest.php`
- `docs/M2-outage-notifications.md`

## Files modified

- `app/Http/Controllers/CoupureController.php`: transactional create/update and status-transition delivery.
- `app/Providers/AppServiceProvider.php`: data composer for the USER bell only.
- `routes/web.php`: protected notification and resident-detail routes.
- `resources/views/partials/navbar.blade.php`: conditional USER bell.
- `resources/views/components/ha/icon.blade.php`: bell icon in the existing icon family.
- `resources/js/app.js`: load the notification enhancement when a bell exists.
- `resources/css/app.css`: import notification styles.

AI summaries, similarity suggestions, list queries, filters, pagination, dashboard calculations and My Reports have not been modified.

## Manual walkthrough

1. Use separate browser sessions for an existing ADMIN and a verified USER.
2. As ADMIN, create an active outage with an existing neighborhood and valid dates.
3. As USER, open the bell. A **New Outage Alert** appears with an unread badge. If the page was already open, open the bell or wait up to one minute for refresh.
4. Open the entry: its unread state clears and the outage details appear.
5. As ADMIN, edit only the description and save: no second alert is generated.
6. Mark the outage as resolved: the USER receives **Outage Resolved**. Open it to verify that resolved details still work.
7. Try individual and all-read actions. Check the empty state with an account that has no notifications.
8. An unrelated user cannot read or open a foreign notification UUID. An ADMIN cannot access the USER inbox endpoints.

No actual outage or notification records were created in the local MySQL database for the automated checks; those ran in temporary SQLite memory. The additive migration is installed in MySQL.

## Tests run

```powershell
php -d extension=pdo_sqlite vendor/pestphp/pest/bin/pest tests/Feature/OutageNotificationTest.php tests/Feature/CoupureSignalementTest.php
php -d extension=pdo_sqlite vendor/pestphp/pest/bin/pest tests/Feature/OutageTest.php --filter='residents can report|reporting requires login'
```

Results: **23 passed, 175 assertions** (15 notification scenarios, 6 existing M2 CRUD scenarios and 2 existing resident-submission scenarios). Coverage includes creation/activation/resolution, unchanged edits, duplicate transition requests, transaction rollback, owned inboxes, idempotent reads, mark-all isolation, foreign IDs, deleted/resolved outages, private report exclusion, unread counts, empty/recent inboxes, and authentication/role/email-verification restrictions.

Vite production build, PHP/JavaScript syntax checks and `git diff --check` also passed. No test configuration was changed. An interactive two-account browser walkthrough has not been performed.

## Limitations

Area-specific targeting requires a reliable user-to-neighborhood relationship that does not currently exist. Existing alerts are not backfilled for newly created accounts. Delivery is synchronous and intended for this project's current scale; a large deployment could later queue delivery. The dropdown shows ten recent items rather than a full notification archive. Status history is represented by notifications, not a separate audit log.
