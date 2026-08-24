<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [

            [
                'course_code' => 'MECS0013',
                'course_name' => 'Theory of Computer Science',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECS0023',
                'course_name' => 'Data Structures and Algorithm',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECS0033',
                'course_name' => 'Artificial Intelligence',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECS1013',
                'course_name' => 'Advanced Theory of Computer Science',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECR0013',
                'course_name' => 'Cryptography',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Cyber Security',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECR0023',
                'course_name' => 'Computer Security',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Cyber Security',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECR1023',
                'course_name' => 'Information Security Governance & Risk Management',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Cyber Security',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECR1033',
                'course_name' => 'Digital Forensics',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Cyber Security',
                'status' => 'active',
            ],

            [
                'course_code' => 'MECR1073',
                'course_name' => 'Penetration Testing',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Cyber Security',
                'status' => 'active',
            ],

            [
                'course_code' => 'MCSD1013',
                'course_name' => 'Business Intelligence & Analytics',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Science (Data Science)',
                'status' => 'active',
            ],

            [
                'course_code' => 'MCSD1113',
                'course_name' => 'Statistics for Data Science',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Science (Data Science)',
                'status' => 'active',
            ],

            [
                'course_code' => 'MCSD1163',
                'course_name' => 'Big Data Computing',
                'faculty' => 'Faculty of Computing',
                'programme' => 'Master of Science (Data Science)',
                'status' => 'active',
            ],

        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(['course_code' => $course['course_code']], $course);
        }
    }
}
