<?php

namespace App\Http\Controllers\Api;

use App\Booking\BookingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Tutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request, BookingService $service): JsonResponse
    {
        $data = $request->validated();
        $tutor = Tutor::with('workingHours')->findOrFail($data['tutor_id']);

        $resource = new BookingResource($service->book($tutor, $data));
        $resource->withToken = true;

        return $resource->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Booking $booking, BookingService $service): BookingResource
    {
        $token = $request->validate(['token' => ['required', 'string']])['token'];

        return new BookingResource($service->cancel($booking, $token));
    }
}
