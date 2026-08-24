<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();

        return view('admin.users.index', compact('users'));
    }

    public function edit($id)
    {
        $user = User::where('_id', $id)->firstOrFail();

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:student,evaluator,admin',
        ]);

        $user = User::where('_id', $id)->firstOrFail();

        $user->update([
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User role updated successfully.');
    }
}
