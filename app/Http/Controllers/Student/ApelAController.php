<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ApelAController extends Controller
{
    public function show($id)
    {
        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->where('application_type', 'APEL A')
            ->firstOrFail();

        return view('student.apel_a.show', compact('application'));
    }
}
