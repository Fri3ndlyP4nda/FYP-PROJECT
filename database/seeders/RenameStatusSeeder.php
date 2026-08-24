<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application;

class RenameStatusSeeder extends Seeder
{
    public function run(): void
    {
        Application::where('status', 'Assessor Assigned')->update(['status' => 'Evaluator Assigned']);
    }
}
