<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ApelCController extends Controller
{
    public function show($id)
    {
        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->where('application_type', 'APEL C')
            ->firstOrFail();

        return view('student.apel_c.show', compact('application'));
    }
}
