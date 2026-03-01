<?php

namespace App\Http\Controllers;

use App\Models\OrderReport;
use App\Models\ReportPeriod;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderReportController extends Controller
{
    /**
     * Display a listing of report orders
     */
    public function index(Request $request)
    {
        // Get month and year from request or use current
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $perPage = $request->input('per_page', 25);

        // Calculate period start and end
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Check and auto-create report_period for current/future months (draft)
        $currentDate = now();
        $requestDate = Carbon::create($year, $month, 1);
        
        if ($requestDate->gte($currentDate->startOfMonth())) {
            ReportPeriod::firstOrCreate(
                [
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'lock_status' => 'unlocked'
                ]
            );
        }

        // Get current period status
        $currentPeriod = ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        // Get all order reports for this period with relations (paginated)
        $orderReports = OrderReport::whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->with(['order.customer', 'order.invoice', 'order.productCategory', 'order.orderItems' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Calculate statistics (use all data, not paginated)
        $allReports = OrderReport::whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->with(['order.customer', 'order.invoice', 'order.productCategory', 'order.orderItems' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->get();

        $stats = [
            'total_orders' => $allReports->count(),
            'total_qty' => $allReports->sum(fn($report) => $report->order->orderItems->sum('qty') ?? 0),
            'total_bill' => $allReports->sum(fn($report) => $report->order->invoice->total_bill ?? 0),
            'total_paid' => $allReports->sum(fn($report) => $report->order->invoice->amount_paid ?? 0),
            'remaining_due' => $allReports->sum(fn($report) => $report->order->invoice->amount_due ?? 0),
            'tshirt_count' => $allReports->where('product_type', 't-shirt')->count(),
            'makloon_count' => $allReports->where('product_type', 'makloon')->count(),
            'hoodie_count' => $allReports->where('product_type', 'hoodie_polo_jersey')->count(),
            'pants_count' => $allReports->where('product_type', 'pants')->count(),
        ];

        // Paginate each product type separately
        $reportsByType = [
            't-shirt' => OrderReport::whereYear('period_start', $year)
                ->whereMonth('period_start', $month)
                ->where('product_type', 't-shirt')
                ->with(['order.customer', 'order.invoice', 'order.productCategory', 'order.orderItems'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'tshirt_page'),
            'makloon' => OrderReport::whereYear('period_start', $year)
                ->whereMonth('period_start', $month)
                ->where('product_type', 'makloon')
                ->with(['order.customer', 'order.invoice', 'order.productCategory', 'order.orderItems'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'makloon_page'),
            'hoodie_polo_jersey' => OrderReport::whereYear('period_start', $year)
                ->whereMonth('period_start', $month)
                ->where('product_type', 'hoodie_polo_jersey')
                ->with(['order.customer', 'order.invoice', 'order.productCategory', 'order.orderItems'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'hoodie_page'),
            'pants' => OrderReport::whereYear('period_start', $year)
                ->whereMonth('period_start', $month)
                ->where('product_type', 'pants')
                ->with(['order.customer', 'order.invoice', 'order.productCategory', 'order.orderItems'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'pants_page'),
        ];

        return view('pages.finance.report.order-list.index', compact(
            'orderReports',
            'stats',
            'reportsByType',
            'month',
            'year',
            'currentPeriod'
        ));
    }

    /**
     * Toggle lock status for entire period
     */
    public function togglePeriodLock(Request $request)
    {
        // Check if user is owner
        if (auth()->user()->role !== 'owner') {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.'
                ], 403);
            }
            return redirect()->back()
                ->with('toast_message', 'Unauthorized action.')
                ->with('toast_type', 'error');
        }

        $month = $request->input('month');
        $year = $request->input('year');
        $action = $request->input('action'); // 'lock' or 'unlock'

        try {
            DB::beginTransaction();

            // Calculate period
            $periodStart = Carbon::create($year, $month, 1)->startOfDay();
            $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

            // Update or create report_period
            $reportPeriod = ReportPeriod::updateOrCreate(
                [
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'lock_status' => $action === 'lock' ? 'locked' : 'unlocked'
                ]
            );

            DB::commit();

            $message = $action === 'lock' 
                ? 'Period locked successfully. All reports in this period are now locked.' 
                : 'Period unlocked successfully. All reports are now draft.';

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'lock_status' => $action === 'lock' ? 'locked' : 'unlocked'
                ]);
            }

            return redirect()->back()
                ->with('toast_message', $message)
                ->with('toast_type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to ' . $action . ' period: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('toast_message', 'Failed to ' . $action . ' period: ' . $e->getMessage())
                ->with('toast_type', 'error');
        }
    }

    /**
     * Update report period and product type
     */
    public function update(Request $request, OrderReport $orderReport)
    {
        // Cannot edit locked reports
        if ($orderReport->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit a locked report.'
            ], 403);
        }

        $validated = $request->validate([
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2025',
            'product_type' => 'required|in:t-shirt,makloon,hoodie_polo_jersey,pants',
        ]);

        try {
            DB::beginTransaction();

            $oldPeriodStart = Carbon::parse($orderReport->period_start);
            $newPeriodStart = Carbon::create($validated['year'], $validated['month'], 1)->startOfDay();
            $newPeriodEnd   = Carbon::create($validated['year'], $validated['month'], 1)->endOfMonth()->endOfDay();

            $periodChanged = $oldPeriodStart->format('Y-m') !== $newPeriodStart->format('Y-m');

            if ($periodChanged) {
                $order    = $orderReport->order;
                $payments = $order->invoice->payments()->where('status', 'approved')->get();

                $payTransfer = (float) $payments->where('payment_method', 'transfer')->sum('amount');
                $payCash     = (float) $payments->where('payment_method', 'cash')->sum('amount');

                // Aggregate material expenses by payment method
                $materials    = $orderReport->materialReports()->get();
                $matCash      = (float) $materials->where('payment_method', 'cash')->sum('amount');
                $matTransfer  = (float) $materials->where('payment_method', 'transfer')->sum('amount');

                // Aggregate partner expenses by payment method
                $partners     = $orderReport->partnerReports()->get();
                $partCash     = (float) $partners->where('payment_method', 'cash')->sum('amount');
                $partTransfer = (float) $partners->where('payment_method', 'transfer')->sum('amount');

                // Net contribution of this order to old balance
                $netTransfer = $payTransfer - $matTransfer - $partTransfer;
                $netCash     = $payCash     - $matCash     - $partCash;
                $netTotal    = $netTransfer + $netCash;

                // 1. Check if removing this order from old balance would cause it to go negative
                $oldBalance = Balance::whereYear('period_start', $oldPeriodStart->year)
                    ->whereMonth('period_start', $oldPeriodStart->month)
                    ->first();

                if ($oldBalance && $netTotal > 0) {
                    $projectedTotal    = (float) $oldBalance->total_balance    - $netTotal;
                    $projectedTransfer = (float) $oldBalance->transfer_balance - $netTransfer;
                    $projectedCash     = (float) $oldBalance->cash_balance     - $netCash;

                    if ($projectedTotal < 0 || $projectedTransfer < 0 || $projectedCash < 0) {
                        DB::rollBack();
                        $oldPeriodLabel = $oldPeriodStart->format('F Y');
                        return response()->json([
                            'success' => false,
                            'message' => "Cannot move this report. Removing it would cause the {$oldPeriodLabel} balance to go negative (other expenses like operational costs rely on this order's income)."
                        ], 422);
                    }
                }

                // 2. REVERSE net effect from OLD balance
                if ($oldBalance) {
                    $oldBalance->decrement('transfer_balance', $netTransfer);
                    $oldBalance->decrement('cash_balance',     $netCash);
                    $oldBalance->decrement('total_balance',    $netTotal);
                }

                // 3. Find or Create NEW balance
                $newBalance = Balance::whereYear('period_start', $validated['year'])
                    ->whereMonth('period_start', $validated['month'])
                    ->first();

                if (!$newBalance) {
                    $newBalance = Balance::create([
                        'period_start'     => $newPeriodStart->toDateString(),
                        'period_end'       => $newPeriodEnd->toDateString(),
                        'total_balance'    => 0,
                        'transfer_balance' => 0,
                        'cash_balance'     => 0,
                    ]);
                }

                // 4. APPLY net effect to NEW balance
                $newBalance->increment('transfer_balance', $netTransfer);
                $newBalance->increment('cash_balance',     $netCash);
                $newBalance->increment('total_balance',    $netTotal);

                // 5. Cascade balance_id on Material & Partner reports
                $orderReport->materialReports()->update(['balance_id' => $newBalance->id]);
                $orderReport->partnerReports()->update(['balance_id' => $newBalance->id]);
            }

            // 5. Update OrderReport
            $orderReport->update([
                'period_start' => $newPeriodStart->toDateString(),
                'period_end'   => $newPeriodEnd->toDateString(),
                'product_type' => $validated['product_type'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Report updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle lock status (owner only) - DEPRECATED: lock is now period-level only
     */
    public function toggleLock(OrderReport $orderReport)
    {
        return response()->json(['success' => false, 'message' => 'Per-report locking is no longer supported. Use period lock instead.'], 400);
    }

    /**
     * Remove the specified report from storage
     */
    public function destroy(OrderReport $orderReport)
    {
        // Check if report is locked
        if ($orderReport->isLocked()) {
            return redirect()->back()
                ->with('message', 'Cannot delete locked report.')
                ->with('alert-type', 'error');
        }

        // Check role authorization
        $userRole = auth()->user()->role;
        if (!in_array($userRole, ['owner', 'finance'])) {
            return redirect()->back()
                ->with('message', 'Unauthorized action.')
                ->with('alert-type', 'error');
        }

        try {
            DB::beginTransaction();

            $order = $orderReport->order;
            if (!$order || !$order->invoice) {
                throw new \Exception('Order or invoice not found');
            }

            // Approved payments (income added to balance)
            $payments    = $order->invoice->payments()->where('status', 'approved')->get();
            $payTransfer = (float) $payments->where('payment_method', 'transfer')->sum('amount');
            $payCash     = (float) $payments->where('payment_method', 'cash')->sum('amount');

            // Material expenses (deducted from balance)
            $materials   = $orderReport->materialReports()->get();
            $matTransfer = (float) $materials->where('payment_method', 'transfer')->sum('amount');
            $matCash     = (float) $materials->where('payment_method', 'cash')->sum('amount');

            // Partner expenses (deducted from balance)
            $partners    = $orderReport->partnerReports()->get();
            $partTransfer = (float) $partners->where('payment_method', 'transfer')->sum('amount');
            $partCash     = (float) $partners->where('payment_method', 'cash')->sum('amount');

            // Net contribution of this order to balance
            $netTransfer = $payTransfer - $matTransfer - $partTransfer;
            $netCash     = $payCash     - $matCash     - $partCash;
            $netTotal    = $netTransfer + $netCash;

            // Find balance for this period
            $periodStart = Carbon::parse($orderReport->period_start);
            $balance = Balance::whereYear('period_start', $periodStart->year)
                ->whereMonth('period_start', $periodStart->month)
                ->first();

            // Check if removing this report would cause balance to go negative
            if ($balance && $netTotal > 0) {
                $projectedTotal    = (float) $balance->total_balance    - $netTotal;
                $projectedTransfer = (float) $balance->transfer_balance - $netTransfer;
                $projectedCash     = (float) $balance->cash_balance     - $netCash;

                if ($projectedTotal < 0 || $projectedTransfer < 0 || $projectedCash < 0) {
                    DB::rollBack();
                    $periodLabel = $periodStart->format('F Y');
                    return redirect()->back()
                        ->with('message', "Cannot delete this report. Removing it would cause the {$periodLabel} balance to go negative (other expenses rely on this order's income).")
                        ->with('alert-type', 'error');
                }
            }

            // Deduct net from balance
            if ($balance) {
                $balance->decrement('transfer_balance', $netTransfer);
                $balance->decrement('cash_balance',     $netCash);
                $balance->decrement('total_balance',    $netTotal);
            }

            // Update order report_status back to 'pending'
            $order->update([
                'report_status' => 'pending',
                'report_date'   => null,
            ]);

            // Delete the report (cascades to material & partner reports if FK set)
            $orderReport->delete();

            DB::commit();

            return redirect()->back()
                ->with('message', 'Report removed successfully and balance updated.')
                ->with('alert-type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('message', 'Failed to remove report: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
    }
}
