<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Support\ApplicationCase;
use Illuminate\Support\Facades\Auth;

class ApelAController extends Controller
{
    public function show($id)
    {
        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->where('application_type', 'APEL A')
            ->firstOrFail();

        /*
         | The rail and the next action come from the stage machine.
         |
         | The view used to build its own progress tracker from a hard-coded
         | step list and a match() on $application->status. StageMachine writes
         | status as $stage->label($type) - "Application submitted", "Reviewer
         | assigned", "Admission approved" - and none of those matched the arms,
         | which expected the pre-stage-machine spellings ("Pre-Application
         | Submitted", "Assessor Assigned"). Every one of the 19 stages fell
         | through to `default => 0`, so the tracker showed step 1 of 5 for
         | every application, an approved one included.
         */
        return view('student.apel_a.show', [
            'application' => $application,
            'case' => ApplicationCase::for($application, Auth::user()),
        ]);
    }
}
