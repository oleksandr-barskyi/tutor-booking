<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TutorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subjects' => $this->subjects,
            'hourly_rate' => $this->hourly_rate,
            'timezone' => $this->timezone,
        ];
    }
}
