# Gather

A small event management app for free RSVPs. Organizers create and publish an event, guests register
from a public page, capacity spills into a waitlist, and the organizer gets an attendee list, CSV
export and a door check-in screen.

Built with **plain PHP 8.5 + PDO + MariaDB** and **Tailwind CSS 4 via Vite**. No framework, no build
step for visitors — just server-rendered PHP and one small JS file.

Product plan and milestones: [`plan.md`](plan.md). Project conventions and architecture notes:
[`CLAUDE.md`](CLAUDE.md).

---

## Features

**Organizers**

- Email/password accounts, password reset by email link.
- Create, edit, publish and cancel events (title, summary, description, venue, times, capacity, cover image).
- Draft events are invisible to the public; cancelled events keep their page but close registration.
- Dashboard with guest, waitlist and check-in totals.
- Attendee list with search and status filters, one-click cancellation, CSV export (UTF-8 with BOM).
- Door check-in screen: search by name, email, phone or ticket reference; idempotent check-ins with undo.
- Public share link with copy-to-clipboard.

**Guests**

- Public event page with local times, venue, capacity and cover image.
- Free RSVP (name, email, optional phone) with a unique ticket reference.
- Automatic waitlist when the event is full; cancellation promotes the oldest waitlisted guest.
- Ticket page to check status, cancel, or download a calendar invite (`.ics`).
- Confirmation, waitlist, promotion and T-24h reminder emails.

**Under the hood**

- Capacity decisions run inside a `SELECT … FOR UPDATE` transaction, so two people cannot take the last seat.
- UTC storage with a per-event IANA timezone for display and calendar files.
- CSRF on every write, server-side validation, per-IP throttling on login and registration.
- No hard deletes: cancellations keep the row and clear the seat.
- Registrations are unique per `(event, email)`; a cancelled guest can re-register on the same row.

---

## Requirements

| Piece | Version used |
|---|---|
| PHP | 8.5 with `pdo_mysql`, `mbstring`, `intl`, `gd`, `fileinfo` |
| Database | MariaDB 11.8 (MySQL 8 works) |
| Node | 26 (for Vite/Tailwind only) |

## Setup

```sh
# 1. Database
mysql -u root -p -e "CREATE DATABASE event CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 2. Environment
cp .env.example .env
# set DB_PASSWORD (and APP_URL if you are not using localhost:8000)

# 3. Schema and demo data (optional but handy)
php bin/console migrate
php bin/console seed

# 4. Front-end dependencies and assets
npm install
npm run build          # or: npm run build -- --watch while styling

# 5. Run it
php -S localhost:8000 -t public
```

Open **http://localhost:8000**.

Demo organizer after seeding: **`demo@event.test` / `password`**.
The seed creates live, draft and cancelled events, a full event with a waitlist, and a past event
with checked-in guests.

### Live styling

- `npm run build -- --watch` recompiles when `resources/css` or `resources/js` change — refresh the page.
- Or set `VITE_DEV=true` in `.env` and run `npm run dev`; pages then load assets from the Vite dev
  server on port 5173 (with HMR) instead of `public/build`.

### Apache / the Yard

If this folder sits in `~/Yard`, `yard serve` exposes it at `http://event.test` with `public/` as the
document root. `.htaccess` routes everything through `public/index.php`. The app works on both
`event.test` and `localhost:8000`; set `APP_URL` to whichever you use for absolute links in emails.

---

## Commands

| Command | What it does |
|---|---|
| `php bin/console migrate` | Apply `database/schema.sql` (safe to re-run). |
| `php bin/console seed` | Create demo organizer, events and registrations (skips if users exist). |
| `php bin/console fresh --force` | Drop the app's tables, re-migrate and re-seed. Dev only. |
| `php bin/console send-reminders` | Email T-24h reminders to confirmed guests. |
| `php bin/console status` | Quick row counts. |
| `php tests/run.php` | Test suite, against a separate `event_test` database. |
| `npm run dev` / `npm run build` | Tailwind/Vite dev server / compiled assets. |

## URL map

| URL | Who | What |
|---|---|---|
| `/` | anyone | Upcoming published events |
| `/e/{slug}` | anyone | Public event page + RSVP form |
| `/e/{slug}/registered/{reference}` | guest with link | Ticket, status, cancel |
| `/e/{slug}/calendar.ics` | anyone | Calendar download |
| `/register`, `/login`, `/forgot-password`, `/reset-password` | organizers | Auth |
| `/dashboard` | organizer | Stats and own events |
| `/events`, `/events/create`, `/events/{id}`, `/events/{id}/edit` | organizer | Event management |
| `/events/{id}/attendees` | owner | Search, cancel, CSV export |
| `/events/{id}/check-in` | owner | Door screen |

Every `/events/*` route checks ownership; another organizer gets a 403.

---

## Project structure

```
app/
  bootstrap.php            env loading, autoload, session, error handling
  helpers.php              view(), url(), csrf_field(), date/timezone, Vite tags
  Router.php               tiny route table + middleware (auth/guest)
  Database.php             PDO wrapper, selects, transactions
  Auth.php  Csrf.php  Validator.php  RateLimiter.php
  EventPolicy.php          owner-only access
  Services/RegistrationService.php   ← capacity, waitlist, promotion, emails
  Models/                  User, Event, Registration, PasswordReset
  Http/Controllers/        Auth, Dashboard, Event, PublicEvent, Attendee, CheckIn
views/                     plain PHP templates (layouts, auth, events, public, attendees, emails)
routes/web.php             the whole route table
database/schema.sql        tables and indexes
public/index.php           front controller; public/build is generated by Vite
bin/console                CLI commands
tests/run.php              dependency-free test runner
```

The schema deliberately mirrors `plan.md` §4: `users`, `events`, `registrations`, `password_resets`
and `rate_limits` (plus a `reminder_sent_at` column on registrations).

---

## Behaviour worth knowing

**Capacity and waitlist.** `RegistrationService::register()` locks the event row, counts confirmed
guests, and decides `confirmed` vs `waitlisted`. Cancelling locks the event again and promotes the
oldest waitlisted guest (`created_at`, then `id`), emailing them. These are the only places that
compute seats.

**Timezones.** `starts_at` / `ends_at` are stored as UTC `DATETIME`; the event's IANA zone lives in
`events.timezone`. Views render through `fmt_datetime()` / `utc_to_local()`. The `.ics` file includes
a `VTIMEZONE` block.

**Email.** Local mail is `MAIL_MAILER=log`: complete MIME messages (including `.ics` attachments) are
appended to `storage/logs/mail.log`. Password-reset links are printed there too. There is no SMTP
transport in this build.

**Security.** CSRF on every POST (including spoofed PUT/DELETE), HttpOnly/SameSite session cookie,
password hashing, login and registration throttling, ownership policy on organizer routes,
`X-Content-Type-Options` / `X-Frame-Options` / `Referrer-Policy` headers. Cover uploads are limited
to 4MB JPG/PNG/WebP and resized to 1600px wide with GD.

**Tests.** `php tests/run.php` covers capacity and waitlist promotion, duplicate registrations,
re-registration after cancellation, validation, slug collisions, ownership checks, check-in
idempotency, timezone rendering and `.ics` output. It uses `event_test`, so dev data is untouched.

---

## Environment

| Key | Default | Notes |
|---|---|---|
| `APP_NAME` | `Gather` | Used in the UI and emails. |
| `APP_ENV` | `local` | |
| `APP_DEBUG` | `true` | `false` shows the friendly 500 page instead of a stack trace. |
| `APP_URL` | `http://localhost:8000` | Absolute links in emails and `.ics`. |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | | MariaDB connection. |
| `MAIL_MAILER` | `log` | Anything other than `log` currently suppresses mail (tests use this). |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | | Sender shown in emails. |
| `SESSION_NAME` | `event_session` | |
| `VITE_DEV` | `false` | `true` loads assets from the Vite dev server. |
| `VITE_PORT` | `5173` | Dev server port. |

---

## Scheduled reminders

`send-reminders` looks for published events starting in 23–25 hours and emails confirmed guests
with `reminder_sent_at IS NULL`. With the log mailer it writes to `storage/logs/mail.log`.

```cron
0 * * * * cd /path/to/event && php bin/console send-reminders >> storage/logs/reminders.log 2>&1
```

---

## Not built yet (v2 backlog)

Paid tickets and refunds (Paystack/Stripe), QR-code check-in, multiple ticket types, recurring
events, organizer teams, embeddable widget, analytics, custom email branding. Payments stay out
until the free RSVP flow has run for a real event — see `plan.md` §8.
