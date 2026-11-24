# Gather — event management

A small, plain-PHP event app: organizers create and publish events, anyone can RSVP for free,
capacity spills into a waitlist, and organizers get an attendee list, CSV export and a door
check-in screen.

Full product and milestone plan: `plan.md`. (Framework decision changed: **plain PHP 8.5 + PDO**,
not Laravel — the app is a tiny front controller, plain PHP views and Tailwind via Vite.)

## Run it

```sh
php bin/console migrate        # apply database/schema.sql
php bin/console seed           # demo organizer + events + guests
php -S localhost:8000 -t public   # the preview
npm run dev                    # or: npm run build -- --watch (compiled assets)
```

- Demo sign-in: `demo@event.test` / `password`.
- With `VITE_DEV=true` the pages use the Vite dev server on `:5173`; with it off they use the
  hashed build in `public/build`.
- The same files are served by Apache at `http://event.test` via `yard serve`.

## Where things are

| What | Where |
|---|---|
| Front controller (every request) | `public/index.php` |
| Bootstrap: env, autoload, session, errors | `app/bootstrap.php` |
| Helpers (`view`, `url`, `csrf_field`, date/timezone, Vite tags) | `app/helpers.php` |
| Router / request dispatch | `app/Router.php`, `routes/web.php` |
| PDO wrapper and transactions | `app/Database.php` |
| Auth, CSRF, validation, throttling | `app/Auth.php`, `app/Csrf.php`, `app/Validator.php`, `app/RateLimiter.php` |
| Organizer ownership rule | `app/EventPolicy.php` |
| Capacity + waitlist (the only place that decides) | `app/Services/RegistrationService.php` |
| Models (plain arrays from PDO) | `app/Models/{User,Event,Registration,PasswordReset}.php` |
| Controllers | `app/Http/Controllers/` |
| Views (plain PHP) | `views/`, layouts in `views/layouts/` |
| Schema | `database/schema.sql` |
| CLI | `bin/console` |
| Tests | `php tests/run.php` (uses the separate `event_test` DB) |

## Hard rules

- **Capacity logic lives in `RegistrationService` only.** It runs inside a transaction with
  `SELECT ... FOR UPDATE` on the event row; don't compute seats anywhere else.
- **Registrations are never hard-deleted.** Cancel sets `status = cancelled` and `cancelled_at`,
  which frees the seat and promotes the oldest waitlisted guest.
- **Times:** store UTC `DATETIME`, keep the event's IANA zone in `events.timezone`, render with
  `fmt_datetime()`. Never display raw UTC to a guest.
- **Writes** always go through CSRF, `Validator`, and (for organizer routes) `EventPolicy`.
- **No secrets in the repo.** `.env` is ignored; keep `.env.example` current.
- Email is `MAIL_MAILER=log` locally — full messages land in `storage/logs/mail.log`.
- No payment/ticketing code until the free RSVP flow has run for a real event (see `plan.md` §8).

## Checks

```sh
php tests/run.php
php bin/console send-reminders
php bin/console status
```

Logs: `storage/logs/php-server.log`, `storage/logs/mail.log`, `storage/logs/php-error.log`,
and `/var/log/apache2/event.test-error.log` for the Apache route.
