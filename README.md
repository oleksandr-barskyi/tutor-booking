# Tutor Booking

Book an online lesson with a tutor: pick a tutor, a day and a free hour, leave your name
and email, get a confirmation. **Laravel 13** API with a **React** front end built by Vite.

## What it does

- Tutors have subjects, an hourly rate, a timezone and weekly working hours.
- `GET /api/tutors/{id}/slots?date=` turns working hours into free one-hour slots for that
  day in the tutor's timezone, minus booked lessons and anything starting within the next hour.
- `POST /api/bookings` books a slot and returns a cancellation token; a queued job sends the
  confirmation email after the transaction commits.
- `DELETE /api/bookings/{id}?token=` cancels up to 12 hours before the start and frees the slot.

## Design choices

- **Double booking is impossible at the database level.** An active booking stores its start
  in `active_slot`, and `(tutor_id, active_slot)` is unique; a cancelled booking sets it to
  `NULL`, so the slot can be booked again. The service still checks first and turns a lost
  race (`UniqueConstraintViolationException`) into `409 Conflict`.
- **Time is stored in UTC, shown in the tutor's timezone.** `SlotCalculator` is a plain class
  with unit tests, including the daylight saving change at the end of October.
- **Thin controllers.** Validation in a Form Request, rules in `BookingService`, output through
  API Resources; a domain exception renders its own 409 response.
- **Side effects after commit.** `SendBookingConfirmation::dispatch()->afterCommit()` never
  emails about a booking that was rolled back, and it re-checks the booking status.

```
app/Booking/           SlotCalculator, BookingService, SlotUnavailable
app/Http/              controllers, StoreBookingRequest, resources
app/Jobs, app/Mail     confirmation job and mailable
resources/js/          React app; booking.js holds UI logic with Vitest tests
tests/                 PHPUnit unit and feature tests
```

## Tests

17 PHPUnit tests (slot maths across timezones and DST, the API end to end with frozen time,
conflicts, the unique constraint itself, validation, cancellation rules, the confirmation
email) and 5 Vitest tests for the front-end logic.

## Run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
php artisan test
npm test
```

Stack: PHP 8.4, Laravel 13, Eloquent, queues, mail, React 19, Vite, Tailwind CSS v4,
PHPUnit, Vitest, Pint.
