<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OperationalReport;
use App\Models\OperationalList;
use App\Models\OperationalExtract;
use App\Models\Balance;
use App\Models\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OperationalReportController extends Controller
{
    /**
     * Display operational reports grouped by category for a given month/year.
     */
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        // Fetch all operational reports for the period
        $reports = OperationalReport::whereYear('operational_date', $year)
            ->whereMonth('operational_date', $month)
            ->orderBy('operational_date', 'desc')
            ->get();

        // Group by category
        $fixCost1       = $reports->where('category', 'fix_cost_1')->values();
        $fixCost2       = $reports->where('category', 'fix_cost_2')->values();
        $printingSupply = $reports->where('category', 'printing_supply')->values();
        $daily          = $reports->where('category', 'daily')->values();

        // Stats
        $stats = [
            'total_operational'  => $reports->sum('amount'),
            'fix_cost_total'     => $fixCost1->sum('amount') + $fixCost2->sum('amount'),
            'printing_supply'    => $printingSupply->sum('amount'),
            'daily_expense'      => $daily->sum('amount'),
        ];

        // Check lock status for the period
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        $reportPeriod = ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();
        $periodLocked = $reportPeriod && $reportPeriod->lock_status === 'locked';

        // Check if extract has been done for this period
        $extractStatus = OperationalExtract::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();
        $hasExtracted = $extractStatus && $extractStatus->is_extracted;

        // Return JSON if AJAX request
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => [
                    'fixCost1'       => $fixCost1,
                    'fixCost2'       => $fixCost2,
                    'printingSupply' => $printingSupply,
                    'daily'          => $daily,
                    'stats'          => $stats,
                    'periodLocked'   => $periodLocked,
                    'hasExtracted'   => $hasExtracted,
                ],
            ]);
        }

        return view('pages.finance.report.operational', compact(
            'fixCost1',
            'fixCost2',
            'printingSupply',
            'daily',
            'stats',
            'month',
            'year',
            'periodLocked',
            'hasExtracted',
        ));
    }

    /**
     * Get operational list items for a given category (for select dropdown).
     */
    public function getOperationalLists(Request $request)
    {
        $category = $request->input('category');

        if (!$category || !in_array($category, ['fix_cost_1', 'fix_cost_2', 'printing_supply'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category',
            ], 400);
        }

        $lists = OperationalList::where('category', $category)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'list_name']);

        return response()->json([
            'success' => true,
            'lists'   => $lists,
        ]);
    }

    /**
     * Check if a period is valid for adding operational expenses.
     */
    public function checkPeriodStatus(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!$month || !$year) {
            return response()->json([
                'success' => false,
                'message' => 'Month and year are required'
            ], 400);
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $reportPeriod = ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if (!$reportPeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found. Please navigate to Order List for this period first to create the period.'
            ], 404);
        }

        if ($reportPeriod->lock_status === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'Period is locked. Cannot add operational expense to a locked period.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Period is valid and ready for operational expense'
        ]);
    }

    /**
     * Store a new operational expense.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'balance_month'    => 'required|integer|min:1|max:12',
            'balance_year'     => 'required|integer|min:2020',
            'category'         => 'required|in:fix_cost_1,fix_cost_2,printing_supply,daily',
            'operational_name' => 'required|string|max:100',
            'payment_method'   => 'required|in:cash,transfer',
            'amount'           => 'required|numeric|min:1',
            'operational_date' => 'required|date',
            'notes'            => 'nullable|string',
            'proof_image'      => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'proof_image2'     => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // Find balance for the period
            $balance = Balance::whereYear('period_start', $request->balance_year)
                ->whereMonth('period_start', $request->balance_month)
                ->first();

            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance not found for the selected period.',
                ], 400);
            }

            // Check balance sufficiency
            $amount = (float) $request->amount;
            if ($request->payment_method === 'cash') {
                if ($balance->cash_balance < $amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient cash balance.',
                    ], 400);
                }
            } else {
                if ($balance->transfer_balance < $amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient transfer balance.',
                    ], 400);
                }
            }

            // Handle image upload (Proof 1)
            $proofImagePath = null;
            if ($request->hasFile('proof_image')) {
                $file     = $request->file('proof_image');
                $filename = 'operational_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofImagePath = $file->storeAs('operational_proofs/proof1', $filename, 'local');
            }

            // Handle image upload (Proof 2)
            $proofImage2Path = null;
            if ($request->hasFile('proof_image2')) {
                $file2     = $request->file('proof_image2');
                $filename2 = 'operational2_' . time() . '_' . uniqid() . '.' . $file2->getClientOriginalExtension();
                $proofImage2Path = $file2->storeAs('operational_proofs/proof2', $filename2, 'local');
            }

            // Create operational report
            $report = OperationalReport::create([
                'balance_id'       => $balance->id,
                'operational_date' => $request->operational_date,
                'operational_type' => 'first_expense',
                'category'         => $request->category,
                'operational_name' => $request->operational_name,
                'amount'           => $amount,
                'notes'            => $request->notes,
                'payment_method'   => $request->payment_method,
                'proof_img'        => $proofImagePath,
                'proof_img2'       => $proofImage2Path,
                'report_status'    => $proofImage2Path ? 'fixed' : 'draft',
            ]);

            // Deduct balance
            if ($request->payment_method === 'cash') {
                $balance->cash_balance -= $amount;
            } else {
                $balance->transfer_balance -= $amount;
            }
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            return response()->json([
                'success' => true,
                'message' => 'Operational expense created successfully!',
                'data'    => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create operational expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing operational expense.
     */
    public function update(Request $request, OperationalReport $operationalReport)
    {
        $validator = Validator::make($request->all(), [
            'operational_name'  => 'required|string|max:100',
            'payment_method'    => 'required|in:cash,transfer',
            'amount'            => 'required|numeric|min:1',
            'operational_date'  => 'required|date',
            'notes'             => 'nullable|string',
            'proof_image'       => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'proof_image2'      => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'remove_proof_image2' => 'nullable|in:1,true',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $oldAmount         = (float) $operationalReport->amount;
            $oldPaymentMethod  = $operationalReport->payment_method;
            $newAmount         = (float) $request->amount;
            $newPaymentMethod  = $request->payment_method;

            // Get balance
            $balance = Balance::find($operationalReport->balance_id);
            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance not found.',
                ], 404);
            }

            // Restore old balance (only if previous method was a real payment)
            if ($oldPaymentMethod === 'cash') {
                $balance->cash_balance += $oldAmount;
            } elseif ($oldPaymentMethod === 'transfer') {
                $balance->transfer_balance += $oldAmount;
            }

            // Check new balance sufficiency
            if ($newPaymentMethod === 'cash') {
                if ($balance->cash_balance < $newAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient cash balance.',
                    ], 400);
                }
                $balance->cash_balance -= $newAmount;
            } else {
                if ($balance->transfer_balance < $newAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient transfer balance.',
                    ], 400);
                }
                $balance->transfer_balance -= $newAmount;
            }

            // Handle image upload if new image provided (Proof 1)
            if ($request->hasFile('proof_image')) {
                // Delete old image
                if ($operationalReport->proof_img && Storage::disk('local')->exists($operationalReport->proof_img)) {
                    Storage::disk('local')->delete($operationalReport->proof_img);
                }
                $file     = $request->file('proof_image');
                $filename = 'operational_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $operationalReport->proof_img = $file->storeAs('operational_proofs/proof1', $filename, 'local');
            }

            // Handle Proof 2
            if ($request->hasFile('proof_image2')) {
                if ($operationalReport->proof_img2 && Storage::disk('local')->exists($operationalReport->proof_img2)) {
                    Storage::disk('local')->delete($operationalReport->proof_img2);
                }
                $file2     = $request->file('proof_image2');
                $filename2 = 'operational2_' . time() . '_' . uniqid() . '.' . $file2->getClientOriginalExtension();
                $operationalReport->proof_img2 = $file2->storeAs('operational_proofs/proof2', $filename2, 'local');
            } elseif ($request->input('remove_proof_image2')) {
                if ($operationalReport->proof_img2 && Storage::disk('local')->exists($operationalReport->proof_img2)) {
                    Storage::disk('local')->delete($operationalReport->proof_img2);
                }
                $operationalReport->proof_img2 = null;
            }

            // Auto-set report_status based on proof_img2
            $operationalReport->report_status = $operationalReport->proof_img2 ? 'fixed' : 'draft';

            // Update record
            $operationalReport->operational_name = $request->operational_name;
            $operationalReport->payment_method   = $newPaymentMethod;
            $operationalReport->amount           = $newAmount;
            $operationalReport->operational_date  = $request->operational_date;
            $operationalReport->notes            = $request->notes;
            $operationalReport->save();

            // Update balance
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            return response()->json([
                'success' => true,
                'message' => 'Operational expense updated successfully!',
                'data'    => $operationalReport,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update operational expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an operational expense and restore the balance.
     */
    public function destroy(Request $request, OperationalReport $operationalReport)
    {
        try {
            $balance = Balance::find($operationalReport->balance_id);

            if (!$balance) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Balance not found!',
                    ], 404);
                }
                return redirect()->back()
                    ->with('toast_message', 'Balance not found!')
                    ->with('toast_type', 'error');
            }

            $amount = (float) $operationalReport->amount;

            // Restore balance (only if a real payment method was used)
            if ($operationalReport->payment_method === 'cash') {
                $balance->cash_balance += $amount;
            } elseif ($operationalReport->payment_method === 'transfer') {
                $balance->transfer_balance += $amount;
            }
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            // Delete proof images
            if ($operationalReport->proof_img && Storage::disk('local')->exists($operationalReport->proof_img)) {
                Storage::disk('local')->delete($operationalReport->proof_img);
            }
            if ($operationalReport->proof_img2 && Storage::disk('local')->exists($operationalReport->proof_img2)) {
                Storage::disk('local')->delete($operationalReport->proof_img2);
            }

            $operationalReport->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Operational expense deleted and balance restored!',
                ]);
            }

            return redirect()->back()
                ->with('toast_message', 'Operational expense deleted and balance restored!')
                ->with('toast_type', 'success');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()
                ->with('toast_message', 'Failed to delete: ' . $e->getMessage())
                ->with('toast_type', 'error');
        }
    }

    /**
     * Store extra operational expense (same operational_name, extra_expense type).
     */
    public function storeExtra(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_id'        => 'required|exists:operational_reports,id',
            'payment_method'   => 'required|in:cash,transfer',
            'amount'           => 'required|numeric|min:1',
            'operational_date' => 'required|date',
            'notes'            => 'nullable|string',
            'proof_image'      => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'proof_image2'     => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $parent  = OperationalReport::findOrFail($request->parent_id);
            $balance = Balance::find($parent->balance_id);

            if (!$balance) {
                return response()->json(['success' => false, 'message' => 'Balance not found.'], 400);
            }

            $amount = (float) $request->amount;

            if ($request->payment_method === 'cash') {
                if ($balance->cash_balance < $amount) {
                    return response()->json(['success' => false, 'message' => 'Insufficient cash balance.'], 400);
                }
            } else {
                if ($balance->transfer_balance < $amount) {
                    return response()->json(['success' => false, 'message' => 'Insufficient transfer balance.'], 400);
                }
            }

            // Proof 1
            $proofImagePath = null;
            if ($request->hasFile('proof_image')) {
                $file           = $request->file('proof_image');
                $filename       = 'operational_extra_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofImagePath = $file->storeAs('operational_proofs/proof1', $filename, 'local');
            }

            // Proof 2
            $proofImage2Path = null;
            if ($request->hasFile('proof_image2')) {
                $file2           = $request->file('proof_image2');
                $filename2       = 'operational2_extra_' . time() . '_' . uniqid() . '.' . $file2->getClientOriginalExtension();
                $proofImage2Path = $file2->storeAs('operational_proofs/proof2', $filename2, 'local');
            }

            $extra = OperationalReport::create([
                'balance_id'       => $parent->balance_id,
                'operational_date' => $request->operational_date,
                'operational_type' => 'extra_expense',
                'category'         => $parent->category,
                'operational_name' => $parent->operational_name,
                'amount'           => $amount,
                'notes'            => $request->notes,
                'payment_method'   => $request->payment_method,
                'proof_img'        => $proofImagePath,
                'proof_img2'       => $proofImage2Path,
                'report_status'    => $proofImage2Path ? 'fixed' : 'draft',
            ]);

            if ($request->payment_method === 'cash') {
                $balance->cash_balance -= $amount;
            } else {
                $balance->transfer_balance -= $amount;
            }
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            return response()->json(['success' => true, 'message' => 'Extra expense created successfully!', 'data' => $extra]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create extra expense: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Extract operational list items into operational reports for FC1, FC2, Printing Supply.
     */
    public function extractFromOperationLists(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'balance_month' => 'required|integer|min:1|max:12',
            'balance_year'  => 'required|integer|min:2020',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $month = (int) $request->balance_month;
        $year  = (int) $request->balance_year;

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Guard: already extracted
        $alreadyExtracted = OperationalExtract::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->where('is_extracted', true)
            ->exists();

        if ($alreadyExtracted) {
            return response()->json(['success' => false, 'message' => 'Data for this period has already been extracted.'], 409);
        }

        // Find balance for the period
        $balance = Balance::whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->first();

        if (!$balance) {
            return response()->json(['success' => false, 'message' => 'Balance not found for the selected period.'], 400);
        }

        try {
            // Get all lists for FC1, FC2, Printing Supply ordered by category + sort_order
            $lists = OperationalList::whereIn('category', ['fix_cost_1', 'fix_cost_2', 'printing_supply'])
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get();

            $now = Carbon::now()->toDateString();

            foreach ($lists as $list) {
                OperationalReport::create([
                    'balance_id'       => $balance->id,
                    'operational_date' => $now,
                    'operational_type' => 'first_expense',
                    'category'         => $list->category,
                    'operational_name' => $list->list_name,
                    'amount'           => 0,
                    'notes'            => null,
                    'payment_method'   => 'null',
                    'proof_img'        => '-',
                    'proof_img2'       => null,
                    'report_status'    => 'draft',
                ]);
            }

            // Mark period as extracted
            OperationalExtract::updateOrCreate(
                ['period_start' => $periodStart->toDateString(), 'period_end' => $periodEnd->toDateString()],
                ['is_extracted' => true]
            );

            return response()->json([
                'success' => true,
                'message' => 'Data extracted successfully! ' . $lists->count() . ' items created.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to extract: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Serve proof image from private storage.
     */
    public function serveImage(OperationalReport $operationalReport)
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$operationalReport->proof_img || !Storage::disk('local')->exists($operationalReport->proof_img)) {
            abort(404, 'Image not found');
        }

        $path     = Storage::disk('local')->path($operationalReport->proof_img);
        $mimeType = Storage::disk('local')->mimeType($operationalReport->proof_img) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    /**
     * Serve proof image 2 from private storage.
     */
    public function serveImage2(OperationalReport $operationalReport)
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$operationalReport->proof_img2 || !Storage::disk('local')->exists($operationalReport->proof_img2)) {
            abort(404, 'Image not found');
        }

        $path     = Storage::disk('local')->path($operationalReport->proof_img2);
        $mimeType = Storage::disk('local')->mimeType($operationalReport->proof_img2) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }
}
