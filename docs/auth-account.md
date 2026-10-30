# Authentication and account settings

HeatAlert remains one Laravel 12 application with its own Blade components. Laravel Fortify provides authentication, registration, password reset, signed email verification, password confirmation and TOTP two-factor authentication. Passkeys and Spatie Permission are not enabled.

## Identity and authorization

- Registration accepts name, email and a confirmed password of at least eight characters. The server always assigns `USER`; submitted roles, verification dates and two-factor secrets are ignored. `role` is deliberately absent from `User::$fillable`: it is only set explicitly (`forceFill`) by registration, the local demo seeder or trusted admin code, so mass assignment can never promote an account to `ADMIN`.
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

Pictures are stored under `storage/app/public/avatars` with generated names. Only JPEG, PNG and WebP images up to 2 MB are accepted; SVG is rejected. Upload and removal require `auth`, a verified email and `throttle:10,1`, keep the strict image validation, and only ever modify the signed-in user's own `avatar_path`. `php artisan storage:link` exposes the public disk. Replaced/removed files are deleted only if their path matches the generated flat avatar directory; arbitrary paths are never removed. Initials remain the fallback, and the same `x-ha.avatar` component is used on `/account` and in the `/my-profile` header.

Password confirmation is intentionally not added to avatar upload/removal: Laravel's `password.confirm` flow redirects back with a `GET`, which cannot resume a `POST`/`DELETE` upload form without an incoherent intermediate page. The upload route is therefore protected by authentication, verified email, CSRF, strict server-side file validation and per-user throttling; the destructive account operations (2FA and account deletion) keep `password.confirm`. Uploads and the public storage link are ignored by Git.

Account deletion follows the existing foreign-key rules: profile and its equipment, bookmarks, votes and reports cascade on deleting their owner. Shared advice, categories, outages, neighborhoods, equipment types and cooling points are retained. Sessions and password reset tokens have no user foreign key and are explicitly removed in the transaction. Logout occurs while the User model still exists so remember-token rotation cannot save a deleted model. The session is invalidated, its CSRF token regenerated and the avatar cleaned after successful deletion. No new destructive cascade or retention rule was introduced.

The sole Auth migration adds nullable `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` and `avatar_path` to users. It does not duplicate `email_verified_at` or modify previously applied migrations. Apply migrations normally; never reset the real database to demonstrate authentication.

## Account vs household profile

`/account` is about identity and security: name, e-mail, password, avatar, two-factor authentication and account deletion. `/my-profile` is the household business record: phone, address, neighborhood, fragile person, sensitive equipment, the readiness checklist and the AI advisor. Each page links to the other so the two areas stay explicit.

## AI household risk advisor (Module 5)

The resident card "Analyse intelligente du risque du foyer" lives on `/my-profile`. It is a Module 5-only feature built on `Profile` + `SensitiveEquipment`; it never reuses the advice assistant, Gemini narration, alert explainer or any other module AI.

Pipeline:

1. `HouseholdRiskService` computes a deterministic local score from data that already exists (fragile person, equipment type risk, heat/outage sensitivity, equipment count).
2. `MistralHouseholdAdvisor` sends only the anonymised business payload to the official Mistral chat-completions endpoint (`https://api.mistral.ai/v1/chat/completions`) with a strict JSON schema.
3. Laravel validates and sanitises the answer; the score and level always remain the local ones.
4. `HouseholdRiskAiController` returns a `no-store` JSON response rendered by `resources/js/household-risk.js` with `textContent` only.

Score weights: fragile person +30; each equipment +2; type risk critical +20 / high +15 / medium +8 / low +3; sensitive to heat +5; sensitive to outages +5; the result is capped at 100. Thresholds: LOW 0-24, MEDIUM 25-59, HIGH 60-100. The score describes "heat/outage exposure and preparation needs"; it is not a medical probability of an accident.

Route: `POST /my-profile/ai-risk-analysis` (`profile.ai-risk.analyze`), middleware `auth`, `verified`, `throttle:5,1`. The controller always uses `$request->user()->profile` and ignores any client-provided `profile_id`/`user_id`. A resident without a profile gets a 422 message and no provider call. A profile without equipment still gets a complete local assessment (no invented equipment).

Mistral configuration lives in the ignored local `.env` only: `MISTRAL_API_KEY` and `MISTRAL_MODEL`. The default model is `ministral-8b-latest`: it is the light, current Mistral model that accepted the demo key and custom structured outputs (`mistral-small-latest` answered 429 for this key). `mistral-small-latest` remains a valid alias when a plan allows it. No key is committed, logged, sent to the browser or included in tests; `.env.example` keeps an empty placeholder.

Data minimisation: only the boolean fragile-person flag, profile completeness, equipment count and, per equipment, the type name, risk level, heat/outage sensitivity and whether a note exists are sent. Names, e-mail, phone, address, neighborhood, equipment labels, free-text notes, identifiers, tokens and secrets never leave the application.

Mistral only produces `summary`, `risk_factors` and ordered `priority_actions`. The system prompt forbids changing the score, inventing equipment or autonomy, medical diagnosis, safety guarantees and personal data, and requires French, short actionable advice and strict JSON. The reply is rejected (and the local fallback shown) on unexpected keys, wrong types, oversized strings, more than five factors/actions, invalid priorities, HTML/script (stripped as defence in depth), invalid JSON, `finish_reason != stop`, 401/403, 429, 5xx, network errors, timeouts or a missing key. Failures return HTTP 200 with `ai: null`, the local score/level/factors and deterministic local actions plus "L'analyse IA est temporairement indisponible."; nothing is logged except a reason code, HTTP status, model and duration.

No `ai_analyses` table exists and no AI content is stored: the analysis is computed on demand, so there is no new retention rule. Automated tests fake the HTTP client and never consume the real quota.

To demonstrate: sign in as a verified resident, open `/my-profile`, click "Analyser mon foyer". The card shows the local score out of 100, LOW/MEDIUM/HIGH, the local factors, the Mistral summary, its risk factors and priority actions. Remove `MISTRAL_API_KEY` (or simulate a 429/timeout) to demonstrate the local fallback. The card footer recalls that AI advice is preventive only and does not replace authorities or professionals.

## Local mail and demonstration

SMTP credentials belong only in the ignored local `.env`. The neutral `.env.example` uses port 587, STARTTLS with TLS required and a 20-second timeout. Laravel 12 uses `MAIL_SCHEME=smtp` with STARTTLS; no password is embedded in PHP, docs or tests. After changing mail settings, clear the configuration cache. Use the `array`/`log` mailer for environments that should not send real messages.

To demonstrate: register, open the verification email, sign in, confirm the Verified badge and the USER refusal on `/admin`, request and complete a password reset, then open Account → confirm password → Enable 2FA. Scan the QR with an authenticator app and confirm a code. Store the recovery codes safely. Log out and sign in again to demonstrate the challenge. Account information, picture, password and the danger zone remain distinct from the household profile.

Automated feature tests use SQLite in memory and fake mail/storage; they never reset or delete the local MySQL business data. Coverage includes registration forgery, role mass-assignment refusal, session rotation, login/challenge/verification throttles, signed link rejection/expiry, reset token expiry/consumption, account updates, secure uploads, avatar authentication/verification/throttling, TOTP and recovery flows, password confirmation and deletion foreign keys. `HouseholdRiskAiTest` fakes the Mistral HTTP client and covers guests, unverified users, missing profiles, empty and populated equipment lists, PII minimisation, deterministic LOW/MEDIUM/HIGH scores, valid and invalid JSON, unexpected structures, timeouts, 401/429/5xx, missing keys, throttling, cross-household identifiers and HTML injection. Real SMTP, MySQL and one Mistral smoke call must be run separately against the local application.

## Dependency and formatting notes

The Auth integration upgrades the Pest/PHPUnit development toolchain to resolve the previously documented PHPUnit advisory (PHPUnit 12.5.33). This includes the required tokenizer 2.x transition and has been checked with the complete suite on PHP 8.3. Composer audit reports no advisory at this revision. The earlier dependency audit remains a historical record of the previous versions.

The existing low-severity Quill 2.0.3 HTML-export advisory is unchanged: the advice editor uses validated Delta JSON and does not use HTML export. See `dependency-security-audit.md` for its scope. The application still declares Node 22.x; local validation used the installed Node 24.21.0. Existing unrelated Pint style issues are retained; newly added PHP files pass Pint.
