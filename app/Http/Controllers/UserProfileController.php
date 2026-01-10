<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Display a listing of user profiles (all users except owner)
     */
    public function index(Request $request)
    {
        $employees = User::where('role', '!=', 'owner')
            ->with(['profile', 'employeeSalary.salarySystem'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get all employees for search functionality
        $allEmployees = User::where('role', '!=', 'owner')
            ->with(['profile', 'employeeSalary.salarySystem'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all salary systems for dropdown
        $salarySystems = \App\Models\SalarySystem::orderBy('type_name')->get();

        // Handle AJAX request for pagination
        if ($request->ajax()) {
            return view('pages.owner.manage-data.user-profile', compact('employees', 'allEmployees', 'salarySystems'));
        }

        return view('pages.owner.manage-data.user-profile', compact('employees', 'allEmployees', 'salarySystems'));
    }

    /**
     * Update user profile data
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:100',
            'birth_day' => 'nullable|integer|min:1|max:31',
            'birth_month' => 'nullable|string',
            'birth_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'gender' => 'nullable|in:male,female',
            'work_day' => 'nullable|integer|min:1|max:31',
            'work_month' => 'nullable|string',
            'work_year' => 'nullable|integer|min:1900|max:2100',
            'dress_size' => 'nullable|string|max:10',
            'salary_system_id' => 'nullable|exists:salary_systems,id',
            'address' => 'nullable|string',
        ], [], [
            'fullname' => 'Full Name',
            'phone_number' => 'Phone Number',
            'birth_day' => 'Birth Day',
            'birth_month' => 'Birth Month',
            'birth_year' => 'Birth Year',
            'gender' => 'Gender',
            'work_day' => 'Work Day',
            'work_month' => 'Work Month',
            'work_year' => 'Work Year',
            'dress_size' => 'Dress Size',
            'salary_system_id' => 'Salary System',
            'address' => 'Address',
        ]);

        // Combine birth_day, birth_month, birth_year into birth_date (format: YYYY-MM-DD)
        if ($validated['birth_day'] && $validated['birth_month'] && $validated['birth_year']) {
            $months = ['January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06',
                       'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'];
            $monthNum = $months[$validated['birth_month']] ?? '01';
            $day = str_pad($validated['birth_day'], 2, '0', STR_PAD_LEFT);
            $birthDate = $validated['birth_year'] . '-' . $monthNum . '-' . $day;
        } else {
            $birthDate = null;
        }

        // Combine work_day, work_month, work_year into work_date (format: YYYY-MM-DD)
        if ($validated['work_day'] && $validated['work_month'] && $validated['work_year']) {
            $months = ['January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06',
                       'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'];
            $monthNum = $months[$validated['work_month']] ?? '01';
            $day = str_pad($validated['work_day'], 2, '0', STR_PAD_LEFT);
            $workDate = $validated['work_year'] . '-' . $monthNum . '-' . $day;
        } else {
            $workDate = null;
        }

        // Update or create user profile
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'fullname' => $validated['fullname'],
                'phone_number' => $validated['phone_number'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'birth_date' => $birthDate,
                'work_date' => $workDate,
                'dress_size' => $validated['dress_size'] ?? null,
                'address' => $validated['address'] ?? null,
            ]
        );

        // Update or create employee salary
        if (isset($validated['salary_system_id'])) {
            $user->employeeSalary()->updateOrCreate(
                ['user_id' => $user->id],
                ['salary_system_id' => $validated['salary_system_id']]
            );
        }

        return redirect()->route('owner.manage-data.user-profile.index')
            ->with('message', 'User profile updated successfully!')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'user-profile-section');
    }
}
