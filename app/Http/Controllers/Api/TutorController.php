<?php

namespace App\Http\Controllers\Api;

use App\Booking\BookingService;
use App\Http\Controllers\Controller;
use App\Http\Resources\TutorResource;
use App\Models\Tutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TutorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subject = $request->string('subject')->toString();

        $tutors = Tutor::query()
            ->when($subject !== '', fn ($q) => $q->whereJsonContains('subjects', $subject))
            ->orderBy('name')
            ->get();

        return TutorResource::collection($tutors);
    }

    public function slots(Request $request, Tutor $tutor, BookingService $service): JsonResponse
    {
        $validated = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $tutor->load('workingHours');

        $slots = array_map(
            fn ($s) => [
                'starts_at' => $s->toIso8601String(),
                'local_time' => $s->setTimezone($tutor->timezone)->format('H:i'),
            ],
            $service->freeSlots($tutor, $validated['date']),
        );

        return response()->json(['date' => $validated['date'], 'timezone' => $tutor->timezone, 'slots' => $slots]);
    }
}
