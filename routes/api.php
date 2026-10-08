<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\TutorController;
use Illuminate\Support\Facades\Route;

Route::get('/tutors', [TutorController::class, 'index']);
Route::get('/tutors/{tutor}/slots', [TutorController::class, 'slots']);
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:20,1');
Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
