<?php

namespace App\Booking;

use Illuminate\Http\JsonResponse;
use RuntimeException;

final class SlotUnavailable extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
