<?php

namespace Database\Seeders;

use App\Models\Application;
use Illuminate\Database\Seeder;

class RenameStatusSeeder extends Seeder
{
    public function run(): void
    {
        Application::where('status', 'Assessor Assigned')->update(['status' => 'Evaluator Assigned']);
    }
}
