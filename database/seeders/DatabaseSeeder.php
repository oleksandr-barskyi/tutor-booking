<?php

namespace Database\Seeders;

use App\Models\Tutor;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tutors = [
            ['name' => 'Oleksandr B.', 'subjects' => ['math', 'physics', 'informatics'], 'hourly_rate' => 400, 'hours' => [[1, '16:00', '21:00'], [2, '16:00', '21:00'], [3, '16:00', '21:00'], [4, '16:00', '21:00'], [6, '10:00', '14:00'], [7, '10:00', '14:00']]],
            ['name' => 'Iryna K.', 'subjects' => ['english'], 'hourly_rate' => 350, 'hours' => [[1, '09:00', '13:00'], [3, '09:00', '13:00'], [5, '09:00', '13:00']]],
            ['name' => 'Taras M.', 'subjects' => ['math', 'chemistry'], 'hourly_rate' => 450, 'hours' => [[2, '18:00', '22:00'], [4, '18:00', '22:00'], [6, '12:00', '18:00']]],
        ];

        foreach ($tutors as $data) {
            $tutor = Tutor::create([
                'name' => $data['name'],
                'subjects' => $data['subjects'],
                'hourly_rate' => $data['hourly_rate'],
            ]);
            foreach ($data['hours'] as [$weekday, $from, $to]) {
                $tutor->workingHours()->create(['weekday' => $weekday, 'starts_at' => $from, 'ends_at' => $to]);
            }
        }
    }
}
