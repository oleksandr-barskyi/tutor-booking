Hi {{ $booking->student_name }},

your {{ $booking->subject }} lesson with {{ $booking->tutor->name }} is booked for
{{ $booking->starts_at->setTimezone($booking->tutor->timezone)->format('l, j F Y, H:i') }} ({{ $booking->tutor->timezone }}).

You can cancel up to 12 hours before the start.
