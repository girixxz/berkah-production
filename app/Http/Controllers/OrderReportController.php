<?php

namespace App\Http\Controllers;

use App\Models\OrderReport;
use App\Models\Order;
use App\Models\ReportPeriod;
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
                    'lock_status' => 'draft'
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
                    'lock_status' => $action === 'lock' ? 'locked' : 'draft'
                ]
            );

            // Update all order_reports in this period
            OrderReport::whereYear('period_start', $year)
                ->whereMonth('period_start', $month)
                ->update(['lock_status' => $action === 'lock' ? 'locked' : 'draft']);

            DB::commit();

            $message = $action === 'lock' 
                ? 'Period locked successfully. All reports in this period are now locked.' 
                : 'Period unlocked successfully. All reports are now draft.';

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'lock_status' => $action === 'lock' ? 'locked' : 'draft'
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
     * Toggle lock status (owner only)
     */
    public function toggleLock(OrderReport $orderReport)
    {
        // Check if user is owner
        if (auth()->user()->role !== 'owner') {
            return redirect()->back()
                ->with('message', 'Unauthorized action.')
                ->with('alert-type', 'error');
        }

        try {
            DB::beginTransaction();

            if ($orderReport->lock_status === 'draft') {
                $orderReport->lock();
                $message = 'Report locked successfully';
            } else {
                $orderReport->unlock();
                $message = 'Report unlocked successfully';
            }

            DB::commit();

            return redirect()->back()
                ->with('message', $message)
                ->with('alert-type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('message', 'Failed to toggle lock status: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
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

            // Get order and invoice
            $order = $orderReport->order;
            if (!$order || !$order->invoice) {
                throw new \Exception('Order or invoice not found');
            }

            // Get all approved payments for this order
            $payments = $order->invoice->payments()
                ->where('status', 'approved')
                ->get();

            // Calculate total transfer and cash to be deducted from balance
            $transferTotal = $payments->where('payment_method', 'transfer')->sum('amount');
            $cashTotal = $payments->where('payment_method', 'cash')->sum('amount');
            $totalAmount = $transferTotal + $cashTotal;

            // Find balance for this period
            $balance = \App\Models\Balance::where('period_start', $orderReport->period_start)
                ->where('period_end', $orderReport->period_end)
                ->first();

            // Deduct from balance if exists
            if ($balance) {
                $balance->decrement('transfer_balance', $transferTotal);
                $balance->decrement('cash_balance', $cashTotal);
                $balance->decrement('total_balance', $totalAmount);
            }

            // Update order report_status back to 'pending'
            $order->update([
                'report_status' => 'pending',
                'report_date' => null,
            ]);

            // Delete the report
            $orderReport->delete();

            DB::commit();

            return redirect()->back()
                ->with('message', 'Report removed successfully and balance updated')
                ->with('alert-type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('message', 'Failed to remove report: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
    }
}
