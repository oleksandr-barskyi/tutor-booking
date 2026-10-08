<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingConfirmed extends Mailable
{
    public function __construct(public readonly Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your lesson with '.$this->booking->tutor->name.' is booked');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.booking-confirmed');
    }
}
