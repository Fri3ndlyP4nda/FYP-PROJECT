<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'ariftermizi2@gmail.com'],
            [
                'name' => 'Arif Termizi',
                'password' => \Illuminate\Support\Facades\Hash::make('123456'),
                'role' => 'admin',
            ]
        );

        $this->call([
            ProgrammeSeeder::class,
            CourseSeeder::class,
            EvaluatorSeeder::class,
        ]);
    }
}
