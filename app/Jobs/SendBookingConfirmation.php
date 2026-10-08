<?php

namespace App\Jobs;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public readonly int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::with('tutor')->find($this->bookingId);
        if ($booking === null || $booking->status !== Booking::BOOKED) {
            return;
        }

        Mail::to($booking->student_email)->send(new BookingConfirmed($booking));
    }
}
