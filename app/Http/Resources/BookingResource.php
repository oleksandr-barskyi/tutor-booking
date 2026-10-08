<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public bool $withToken = false;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tutor_id' => $this->tutor_id,
            'student_name' => $this->student_name,
            'subject' => $this->subject,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'status' => $this->status,
            'cancel_token' => $this->when($this->withToken, $this->cancel_token),
        ];
    }
}
