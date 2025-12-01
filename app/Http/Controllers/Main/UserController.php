<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);

        // Handle AJAX request for pagination
        if ($request->ajax()) {
            return view('pages.owner.manage-data.users', compact('users'));
        }

        return view('pages.owner.manage-data.users', compact('users'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'phone_number' => 'nullable|string|max:100',
            'role' => 'required|in:owner,admin,pm,karyawan',
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [], [
            'fullname' => 'Fullname',
            'username' => 'Username',
            'phone_number' => 'Phone',
            'role' => 'Role',
            'password' => 'Password',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('owner.manage-data.users.index')
            ->with('message', 'User created successfully')
            ->with('alert-type', 'success');
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'phone_number' => 'nullable|string|max:100',
            'role' => 'required|in:owner,admin,pm,karyawan',
            'password' => ['nullable', 'confirmed', Password::min(6)],
        ], [], [
            'fullname' => 'Fullname',
            'username' => 'Username',
            'phone_number' => 'Phone',
            'role' => 'Role',
            'password' => 'Password',
        ]);

        // Only update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('owner.manage-data.users.index')
            ->with('message', 'User updated successfully')
            ->with('alert-type', 'success');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return redirect()->route('owner.manage-data.users.index')
                ->with('message', 'You cannot delete your own account')
                ->with('alert-type', 'warning');
        }

        $user->delete();

        return redirect()->route('owner.manage-data.users.index')
            ->with('message', 'User deleted successfully')
            ->with('alert-type', 'success');
    }
}
