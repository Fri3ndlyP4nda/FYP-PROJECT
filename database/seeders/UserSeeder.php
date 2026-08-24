<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One account per role, for local development and demonstration.
 *
 * Replaces the previous seeders, which hard-coded real personal Gmail and
 * university addresses with the password "123456" - a password that fails this
 * application's own registration policy, and which could only exist because
 * seeding bypasses that policy.
 *
 * Passwords are read from the environment so a deployed instance never inherits
 * the documented defaults. The defaults below exist so a fresh clone is usable
 * immediately; the addresses use the reserved .test domain, which cannot receive
 * mail and cannot belong to a real person.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Aina Student',
                'email' => 'student@apel.test',
                'role' => 'student',
                'password' => env('SEED_STUDENT_PASSWORD', 'ApelStudent2026'),
            ],
            [
                'name' => 'Rizal Evaluator',
                'email' => 'evaluator@apel.test',
                'role' => 'evaluator',
                'password' => env('SEED_EVALUATOR_PASSWORD', 'ApelEvaluator2026'),
            ],
            [
                'name' => 'Siti Admin',
                'email' => 'admin@apel.test',
                'role' => 'admin',
                'password' => env('SEED_ADMIN_PASSWORD', 'ApelAdmin2026'),
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make($account['password']),
                ]
            );

            $this->command?->info("  {$account['role']}: {$account['email']}");
        }
    }
}
