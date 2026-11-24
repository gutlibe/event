# Event Management System — Plan and Build Status

v1.1 · 24 Nov 2026 · lives at `~/Yard/event`
Preview: `http://localhost:8000` (`php -S localhost:8000 -t public`), also served at `http://event.test`.

This started as a Laravel-shaped plan. It now describes what was actually built: a small plain-PHP app
with PDO, server-rendered views and Tailwind via Vite. Setup and commands live in `README.md`;
code-level conventions live in `CLAUDE.md`.

---

## 1. What we're building

A web app where organizers create events, publish a public page, and people register.
v1 is **free RSVP**, not ticketing with payments.

**Built in v1**

- Organizer accounts: sign up, sign in, sign out, reset password by email link.
- Create / edit / publish / cancel an event (title, description, venue, dates, capacity, cover image).
- Public event page at `/e/{slug}` with local times, capacity and calendar download.
- Registration form (name, email, optional phone) with a confirmation email and unique ticket reference.
- Capacity limit with automatic waitlist; cancelling a spot promotes the oldest waitlisted person.
- Attendee list, CSV export, and a check-in screen (search by name/email/reference).

**Later (v2+)**

- Paid tickets (Paystack/Stripe), refunds, multiple ticket types and prices.
- Recurring events, multi-session agendas, speaker pages.
- QR code check-in with a phone camera.
- Reminder scheduling beyond the T-24h command, calendar-invite options, embeddable widget.
- Organizer team members (staff with restricted access), analytics.

**Not a goal**

- An incident/telemetry "events" pipeline. This is people-and-calendars events only.
- Native mobile app.

---

## 2. Environment (verified on this machine)

| Piece | State |
|---|---|
| PHP | 8.5.4 CLI and PHP-FPM, `/usr/bin/php` |
| Extensions | `pdo_mysql`, `mbstring`, `intl`, `gd`, `fileinfo`, `openssl` |
| Database | MariaDB 11.8.6, local, InnoDB, utf8mb4 |
| DB access | dedicated `event` database; app connects as `root` on `127.0.0.1` with the local password |
| Web server | Apache 2.4.66 + PHP-FPM at `event.test`, or PHP's built-in server on port 8000 |
| Node | 26 + npm 11 (Vite/Tailwind only) |
| Logs | `storage/logs/php-server.log`, `storage/logs/mail.log`, `storage/logs/php-error.log` |

MariaDB is a shared local instance. The app owns the `event` database (and `event_test` for tests);
do not run `DROP DATABASE` or global grants.

---

## 3. Stack

**Plain PHP 8.5 + PDO + server-rendered PHP views + Tailwind 4 via Vite 8, MariaDB.**

Why not a framework: the surface is small — a front controller, a route table, sessions, CSRF,
validation and a log mailer. Plain PHP keeps the app readable and dependency-free, and matches the
"no build step for the visitor" goal. The trade-off is that auth, CSRF, validation and mail are
hand-rolled; they are centralised and covered by the test suite.

| Need | Choice |
|---|---|
| Routing | `app/Router.php` + `routes/web.php` (method + `{param}` patterns, auth/guest middleware) |
| Templates | plain PHP files in `views/`, layouts via `view()` / `render()` |
| Database | PDO (`app/Database.php`), prepared statements, transactions |
| Auth / sessions | PHP sessions, `password_hash`, custom reset tokens (`app/Auth.php`, `Models/PasswordReset.php`) |
| Validation | `app/Validator.php` with rule strings, `ValidationException` |
| Throttling | `app/RateLimiter.php` backed by `rate_limits` |
| Styling | Tailwind CSS 4 + Vite 8 (`resources/css/app.css`, `resources/js/app.js`) |
| Images | GD resize to 1600px wide, saved as JPEG |
| Mail | hand-built MIME messages written to `storage/logs/mail.log` (`app/Mailer.php`); no SMTP or queue yet |
| Calendar | `app/Ics.php` with `VTIMEZONE` |
| Tests | `tests/run.php`, a dependency-free runner against `event_test` |

PHP/DB conventions: `utf8mb4` / `utf8mb4_unicode_ci`; timestamps in UTC, per-event IANA zone in
`events.timezone`; backed enums for event and registration status; all writes go through CSRF +
validation + ownership checks.

---

## 4. Data model

The schema is `database/schema.sql` and is applied by `php bin/console migrate`. It is safe to re-run.

### users

```sql
CREATE TABLE users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  password      VARCHAR(255)  NOT NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Events are scoped by `user_id`; organizer teams come later as an `event_members` pivot.

### events

```sql
CREATE TABLE events (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL,
  slug          VARCHAR(190) NOT NULL UNIQUE,
  title         VARCHAR(160) NOT NULL,
  summary       VARCHAR(255) NULL,
  description   MEDIUMTEXT NULL,
  cover_image   VARCHAR(255) NULL,
  venue_name    VARCHAR(160) NULL,
  venue_address VARCHAR(255) NULL,
  timezone      VARCHAR(64)  NOT NULL DEFAULT 'UTC',
  starts_at     DATETIME NOT NULL,
  ends_at       DATETIME NULL,
  capacity      INT UNSIGNED NULL,            -- NULL = unlimited
  status        ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft',
  published_at  DATETIME NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL,
  CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_events_feed (status, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### registrations

```sql
CREATE TABLE registrations (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id         BIGINT UNSIGNED NOT NULL,
  reference        CHAR(12) NOT NULL UNIQUE,   -- e.g. EVT-7K2QX9
  name             VARCHAR(120) NOT NULL,
  email            VARCHAR(190) NOT NULL,
  phone            VARCHAR(32)  NULL,
  status           ENUM('confirmed','waitlisted','cancelled') NOT NULL DEFAULT 'confirmed',
  checked_in_at    DATETIME NULL,
  cancelled_at     DATETIME NULL,
  reminder_sent_at DATETIME NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  UNIQUE KEY uq_reg_event_email (event_id, email),
  KEY idx_reg_list (event_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`password_resets` (email, token hash, expiry) and `rate_limits` (bucket, hits, resets_at) complete
the v1 schema. `ticket_types` below stays v2.

### ticket_types (v2, not created)

```sql
CREATE TABLE ticket_types (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id     BIGINT UNSIGNED NOT NULL,
  name         VARCHAR(80) NOT NULL,          -- General, VIP, Student
  price_cents  INT UNSIGNED NOT NULL DEFAULT 0,
  quantity     INT UNSIGNED NULL,             -- NULL = limited only by event capacity
  position     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_tt_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Rules the schema encodes

- **One registration per email per event** (`uq_reg_event_email`). A cancelled guest who re-registers
  updates the existing row instead of creating a second ticket.
- **Cancelling frees a seat** — `cancelled` rows never count toward capacity.
- **Waitlist order is `created_at`, then `id`**, so promotion is deterministic.
- **No hard deletes** — cancel sets `status` + `cancelled_at`; the row and reference stay.

### Capacity and the race

Two people submitting the last seat at the same instant must not both get in.
`app/Services/RegistrationService.php` is the only place that decides:

```php
Database::transaction(function () use ($event, $data) {
    $locked = Database::first('SELECT * FROM events WHERE id = ? FOR UPDATE', [$event['id']]);

    $confirmed = (int) Database::value(
        "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'confirmed'",
        [$locked['id']]
    );

    $status = ($locked['capacity'] === null || $confirmed < (int) $locked['capacity'])
        ? 'confirmed'
        : 'waitlisted';

    // insert a new row, or revive the cancelled row for this email
});
```

The InnoDB row lock on the event serialises the count. Do not move this logic into controllers.

---

## 5. Status by milestone

**M0 — Scaffold and environment — built**

- Plain-PHP front controller (`public/index.php`), `.htaccess` rewrite and a built-in-server route.
- Dedicated `event` database, `.env` loader, `php bin/console migrate` / `seed`.
- Tailwind + Vite build, Apache at `event.test` and `php -S localhost:8000 -t public`.

**M1 — Organizer accounts — built**

- Register, sign in, sign out, forgot/reset password (reset link written to `storage/logs/mail.log`).
- Session regeneration, HttpOnly/SameSite cookies, CSRF on all writes, login throttling.

**M2 — Event CRUD and publish — built**

- Create/edit/publish/cancel, cover upload (JPEG/PNG/WebP, resized to 1600px).
- Validation: title 3–160, future start required to publish, `ends_at > starts_at`, capacity ≥ 1 or empty,
  valid IANA timezone.
- `EventPolicy` restricts every `/events/*` route to the owner; slugs are unique (`-2`, `-3` suffixes).

**M3 — Public page and registration — built**

- Drafts 404; cancelled events keep their page with a banner and closed registration.
- RSVP validation, per-IP throttle (5/min), unique reference, confirmation email with `.ics`.
- Seats-left updates immediately; confirmation page offers calendar download and cancellation.

**M4 — Capacity and waitlist — built**

- The transaction in §4; waitlist copy shows the guest's position.
- Cancelling promotes the oldest waitlisted guest and emails them.
- Covered by tests: capacity 2 accepts two and waitlists the third; cancelling promotes them.

**M5 — Attendee list, export, check-in — built**

- Searchable/filterable attendee table with confirmed / waitlist / checked-in counts.
- CSV streams from a query with a UTF-8 BOM: name, email, phone, reference, status, checked in, booked at.
- Check-in screen searches any part of name/email/phone/reference; check-in is idempotent and undoable.

**M6 — Reminders, ICS, polish — code complete, cron not installed**

- `php bin/console send-reminders` emails T-24h reminders to confirmed guests (`reminder_sent_at` guard).
- `.ics` includes `TZID` and `VTIMEZONE`; attached to confirmation, promotion and reminder emails.
- Empty states, 404/403/419/500 pages, flash messages and mobile layouts are in place.
- Remaining: add the cron entry, and verify a reminder end to end in `storage/logs/mail.log`.

**M7 — Hardening — mostly done**

- `php tests/run.php` covers capacity/promotion, duplicate registrations, re-registration,
  validation, slug collisions, ownership checks, check-in idempotency, timezones and `.ics`.
- Login/registration throttling; security headers from the PHP bootstrap; friendly 500 page.
- Remaining: nightly local `mysqldump` backup (keep 7), cover-upload and password-reset end-to-end
  checks, and a pass with `APP_DEBUG=false`.

**M8 — v2 backlog — not scheduled**

Ticket types and prices, Paystack checkout and refunds, QR check-in, team members, embeddable widget,
organizer analytics, custom email branding, recurring events.

---

## 6. Project structure

```
~/Yard/event/
├── app/
│   ├── bootstrap.php         env loading, autoload, session, error handling
│   ├── helpers.php           view(), url(), csrf_field(), dates, Vite tags
│   ├── Router.php            route table and middleware
│   ├── Database.php          PDO wrapper and transactions
│   ├── Auth.php              sessions and credentials
│   ├── Csrf.php  Validator.php  RateLimiter.php
│   ├── EventPolicy.php       owner-only access
│   ├── Services/             RegistrationService.php  ← capacity + waitlist logic
│   ├── Models/               User, Event, Registration, PasswordReset
│   ├── Enums/                EventStatus, RegistrationStatus
│   └── Http/Controllers/     Auth, Dashboard, Event, PublicEvent, Attendee, CheckIn
├── resources/
│   ├── css/app.css           Tailwind entry and design tokens
│   └── js/app.js             drawer, copy buttons, confirmations
├── views/
│   ├── layouts/              public, auth, app (admin shell)
│   ├── events/               index, form, show
│   ├── public/               event, registered
│   ├── attendees/            index, checkin
│   ├── auth/                 login, register, forgot, reset
│   ├── emails/               registration, reminder, reset
│   └── errors/               404, 403, 419, 500
├── database/schema.sql
├── public/
│   ├── index.php             front controller
│   ├── media/                demo cover art
│   └── build/                generated by Vite (git-ignored)
├── routes/web.php
├── tests/run.php
├── bin/console
├── plan.md  README.md  CLAUDE.md
└── storage/logs/             mail.log, php-server.log, php-error.log
```

**URL map**

| URL | Who | What |
|---|---|---|
| `/` | anyone | upcoming published events |
| `/e/{slug}` | anyone | public event page + registration |
| `/e/{slug}/registered/{reference}` | anyone with link | ticket / cancel |
| `/e/{slug}/calendar.ics` | anyone | calendar download |
| `/register`, `/login`, `/forgot-password`, `/reset-password` | organizers | auth |
| `/dashboard` | organizer | own events and totals |
| `/events/*` | organizer | CRUD, publish, cancel |
| `/events/{id}/attendees` | owner | list, cancel, CSV |
| `/events/{id}/check-in` | owner | door screen |

Keep `RegistrationService` as the only place that computes capacity.

---

## 7. Conventions

- Commands: `php bin/console migrate|seed|fresh|send-reminders|status`, `php tests/run.php`,
  `npm run dev|build`.
- Commits: `type(scope): summary`, lowercase, imperative, under ~72 chars, one line, no body.
- Stage and commit separately; never `git add -A && git commit`.
- No AI attribution in commits, PRs or comments.
- No secrets in the repo; `.env` is ignored, `.env.example` kept current.
- Capacity changes need a test that fails first.
- Errors go to `storage/logs/` (and the Apache error log for the `event.test` route).

---

## 8. Risks and standing rules

| Risk | Mitigation |
|---|---|
| Overbooking on the last seat | `SELECT … FOR UPDATE` inside `RegistrationService`, tested |
| Timezone bugs on event times | UTC storage + per-event IANA zone; `.ics` carries `VTIMEZONE` |
| Duplicate registrations / spam | unique `(event_id, email)`, per-IP throttle, email confirmation |
| Sending real email by accident | `MAIL_MAILER=log`; no SMTP transport wired up |
| Local MariaDB shared with other apps | dedicated `event` / `event_test` databases, no global grants |
| Reminder job never runs | cron entry still to add; verify with `php bin/console send-reminders` |
| Scope creep into ticketing | payments stay in M8 until free RSVP has run for a real event |

**Standing rules**

- No payment code until the RSVP flow has run for a real event.
- Registrations are never hard-deleted; cancel sets `status` + `cancelled_at`.
- Any change that touches capacity logic needs a test that fails first.
- Timezone-aware display only; never show raw UTC to a guest.

---

## 9. Open decisions

1. **Framework** — resolved: plain PHP 8.5 + PDO (see §3), not Laravel.
2. **Tenancy** — many organizer accounts, one installation, events scoped by `user_id`. Confirm if it
   is actually single-organizer.
3. **Branding** — "Gather" is the working name, used in the UI and emails. Confirm the final name,
   logo and sender address.
4. **Deploy** — out of scope for now; the app runs locally on `event.test` and `localhost:8000`.
5. **Email** — the log mailer is enough while testing locally; a real transport is needed before
   inviting actual guests.
