<?php

namespace App\Http\Controllers;

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
        $users = User::with('profile')->orderBy('created_at', 'desc')->paginate(10);
        $allUsers = User::with('profile')->orderBy('created_at', 'desc')->get();

        // Handle AJAX request for pagination
        if ($request->ajax()) {
            return view('pages.owner.manage-data.users', compact('users', 'allUsers'));
        }

        return view('pages.owner.manage-data.users', compact('users', 'allUsers'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:255|unique:users,username',
            'phone_number' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'role' => 'required|in:owner,admin,finance,pm,employee',
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [], [
            'fullname' => 'Fullname',
            'username' => 'Username',
            'phone_number' => 'Phone',
            'gender' => 'Gender',
            'role' => 'Role',
            'password' => 'Password',
        ]);

        // Create user
        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => 'active',
        ]);

        // Create user profile
        $user->profile()->create([
            'fullname' => $validated['fullname'],
            'phone_number' => $validated['phone_number'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ]);

        return redirect()->route('owner.manage-data.users.index')
            ->with('scrollToSection', 'users-section')
            ->with('message', 'User created successfully')
            ->with('alert-type', 'success');
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'phone_number' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'role' => 'required|in:owner,admin,finance,pm,employee',
            'status' => 'required|in:active,inactive',
            'password' => ['nullable', 'confirmed', Password::min(6)],
        ], [], [
            'fullname' => 'Fullname',
            'username' => 'Username',
            'phone_number' => 'Phone',
            'gender' => 'Gender',
            'role' => 'Role',
            'status' => 'Status',
            'password' => 'Password',
        ]);

        // Update user
        $userData = [
            'username' => $validated['username'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];

        // Only update password if provided
        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        // Update or create user profile
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'fullname' => $validated['fullname'],
                'phone_number' => $validated['phone_number'] ?? null,
                'gender' => $validated['gender'] ?? null,
            ]
        );

        return redirect()->route('owner.manage-data.users.index')
            ->with('scrollToSection', 'users-section')
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
                ->with('scrollToSection', 'users-section')
                ->with('message', 'You cannot delete your own account')
                ->with('alert-type', 'warning');
        }

        $user->delete();

        return redirect()->route('owner.manage-data.users.index')
            ->with('scrollToSection', 'users-section')
            ->with('message', 'User deleted successfully')
            ->with('alert-type', 'success');
    }
}
