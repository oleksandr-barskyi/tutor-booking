<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    public const BOOKED = 'booked';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'tutor_id', 'student_name', 'student_email', 'subject',
        'starts_at', 'ends_at', 'status', 'active_slot', 'cancel_token',
    ];

    protected $hidden = ['cancel_token', 'active_slot'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::BOOKED);
    }
}
