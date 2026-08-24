<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EvaluatorSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'evaluator153@gmail.com'],
            [
                'name' => 'Evaluator 153',
                'password' => Hash::make('123456'),
                'role' => 'evaluator',
            ]
        );

        User::firstOrCreate(
            ['email' => 'muhammadarif.at@graduate.utm.my'],
            [
                'name' => 'Muhammad Arif',
                'password' => Hash::make('123456'),
                'role' => 'evaluator',
            ]
        );
    }
}
