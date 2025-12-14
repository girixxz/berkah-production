<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStage;
use App\Models\ProductionStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'default');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');
        
        // Per page validation
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25]) ? $perPage : 15;

        // Admin can also edit tasks now
        $isViewOnly = false;

        // Get all production stages
        $productionStages = ProductionStage::orderBy('id')->get();

        $query = Order::with([
            'customer',
            'invoice.payments',
            'productCategory',
            'orderStages.productionStage'
        ])
        // Only show orders with WIP or Finished status
        ->whereIn('production_status', ['wip', 'finished']);

        // Apply filter based on production status
        if ($filter === 'wip') {
            $query->where('production_status', 'wip');
        } elseif ($filter === 'finished') {
            $query->where('production_status', 'finished');
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('invoice', function ($invoiceQuery) use ($search) {
                    $invoiceQuery->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        // Apply date range filter
        // Default to this month if no date filter is applied
        if ($dateRange) {
            $today = now();
            switch ($dateRange) {
                case 'last_month':
                    $startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_7_days':
                    $startDate = $today->copy()->subDays(7)->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'yesterday':
                    $startDate = $today->copy()->subDay()->format('Y-m-d');
                    $endDate = $today->copy()->subDay()->format('Y-m-d');
                    break;
                case 'today':
                    $startDate = $today->copy()->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'this_month':
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }
        
        // Set default to this month if no date parameters at all
        if (!$dateRange && !$startDate && !$endDate) {
            $routeName = $isViewOnly ? 'admin.manage-task' : 'pm.manage-task';
            $redirect = redirect()->route($routeName, [
                'filter' => $filter,
                'search' => $search,
                'date_range' => 'this_month',
            ]);
            
            if (session()->has('message')) {
                $redirect->with('message', session('message'))
                        ->with('alert-type', session('alert-type', 'success'));
            }
            
            return $redirect;
        }

        if ($startDate) {
            $query->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('order_date', '<=', $endDate);
        }

        // Sort by wip_date for default & wip filter, finished_date for finished filter
        // DESC = data baru di atas (dari bawah ke atas)
        if ($filter === 'finished') {
            // For finished orders, sort by finished_date (newest first)
            $orders = $query
                ->orderBy('finished_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($request->except('page'));
        } else {
            // For default & wip, sort by wip_date (newest first)
            $orders = $query
                ->orderBy('wip_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($request->except('page'));
        }

        // Get all orders with same filters for client-side search
        $allOrdersQuery = Order::with([
            'customer',
            'invoice.payments',
            'productCategory',
            'orderStages.productionStage'
        ])
        ->whereIn('production_status', ['wip', 'finished']);

        // Apply same filter based on production status
        if ($filter === 'wip') {
            $allOrdersQuery->where('production_status', 'wip');
        } elseif ($filter === 'finished') {
            $allOrdersQuery->where('production_status', 'finished');
        }

        // Apply same search
        if ($search) {
            $allOrdersQuery->where(function ($q) use ($search) {
                $q->whereHas('invoice', function ($invoiceQuery) use ($search) {
                    $invoiceQuery->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        // Apply same date range filter
        if ($startDate) {
            $allOrdersQuery->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $allOrdersQuery->whereDate('order_date', '<=', $endDate);
        }

        // Apply same sorting
        if ($filter === 'finished') {
            $allOrders = $allOrdersQuery
                ->orderBy('finished_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $allOrders = $allOrdersQuery
                ->orderBy('wip_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Calculate statistics
        $stats = [
            'total_orders' => Order::whereIn('production_status', ['wip', 'finished'])->count(),
            'order_wip' => Order::where('production_status', 'wip')->count(),
            'order_finished' => Order::where('production_status', 'finished')->count(),
        ];

        // AJAX support - return rendered HTML for AJAX requests
        if ($request->ajax() || $request->wantsJson() || 
            $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('pages.pm.manage-task', compact('orders', 'allOrders', 'stats', 'productionStages', 'dateRange', 'startDate', 'endDate', 'isViewOnly'))->render();
        }
        
        return view('pages.pm.manage-task', compact('orders', 'allOrders', 'stats', 'productionStages', 'dateRange', 'startDate', 'endDate', 'isViewOnly'));
    }

    /**
     * Update order stage dates
     */
    public function updateStage(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'stage_id' => 'required|exists:production_stages,id',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
        ]);

        DB::beginTransaction();
        try {
            // Find or create order stage
            $orderStage = OrderStage::firstOrCreate(
                [
                    'order_id' => $validated['order_id'],
                    'stage_id' => $validated['stage_id'],
                ],
                [
                    'status' => 'pending',
                ]
            );

            // Update dates
            $orderStage->update([
                'start_date' => $validated['start_date'],
                'deadline' => $validated['deadline'],
                'status' => $this->calculateStageStatus($validated['start_date'], $validated['deadline']),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stage dates updated successfully',
                'data' => $orderStage->load('productionStage')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stage dates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stage status
     */
    public function updateStageStatus(Request $request)
    {
        $validated = $request->validate([
            'order_stage_id' => 'required|exists:order_stages,id',
            'status' => 'required|in:pending,in_progress,done',
        ]);

        try {
            $orderStage = OrderStage::findOrFail($validated['order_stage_id']);
            $orderStage->update(['status' => $validated['status']]);
            
            // Auto-check if all stages are done and update order production status
            $order = $orderStage->order;
            $order->refresh(); // Refresh to get latest stage data
            $statusChanged = $order->checkAndUpdateProductionStatus();
            
            $message = 'Stage status updated successfully';
            if ($statusChanged) {
                if ($order->production_status === 'finished') {
                    $message .= '. All stages completed - Order marked as Finished!';
                } else if ($order->production_status === 'wip') {
                    $message .= '. Order status reverted to WIP';
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $orderStage->load('productionStage'),
                'production_status_changed' => $statusChanged,
                'new_production_status' => $order->production_status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stage status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate stage status based on dates and current time
     */
    private function calculateStageStatus($startDate, $deadline)
    {
        // If no dates set, status is pending
        if (!$startDate || !$deadline) {
            return 'pending';
        }

        $today = now()->startOfDay();
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($deadline)->startOfDay();

        // If current date is before start date, status is pending
        if ($today->lt($start)) {
            return 'pending';
        }

        // If current date is between start and deadline (inclusive), status is in_progress
        if ($today->between($start, $end)) {
            return 'in_progress';
        }

        // If current date is after deadline, keep as in_progress (can be manually set to done)
        return 'in_progress';
    }
}
