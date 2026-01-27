<?php

namespace App\Http\Controllers;

use App\Models\OrderReport;
use App\Models\Order;
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

        // Group by product type (sorted desc - newest first)
        $reportsByType = [
            't-shirt' => $allReports->where('product_type', 't-shirt')->sortByDesc('created_at')->values(),
            'makloon' => $allReports->where('product_type', 'makloon')->sortByDesc('created_at')->values(),
            'hoodie_polo_jersey' => $allReports->where('product_type', 'hoodie_polo_jersey')->sortByDesc('created_at')->values(),
            'pants' => $allReports->where('product_type', 'pants')->sortByDesc('created_at')->values(),
        ];

        return view('pages.finance.report.order-list.index', compact(
            'orderReports',
            'stats',
            'reportsByType',
            'month',
            'year'
        ));
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
