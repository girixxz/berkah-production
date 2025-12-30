<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees (all users except owner)
     */
    public function index(Request $request)
    {
        $employees = User::where('role', '!=', 'owner')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get all employees for search functionality
        $allEmployees = User::where('role', '!=', 'owner')
            ->orderBy('created_at', 'desc')
            ->get();

        // Split work_date for each employee (format: "YYYY-MM-DD" to day, month, year)
        foreach ($employees as $employee) {
            if ($employee->work_date) {
                try {
                    $date = \Carbon\Carbon::parse($employee->work_date);
                    $employee->work_day = $date->day;
                    $employee->work_month = $date->format('F');
                    $employee->work_year = $date->year;
                } catch (\Exception $e) {
                    $employee->work_day = '';
                    $employee->work_month = '';
                    $employee->work_year = '';
                }
            } else {
                $employee->work_day = '';
                $employee->work_month = '';
                $employee->work_year = '';
            }
        }

        // Split work_date for all employees
        foreach ($allEmployees as $employee) {
            if ($employee->work_date) {
                try {
                    $date = \Carbon\Carbon::parse($employee->work_date);
                    $employee->work_day = $date->day;
                    $employee->work_month = $date->format('F');
                    $employee->work_year = $date->year;
                } catch (\Exception $e) {
                    $employee->work_day = '';
                    $employee->work_month = '';
                    $employee->work_year = '';
                }
            } else {
                $employee->work_day = '';
                $employee->work_month = '';
                $employee->work_year = '';
            }
        }

        // Handle AJAX request for pagination
        if ($request->ajax()) {
            return view('pages.owner.manage-data.employees', compact('employees', 'allEmployees'));
        }

        return view('pages.owner.manage-data.employees', compact('employees', 'allEmployees'));
    }

    /**
     * Update employee data
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'birth_day' => 'nullable|integer|min:1|max:31',
            'birth_month' => 'nullable|string',
            'birth_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'gender' => 'nullable|in:Male,Female',
            'work_day' => 'nullable|integer|min:1|max:31',
            'work_month' => 'nullable|string',
            'work_year' => 'nullable|integer|min:1900|max:2100',
            'dress_size' => 'nullable|string',
            'salary_system' => 'nullable|string',
            'salary_cycle' => 'nullable|integer|min:1|max:31',
            'address' => 'nullable|string',
        ], [], [
            'fullname' => 'Full Name',
            'birth_day' => 'Birth Day',
            'birth_month' => 'Birth Month',
            'birth_year' => 'Birth Year',
            'gender' => 'Gender',
            'work_day' => 'Work Day',
            'work_month' => 'Work Month',
            'work_year' => 'Work Year',
            'dress_size' => 'Dress Size',
            'salary_system' => 'Salary System',
            'salary_cycle' => 'Salary Cycle',
            'address' => 'Address',
        ]);

        // Auto-generate username from fullname if fullname is changed
        if ($validated['fullname'] !== $user->fullname) {
            $baseUsername = strtolower(str_replace(' ', '', $validated['fullname']));
            $username = $baseUsername;
            $counter = 1;
            
            // Check if username exists, add number suffix if needed
            while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            
            $validated['username'] = $username;
        }

        // Combine birth_day, birth_month, birth_year into birth_date (format: YYYY-MM-DD)
        if ($validated['birth_day'] && $validated['birth_month'] && $validated['birth_year']) {
            $months = ['January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06',
                       'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'];
            $monthNum = $months[$validated['birth_month']] ?? '01';
            $day = str_pad($validated['birth_day'], 2, '0', STR_PAD_LEFT);
            $validated['birth_date'] = $validated['birth_year'] . '-' . $monthNum . '-' . $day;
        } else {
            $validated['birth_date'] = null;
        }
        
        // Remove temporary birth fields
        unset($validated['birth_day'], $validated['birth_month'], $validated['birth_year']);

        // Combine work_day, work_month, work_year into work_date (format: YYYY-MM-DD)
        if ($validated['work_day'] && $validated['work_month'] && $validated['work_year']) {
            $months = ['January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06',
                       'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'];
            $monthNum = $months[$validated['work_month']] ?? '01';
            $day = str_pad($validated['work_day'], 2, '0', STR_PAD_LEFT);
            $validated['work_date'] = $validated['work_year'] . '-' . $monthNum . '-' . $day;
        } else {
            $validated['work_date'] = null;
        }

        // Remove temporary fields
        unset($validated['work_day'], $validated['work_month'], $validated['work_year']);

        $user->update($validated);

        return redirect()->route('owner.manage-data.employees.index')
            ->with('message', 'Employee updated successfully!')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'employees-section');
    }
}
