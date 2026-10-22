# Authentication and account settings

HeatAlert remains one Laravel 12 application with its own Blade components. Laravel Fortify provides authentication, registration, password reset, signed email verification, password confirmation and TOTP two-factor authentication. Passkeys and Spatie Permission are not enabled.

## Identity and authorization

- Registration accepts name, email and a confirmed password of at least eight characters. The server always assigns `USER`; submitted roles, verification dates and two-factor secrets are ignored.
- Login normalizes email and preserves remember me. Fortify's built-in `LoginRateLimiter` permits five failed attempts per normalized email and IP per minute, clears on successful authentication, and displays its lockout message in the HeatAlert form. The separate two-factor challenge allows five attempts per account and IP per minute.
- Verified users return to a local intended URL, or their household profile (`USER`) / dashboard (`ADMIN`). Unverified users go to the verification notice. External intended URLs are discarded.
- All personal profile/equipment, reports, saved advice and voting operations require `auth` and `verified`. Public home, weather, advice and cooling-point pages stay public. Administration requires `auth`, `admin` and `verified`; there is no production verification bypass.
- Account settings remain available before verification, allowing an incorrect address to be fixed. Changing email requires the current password, clears verification, removes reset tokens for the old address and sends a new verification email. Name/email changes do not modify the separate household profile.
- Only the explicitly configured local demo admin may be marked verified by `DatabaseSeeder`, inside its existing `local` environment condition. Existing production accounts must verify normally.

## Account and security routes

| Route | Purpose |
|---|---|
| `GET /account` | Identity, avatar, password, verification status, 2FA and danger zone |
| `GET /account/security` | Confirm the current password before showing sensitive settings |
| `PUT /user/profile-information` | Fortify account information update |
| `PUT /user/password` | Fortify password update requiring the current password |
| `POST /account/avatar`, `DELETE /account/avatar` | Upload, replace or remove the signed-in user's picture |
| `DELETE /account` | Password-confirmed account deletion with explicit agreement |
| `/user/two-factor-*`, `/user/confirmed-two-factor-authentication` | Fortify TOTP setup, confirmation, QR, recovery codes and disabling |
| `GET/POST /two-factor-challenge` | Finish login using TOTP or one recovery code |

Password confirmation expires after 15 minutes by default (`AUTH_PASSWORD_TIMEOUT`). The Account view does not render a setup key, QR or recovery codes after confirmation expires. Sensitive pages and Fortify responses use `Cache-Control: no-store, private`. Fortify encrypts the TOTP secret and recovery codes, which are also hidden from User serialization. Protection starts only after a valid setup code; an unfinished setup does not lock the account out. A consumed recovery code is replaced and cannot be reused.

Changing the password rotates the remember token and current session, clears password confirmation, removes pending reset tokens and revokes other database sessions. Password reset retains the Laravel broker's 60-minute expiry, 60-second dispatch throttle, hashed tokens, token consumption and remember-token rotation. Login/logout keep Laravel session regeneration/invalidation and CSRF protections.

## Avatars and deletion

Pictures are stored under `storage/app/public/avatars` with generated names. Only JPEG, PNG and WebP images up to 2 MB are accepted; SVG is rejected. `php artisan storage:link` exposes the public disk. Replaced/removed files are deleted only if their path matches the generated flat avatar directory; arbitrary paths are never removed. Initials remain the fallback. Uploads and the public storage link are ignored by Git.

Account deletion follows the existing foreign-key rules: profile and its equipment, bookmarks, votes and reports cascade on deleting their owner. Shared advice, categories, outages, neighborhoods, equipment types and cooling points are retained. Sessions and password reset tokens have no user foreign key and are explicitly removed in the transaction. Logout occurs while the User model still exists so remember-token rotation cannot save a deleted model. The session is invalidated, its CSRF token regenerated and the avatar cleaned after successful deletion. No new destructive cascade or retention rule was introduced.

The sole Auth migration adds nullable `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` and `avatar_path` to users. It does not duplicate `email_verified_at` or modify previously applied migrations. Apply migrations normally; never reset the real database to demonstrate authentication.

## Local mail and demonstration

SMTP credentials belong only in the ignored local `.env`. The neutral `.env.example` uses port 587, STARTTLS with TLS required and a 20-second timeout. Laravel 12 uses `MAIL_SCHEME=smtp` with STARTTLS; no password is embedded in PHP, docs or tests. After changing mail settings, clear the configuration cache. Use the `array`/`log` mailer for environments that should not send real messages.

To demonstrate: register, open the verification email, sign in, confirm the Verified badge and the USER refusal on `/admin`, request and complete a password reset, then open Account → confirm password → Enable 2FA. Scan the QR with an authenticator app and confirm a code. Store the recovery codes safely. Log out and sign in again to demonstrate the challenge. Account information, picture, password and the danger zone remain distinct from the household profile.

Automated feature tests use SQLite in memory and fake mail/storage; they never reset or delete the local MySQL business data. Coverage includes registration forgery, role enforcement, session rotation, login/challenge/verification throttles, signed link rejection/expiry, reset token expiry/consumption, account updates, secure uploads, TOTP and recovery flows, password confirmation and deletion foreign keys. Real SMTP and HTTP checks must be run separately against the local application.

## Dependency and formatting notes

The Auth integration upgrades the Pest/PHPUnit development toolchain to resolve the previously documented PHPUnit advisory (PHPUnit 12.5.33). This includes the required tokenizer 2.x transition and has been checked with the complete suite on PHP 8.3. Composer audit reports no advisory at this revision. The earlier dependency audit remains a historical record of the previous versions.

The existing low-severity Quill 2.0.3 HTML-export advisory is unchanged: the advice editor uses validated Delta JSON and does not use HTML export. See `dependency-security-audit.md` for its scope. The application still declares Node 22.x; local validation used the installed Node 24.21.0. Existing unrelated Pint style issues are retained; newly added PHP files pass Pint.
