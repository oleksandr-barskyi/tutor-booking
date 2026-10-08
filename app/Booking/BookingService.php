<?php

namespace App\Booking;

use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\Tutor;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BookingService
{
    public const CANCEL_CUTOFF_HOURS = 12;

    public function __construct(private readonly SlotCalculator $slots) {}

    public function freeSlots(Tutor $tutor, string $date, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $weekday = CarbonImmutable::parse($date, $tutor->timezone)->isoWeekday();

        $windows = $tutor->workingHours
            ->where('weekday', $weekday)
            ->map(fn ($h) => [substr($h->starts_at, 0, 5), substr($h->ends_at, 0, 5)])
            ->values()
            ->all();

        $dayStart = CarbonImmutable::parse($date, $tutor->timezone)->startOfDay()->utc();
        $taken = $tutor->bookings()
            ->active()
            ->whereBetween('starts_at', [$dayStart, $dayStart->addDay()])
            ->pluck('starts_at')
            ->map(fn ($s) => CarbonImmutable::parse($s, 'UTC'))
            ->all();

        return $this->slots->freeSlots($date, $tutor->timezone, $windows, $taken, $now);
    }

    public function book(Tutor $tutor, array $data, ?CarbonImmutable $now = null): Booking
    {
        $start = CarbonImmutable::parse($data['starts_at'])->utc();

        if (! in_array($data['subject'], $tutor->subjects, true)) {
            throw ValidationException::withMessages(['subject' => 'This tutor does not teach that subject.']);
        }

        $date = $start->setTimezone($tutor->timezone)->toDateString();
        $isFree = collect($this->freeSlots($tutor, $date, $now))->contains(fn ($s) => $s->eq($start));
        if (! $isFree) {
            throw new SlotUnavailable('This time is not available.');
        }

        try {
            $booking = DB::transaction(fn () => Booking::create([
                'tutor_id' => $tutor->id,
                'student_name' => $data['student_name'],
                'student_email' => $data['student_email'],
                'subject' => $data['subject'],
                'starts_at' => $start,
                'ends_at' => $start->addMinutes($this->slots->lessonMinutes()),
                'status' => Booking::BOOKED,
                'active_slot' => $start->toIso8601String(),
                'cancel_token' => Str::random(48),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw new SlotUnavailable('Someone has just booked this time.');
        }

        SendBookingConfirmation::dispatch($booking->id)->afterCommit();

        return $booking;
    }

    public function cancel(Booking $booking, string $token, ?CarbonImmutable $now = null): Booking
    {
        $now ??= CarbonImmutable::now();

        if (! hash_equals($booking->cancel_token, $token)) {
            abort(403, 'Invalid cancellation token.');
        }
        if ($booking->status === Booking::CANCELLED) {
            return $booking;
        }
        if ($booking->starts_at->subHours(self::CANCEL_CUTOFF_HOURS)->lt($now)) {
            throw ValidationException::withMessages([
                'booking' => 'Lessons can be cancelled up to '.self::CANCEL_CUTOFF_HOURS.' hours before the start.',
            ]);
        }

        $booking->update(['status' => Booking::CANCELLED, 'active_slot' => null]);

        return $booking;
    }
}
