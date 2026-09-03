<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Support\ApplicationCase;
use Illuminate\Support\Facades\Auth;

class ApelCController extends Controller
{
    public function show($id)
    {
        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->where('application_type', 'APEL C')
            ->firstOrFail();

        /*
         | The rail and the next action come from the stage machine.
         |
         | As with APEL A, the view used to build its own progress tracker from
         | a hard-coded step list and a match() on $application->status. Status
         | is written as $stage->label($type), and none of the labels matched
         | the arms - which still expected the pre-stage-machine spellings - so
         | all 19 stages fell through to `default => 0` and every candidate saw
         | step 1 of 8 no matter how far along they were.
         */
        return view('student.apel_c.show', [
            'application' => $application,
            'case' => ApplicationCase::for($application, Auth::user()),
        ]);
    }
}
