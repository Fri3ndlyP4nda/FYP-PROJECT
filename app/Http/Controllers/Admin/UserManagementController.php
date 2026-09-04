<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Account administration.
 *
 * All three routes into this controller were commented out in routes/web.php,
 * which left the system with no way at all to create an evaluator or an
 * administrator: registration hardcodes 'student', and nothing else writes the
 * role. Evaluator accounts could only be made by editing MongoDB by hand — so a
 * fresh deployment could accept applications it had nobody to assess.
 *
 * The routes are restored, with the two guards the original lacked: an
 * administrator cannot change their own role, and an evaluator holding live
 * work cannot be demoted out from under it.
 */
class UserManagementController extends Controller
{
    /**
     * Stages that do not count as live work: decided, refused, or never sent.
     * Shared by the single-evaluator count and the batched one so the two can
     * never disagree about what "assigned right now" means.
     */
    private const CLOSED_STAGES = ['approved', 'rejected', 'advisor_rejected', 'draft'];

    public function index(Request $request)
    {
        $query = User::query();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('name', 'asc')->get();

        /*
         | Live workload, so an administrator can see what a demotion would
         | strand - counted for every evaluator in one pass rather than one
         | query each. Measured: 4 users cost 7 queries and 34 cost 37, because
         | this was a loop calling activeAssignments() per evaluator. A registry
         | with a hundred assessors would have issued a hundred counts to render
         | one page.
         */
        $workload = $this->workloadFor($users->where('role', 'evaluator'));

        return view('admin.users.index', [
            'users' => $users,
            'workload' => $workload,
            'search' => $search,
            'role' => $role,
            'counts' => [
                'student' => User::where('role', 'student')->count(),
                'evaluator' => User::where('role', 'evaluator')->count(),
                'admin' => User::where('role', 'admin')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Create a member of staff directly. Self-registration is students only, by
     * design, so this is the only way an evaluator or administrator comes into
     * existence.
     */
    public function store(Request $request)
    {
        $this->normalizeEmail($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:evaluator,admin',
        ], [
            'role.in' => 'Student accounts are created by the applicant through registration.',
        ]);

        // A generated password the administrator hands over out of band; the
        // holder resets it through the normal forgotten-password flow.
        $temporaryPassword = Str::password(16);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($temporaryPassword),
            'role' => $request->role,
        ]);

        $this->log('Created Staff Account', "Created {$request->role} account for {$user->name} ({$user->email})", $request);

        return redirect()->route('admin.users.index')
            ->with('success', "Account created for {$user->name}. Temporary password: {$temporaryPassword} — give this to them directly and ask them to reset it.");
    }

    public function edit($id)
    {
        $user = User::where('_id', $id)->firstOrFail();

        return view('admin.users.edit', [
            'user' => $user,
            'activeAssignments' => $user->role === 'evaluator'
                ? $this->activeAssignments((string) $user->_id)
                : 0,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::where('_id', $id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::in(['student', 'evaluator', 'admin'])],
        ]);

        /*
         | Nothing stopped an administrator demoting themselves. With one admin
         | account — which is the normal case here — that locks the whole
         | institution out of the admin area permanently, and there is no route
         | left that could undo it.
         */
        if ((string) $user->_id === (string) Auth::id() && $request->role !== $user->role) {
            return redirect()->back()->withErrors([
                'role' => 'You cannot change your own role. Ask another administrator to do it.',
            ]);
        }

        /*
         | Demoting an evaluator who is holding live applications would leave
         | those applications assigned to somebody who can no longer open them,
         | with no error anywhere — the evaluator queue simply filters them out.
         */
        if ($user->role === 'evaluator' && $request->role !== 'evaluator') {
            $active = $this->activeAssignments((string) $user->_id);

            if ($active > 0) {
                return redirect()->back()->withErrors([
                    'role' => "{$user->name} is currently assigned to {$active} live application(s). Reassign those first, or they will be left with no evaluator.",
                ]);
            }
        }

        $previousRole = $user->role;

        $user->update([
            'name' => $request->name,
            'role' => $request->role,
        ]);

        if ($previousRole !== $request->role) {
            $this->log('Changed User Role', "Changed {$user->email} from {$previousRole} to {$request->role}", $request);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} updated.");
    }

    /** Applications this evaluator holds that have not been decided. */
    /**
     * Active assignment counts for many evaluators, in one query.
     *
     * @param  Collection<int, User>  $evaluators
     * @return array<string, int>
     */
    private function workloadFor($evaluators): array
    {
        $ids = $evaluators->map(fn (User $u) => (string) $u->_id)->values()->all();

        if ($ids === []) {
            return [];
        }

        $counts = array_fill_keys($ids, 0);

        // One pass over the live applications assigned to any of them. Only the
        // two evaluator fields are read back, so the size of an application
        // document does not affect the cost of counting.
        $applications = Application::where(function ($query) use ($ids) {
            $query->whereIn('evaluator_id', $ids)
                ->orWhereIn('evaluator_2_id', $ids);
        })
            ->whereNotIn('stage', self::CLOSED_STAGES)
            ->get(['evaluator_id', 'evaluator_2_id']);

        foreach ($applications as $application) {
            foreach ([$application->evaluator_id, $application->evaluator_2_id] as $assigned) {
                $assigned = (string) $assigned;

                if ($assigned !== '' && array_key_exists($assigned, $counts)) {
                    $counts[$assigned]++;
                }
            }
        }

        return $counts;
    }

    private function activeAssignments(string $evaluatorId): int
    {
        return Application::where(function ($query) use ($evaluatorId) {
            $query->where('evaluator_id', $evaluatorId)
                ->orWhere('evaluator_2_id', $evaluatorId);
        })
            ->whereNotIn('stage', self::CLOSED_STAGES)
            ->count();
    }

    private function log(string $action, string $description, Request $request): void
    {
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);
    }
}
