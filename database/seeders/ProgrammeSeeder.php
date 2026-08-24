<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Programme;

class ProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $programmes = [
            [
                'name' => 'Master of Science (Data Science)',
                'faculty' => 'Faculty of Computing',
                'level' => 'Master',
                'type' => 'APEL A',
                'status' => 'active',
            ],
            [
                'name' => 'Master in Innovative Computing',
                'faculty' => 'Faculty of Computing',
                'level' => 'Master',
                'type' => 'APEL A',
                'status' => 'active',
            ],
            [
                'name' => 'Master of Computer Science',
                'faculty' => 'Faculty of Computing',
                'level' => 'Master',
                'type' => 'APEL A',
                'status' => 'active',
            ],
            [
                'name' => 'Master of Computer Science (ODL)',
                'faculty' => 'Faculty of Computing',
                'level' => 'Master',
                'type' => 'APEL A',
                'status' => 'active',
            ],
            [
                'name' => 'Master of Cyber Security',
                'faculty' => 'Faculty of Computing',
                'level' => 'Master',
                'type' => 'APEL A',
                'status' => 'active',
            ],
            [
                'name' => 'Master of Cyber Security (ODL)',
                'faculty' => 'Faculty of Computing',
                'level' => 'Master',
                'type' => 'APEL A',
                'status' => 'active',
            ],
        ];

        foreach ($programmes as $programme) {
            Programme::firstOrCreate(['name' => $programme['name']], $programme);
        }
    }
}

