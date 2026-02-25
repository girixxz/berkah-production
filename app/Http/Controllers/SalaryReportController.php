<?php

namespace App\Http\Controllers;

use App\Models\SalaryReport;
use App\Models\EmployeeSalary;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SalaryReportController extends Controller
{
    /**
     * Display salary report page.
     * Also handles AJAX JSON fetch for month/year navigation.
     */
    public function index(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        // Get report period lock status for current month/year
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $reportPeriod = \App\Models\ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodStart->copy()->endOfMonth()->toDateString())
            ->first();

        // Check if this is an AJAX request
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return $this->jsonResponse($month, $year);
        }

        return view('pages.finance.report.salary', compact('reportPeriod'));
    }

    /**
     * Build JSON response with salary data for the given period.
     */
    private function jsonResponse(int $month, int $year): \Illuminate\Http\JsonResponse
    {
        // Get all salary reports for this period, grouped by employee
        $reports = SalaryReport::with(['employeeSalary.user.profile', 'employeeSalary.salarySystem', 'balance'])
            ->whereHas('balance', function ($q) use ($month, $year) {
                $q->whereYear('period_start', $year)
                  ->whereMonth('period_start', $month);
            })
            ->orderBy('employee_salary_id')
            ->orderBy('payment_sequence')
            ->get();

        // Group by employee_salary_id
        $typeOrder = ['monthly_1x' => 1, 'monthly_2x' => 2, 'project_3x' => 3, 'freelance' => 4];

        $grouped = $reports->groupBy('employee_salary_id')->map(function ($items) {
            $first = $items->first();
            $employeeSalary = $first->employeeSalary;
            $salarySystem   = $employeeSalary?->salarySystem;

            // Expected payments based on salary type
            $expectedPayments = match ($salarySystem?->type_name) {
                'monthly_2x' => 2,
                'project_3x' => 3,
                default       => 1,
            };

            $hasDraft = $items->contains(fn ($r) => $r->report_status === 'draft');

            return [
                'employee_salary_id'  => $first->employee_salary_id,
                'employee_name'       => $employeeSalary?->user?->profile?->fullname
                                          ?? $employeeSalary?->user?->username
                                          ?? '—',
                'salary_type'         => $salarySystem?->type_name,
                'expected_payments'   => $expectedPayments,
                'payment_count'       => $items->count(),
                'total_amount'        => $items->sum('amount'),
                'has_draft'           => $hasDraft,
                'latest_date'         => $items->max(fn ($r) => $r->salary_date?->toDateString()),
                'payments'            => $items->map(fn ($r) => [
                    'id'                 => $r->id,
                    'salary_date'        => $r->salary_date?->toDateString(),
                    'payment_sequence'   => $r->payment_sequence,
                    'payment_method'     => $r->payment_method,
                    'amount'             => $r->amount,
                    'notes'              => $r->notes,
                    'proof_img'          => $r->proof_img,
                    'proof_img_url'      => $r->proof_img
                                            ? route('finance.report.salary.serve-image', $r->id)
                                            : null,
                    'report_status'      => $r->report_status,
                    'updated_at'         => $r->updated_at?->toDateTimeString(),
                ])->values(),
            ];
        })->values();

        // Sort by salary type: monthly_1x → monthly_2x → project_3x → freelance
        $grouped = $grouped->sortBy(fn ($item) => $typeOrder[$item['salary_type']] ?? 99)->values();

        // Stats
        $totalSalary    = $reports->sum('amount');
        $totalEmployees = $grouped->count();
        $totalActiveEmployees = EmployeeSalary::whereHas('user', fn($q) => $q->where('status', 'active'))->count();

        // Get balance for this period
        $balance = Balance::whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->first();

        // Get report period lock status
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $reportPeriod = \App\Models\ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodStart->copy()->endOfMonth()->toDateString())
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'salaryData'   => $grouped,
                'stats'        => [
                    'total_employees'        => $totalEmployees,
                    'total_active_employees' => $totalActiveEmployees,
                    'total_salary'           => $totalSalary,
                    'balance_transfer'       => $balance->transfer_balance ?? 0,
                    'balance_cash'           => $balance->cash_balance ?? 0,
                ],
                'periodLocked'  => $reportPeriod && $reportPeriod->lock_status === 'locked',
            ],
        ]);
    }

    /**
     * Delete a salary report and restore balance.
     */
    public function destroy(SalaryReport $salaryReport): \Illuminate\Http\JsonResponse
    {
        try {
            $balance = $salaryReport->balance;

            // Check if period is locked
            if ($balance) {
                $periodStart = Carbon::parse($balance->period_start)->startOfMonth();
                $reportPeriod = \App\Models\ReportPeriod::where('period_start', $periodStart->toDateString())
                    ->where('period_end', $periodStart->copy()->endOfMonth()->toDateString())
                    ->first();

                if ($reportPeriod && $reportPeriod->lock_status === 'locked') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Period is locked. Cannot delete salary payment from a locked period.',
                    ], 403);
                }
            }

            // Restore balance only for real payment methods
            if ($balance && in_array($salaryReport->payment_method, ['cash', 'transfer'])) {
                if ($salaryReport->payment_method === 'cash') {
                    $balance->cash_balance += $salaryReport->amount;
                } else {
                    $balance->transfer_balance += $salaryReport->amount;
                }
                $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
                $balance->save();
            }

            // Delete proof image
            if ($salaryReport->proof_img && Storage::disk('local')->exists($salaryReport->proof_img)) {
                Storage::disk('local')->delete($salaryReport->proof_img);
            }

            $salaryReport->delete();

            return response()->json([
                'success' => true,
                'message' => 'Salary payment deleted and balance restored!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract salary data — create 1 initial draft row per active employee for the period.
     * Can be called multiple times; only adds missing employees.
     */
    public function extract(Request $request): \Illuminate\Http\JsonResponse
    {
        $month = (int) $request->input('balance_month', now()->month);
        $year  = (int) $request->input('balance_year', now()->year);

        // Check if period is locked
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $reportPeriod = \App\Models\ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodStart->copy()->endOfMonth()->toDateString())
            ->first();

        if ($reportPeriod && $reportPeriod->lock_status === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'Period is locked. Cannot extract data for a locked period.',
            ], 403);
        }

        // Find balance for this period
        $balance = Balance::whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->first();

        if (!$balance) {
            return response()->json([
                'success' => false,
                'message' => 'No balance found for this period. Please create a balance first.',
            ], 400);
        }

        // Get all active employees with salary, sorted by type
        $typeOrder = ['monthly_1x' => 1, 'monthly_2x' => 2, 'project_3x' => 3, 'freelance' => 4];

        $employees = EmployeeSalary::with('salarySystem')
            ->whereHas('user', fn($q) => $q->where('status', 'active'))
            ->get()
            ->sortBy(fn($es) => $typeOrder[$es->salarySystem?->type_name] ?? 99)
            ->values();

        if ($employees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active employees with salary data found.',
            ], 400);
        }

        // Check which employees already have reports for this period (with their sequences)
        $existingReports = SalaryReport::where('balance_id', $balance->id)
            ->get()
            ->groupBy('employee_salary_id');

        $created = 0;
        $employeesAdded = 0;
        foreach ($employees as $es) {
            $typeName = $es->salarySystem?->type_name;
            $rowCount = match ($typeName) {
                'monthly_2x' => 2,
                'project_3x' => 3,
                default       => 1,
            };

            $existingSequences = isset($existingReports[$es->id])
                ? $existingReports[$es->id]->pluck('payment_sequence')->toArray()
                : [];

            $addedForThisEmployee = false;
            for ($seq = 1; $seq <= $rowCount; $seq++) {
                if (in_array($seq, $existingSequences)) {
                    continue;
                }

                SalaryReport::create([
                    'balance_id'         => $balance->id,
                    'employee_salary_id' => $es->id,
                    'salary_date'        => now(),
                    'payment_sequence'   => $seq,
                    'payment_method'     => 'null',
                    'amount'             => 0,
                    'notes'              => null,
                    'proof_img'          => null,
                    'report_status'      => 'draft',
                ]);
                $created++;
                $addedForThisEmployee = true;
            }
            if ($addedForThisEmployee) {
                $employeesAdded++;
            }
        }

        if ($created === 0) {
            return response()->json([
                'success' => true,
                'message' => 'All employees are already in the salary report for this period.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully extracted {$employeesAdded} employee(s) with {$created} salary record(s).",
        ]);
    }

    /**
     * Update a salary report (edit from draft to fixed).
     */
    public function update(Request $request, SalaryReport $salaryReport): \Illuminate\Http\JsonResponse
    {
        // Check if period is locked
        $balance = $salaryReport->balance;
        if ($balance) {
            $periodStart = Carbon::parse($balance->period_start)->startOfMonth();
            $reportPeriod = \App\Models\ReportPeriod::where('period_start', $periodStart->toDateString())
                ->where('period_end', $periodStart->copy()->endOfMonth()->toDateString())
                ->first();

            if ($reportPeriod && $reportPeriod->lock_status === 'locked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Period is locked. Cannot update salary payment in a locked period.',
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cash,transfer',
            'amount'         => 'required|numeric|min:1',
            'notes'          => 'nullable|string|max:500',
            'proof_img'      => 'required|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $balance = $salaryReport->balance;
            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance not found for this salary record.',
                ], 400);
            }

            $oldAmount        = (float) $salaryReport->amount;
            $oldPaymentMethod = $salaryReport->payment_method;
            $newAmount        = (float) $request->amount;
            $newPaymentMethod = $request->payment_method;

            // Restore old balance deduction (if any)
            if (in_array($oldPaymentMethod, ['cash', 'transfer']) && $oldAmount > 0) {
                if ($oldPaymentMethod === 'cash') {
                    $balance->cash_balance += $oldAmount;
                } else {
                    $balance->transfer_balance += $oldAmount;
                }
            }

            // Deduct new balance
            if ($newPaymentMethod === 'cash') {
                if ($balance->cash_balance < $newAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient cash balance. Available: Rp ' . number_format($balance->cash_balance, 0, ',', '.'),
                    ], 400);
                }
                $balance->cash_balance -= $newAmount;
            } elseif ($newPaymentMethod === 'transfer') {
                if ($balance->transfer_balance < $newAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient transfer balance. Available: Rp ' . number_format($balance->transfer_balance, 0, ',', '.'),
                    ], 400);
                }
                $balance->transfer_balance -= $newAmount;
            }

            // Handle proof image upload
            $proofImgPath = $salaryReport->proof_img;
            if ($request->hasFile('proof_img')) {
                // Delete old proof image
                if ($salaryReport->proof_img && Storage::disk('local')->exists($salaryReport->proof_img)) {
                    Storage::disk('local')->delete($salaryReport->proof_img);
                }
                $file = $request->file('proof_img');
                $filename = 'salary_' . $salaryReport->employee_salary_id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofImgPath = $file->storeAs('salary_proofs', $filename, 'local');
            }

            // Update salary report
            $salaryReport->update([
                'payment_method' => $newPaymentMethod,
                'amount'         => $newAmount,
                'notes'          => $request->notes,
                'proof_img'      => $proofImgPath,
                'report_status'  => 'fixed',
                'salary_date'    => now()->toDateString(),
            ]);

            // Save updated balance
            if ($newPaymentMethod !== 'null' || in_array($oldPaymentMethod, ['cash', 'transfer'])) {
                $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
                $balance->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Salary payment updated successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serve private proof image.
     */
    public function serveProofImage(SalaryReport $salaryReport)
    {
        if (!$salaryReport->proof_img || !Storage::disk('local')->exists($salaryReport->proof_img)) {
            abort(404);
        }

        $path     = Storage::disk('local')->path($salaryReport->proof_img);
        $mimeType = mime_content_type($path);

        return response()->file($path, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
