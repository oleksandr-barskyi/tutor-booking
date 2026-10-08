<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Tutor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private Tutor $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-07 10:00:00');
        Mail::fake();

        $this->tutor = Tutor::create(['name' => 'Oleksandr B.', 'subjects' => ['math', 'physics'], 'hourly_rate' => 400]);
        $this->tutor->workingHours()->create(['weekday' => 4, 'starts_at' => '16:00', 'ends_at' => '21:00']);
        Tutor::create(['name' => 'Iryna K.', 'subjects' => ['english'], 'hourly_rate' => 350]);
    }

    private function bookingPayload(array $override = []): array
    {
        return array_merge([
            'tutor_id' => $this->tutor->id,
            'student_name' => 'Sofia',
            'student_email' => 'sofia@example.com',
            'subject' => 'math',
            'starts_at' => '2026-10-08T13:00:00Z',
        ], $override);
    }

    public function test_lists_tutors_filtered_by_subject(): void
    {
        $this->getJson('/api/tutors?subject=english')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Iryna K.');
    }

    public function test_returns_free_slots_in_local_and_utc_time(): void
    {
        $this->getJson("/api/tutors/{$this->tutor->id}/slots?date=2026-10-08")
            ->assertOk()
            ->assertJsonCount(5, 'slots')
            ->assertJsonPath('slots.0.local_time', '16:00')
            ->assertJsonPath('slots.0.starts_at', '2026-10-08T13:00:00+00:00');
    }

    public function test_books_a_slot_and_sends_a_confirmation(): void
    {
        $response = $this->postJson('/api/bookings', $this->bookingPayload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'booked');

        $this->assertNotEmpty($response->json('data.cancel_token'));
        Mail::assertSent(BookingConfirmed::class, fn ($mail) => $mail->hasTo('sofia@example.com'));

        $this->getJson("/api/tutors/{$this->tutor->id}/slots?date=2026-10-08")->assertJsonCount(4, 'slots');
    }

    public function test_the_same_slot_cannot_be_booked_twice(): void
    {
        $this->postJson('/api/bookings', $this->bookingPayload())->assertCreated();
        $this->postJson('/api/bookings', $this->bookingPayload(['student_email' => 'other@example.com']))
            ->assertStatus(409);
    }

    public function test_the_database_rejects_a_double_booking_even_without_the_service(): void
    {
        $row = [
            'tutor_id' => $this->tutor->id,
            'student_name' => 'A',
            'student_email' => 'a@example.com',
            'subject' => 'math',
            'starts_at' => '2026-10-08 13:00:00',
            'ends_at' => '2026-10-08 14:00:00',
            'status' => Booking::BOOKED,
            'active_slot' => '2026-10-08T13:00:00+00:00',
        ];
        Booking::create($row + ['cancel_token' => 'one']);

        $this->expectException(UniqueConstraintViolationException::class);
        Booking::create($row + ['cancel_token' => 'two']);
    }

    public function test_rejects_times_outside_working_hours(): void
    {
        $this->postJson('/api/bookings', $this->bookingPayload(['starts_at' => '2026-10-08T05:00:00Z']))
            ->assertStatus(409);
    }

    public function test_rejects_a_subject_the_tutor_does_not_teach(): void
    {
        $this->postJson('/api/bookings', $this->bookingPayload(['subject' => 'english']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');
    }

    public function test_validates_input(): void
    {
        $this->postJson('/api/bookings', $this->bookingPayload(['student_email' => 'not-an-email', 'starts_at' => '2020-01-01T10:00:00Z']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_email', 'starts_at']);
    }

    public function test_cancel_needs_the_right_token(): void
    {
        $id = $this->postJson('/api/bookings', $this->bookingPayload())->json('data.id');

        $this->deleteJson("/api/bookings/{$id}?token=wrong")->assertForbidden();
    }

    public function test_cancelling_frees_the_slot_for_someone_else(): void
    {
        $booking = $this->postJson('/api/bookings', $this->bookingPayload())->json('data');

        $this->deleteJson("/api/bookings/{$booking['id']}?token={$booking['cancel_token']}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->postJson('/api/bookings', $this->bookingPayload(['student_email' => 'next@example.com']))->assertCreated();
    }

    public function test_cannot_cancel_less_than_twelve_hours_before(): void
    {
        $booking = $this->postJson('/api/bookings', $this->bookingPayload())->json('data');
        $this->travelTo('2026-10-08 02:00:00');

        $this->deleteJson("/api/bookings/{$booking['id']}?token={$booking['cancel_token']}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('booking');
    }
}
