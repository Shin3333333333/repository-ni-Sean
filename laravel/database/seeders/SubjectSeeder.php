<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subjects = [
            [
                'year_level' => '1',
                'semester_id' => 1,
                'subject_code' => 'ITP 1',
                'subject_title' => 'Introduction to Computing',
                'lec_units' => 2,
                'lab_units' => 1,
                'hours' => 3,
                'course_id' => 1,
            ],
            [
                'year_level' => '1',
                'semester_id' => 1,
                'subject_code' => 'ITP 2',
                'subject_title' => 'Computer Programming 1',
                'lec_units' => 2,
                'lab_units' => 1,
                'hours' => 3,
                'course_id' => 1,
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}