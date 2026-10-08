<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tutor_id' => ['required', 'integer', 'exists:tutors,id'],
            'student_name' => ['required', 'string', 'min:2', 'max:100'],
            'student_email' => ['required', 'email:rfc', 'max:150'],
            'subject' => ['required', 'string', 'max:50'],
            'starts_at' => ['required', 'date', 'after:now'],
        ];
    }
}
