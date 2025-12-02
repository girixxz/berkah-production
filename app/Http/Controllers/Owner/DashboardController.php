<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');

        // Apply date range filter
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

        // Set default to this month if no date parameters
        if (!$dateRange && !$startDate && !$endDate) {
            return redirect()->route('owner.dashboard', [
                'date_range' => 'this_month',
            ]);
        }

        // Calculate statistics
        $statsQuery = Order::query();
        
        // Apply date filter to stats
        if ($startDate) {
            $statsQuery->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $statsQuery->whereDate('order_date', '<=', $endDate);
        }

        $stats = [
            'total_orders' => (clone $statsQuery)->count(),
            'total_qty' => (clone $statsQuery)->join('order_items', 'orders.id', '=', 'order_items.order_id')->sum('order_items.qty'),
            'revenue' => (clone $statsQuery)->where('production_status', 'finished')->join('invoices', 'orders.id', '=', 'invoices.order_id')->sum('invoices.total_bill'),
            'total_bill' => (clone $statsQuery)->join('invoices', 'orders.id', '=', 'invoices.order_id')->sum('invoices.total_bill'),
            'remaining_due' => (clone $statsQuery)->join('invoices', 'orders.id', '=', 'invoices.order_id')->sum('invoices.amount_due'),
            'total_customers' => Customer::count(),
            'total_sales' => Sale::count(),
            'total_products' => ProductCategory::count(),
        ];

        // Get Order By Sales Data (using same date filter as stats)
        $salesData = $this->getOrderBySalesData($startDate, $endDate);

        return view('pages.owner.dashboard', compact('stats', 'salesData', 'dateRange', 'startDate', 'endDate'));
    }

    /**
     * Get Order By Sales data with date filter
     */
    private function getOrderBySalesData($startDate = null, $endDate = null)
    {
        // Get all sales first
        $sales = Sale::all();
        
        $salesData = $sales->map(function($sale) use ($startDate, $endDate) {
            // Query orders for this sales with date filter
            $ordersQuery = Order::where('sales_id', $sale->id);
            
            if ($startDate) {
                $ordersQuery->whereDate('order_date', '>=', $startDate);
            }
            if ($endDate) {
                $ordersQuery->whereDate('order_date', '<=', $endDate);
            }
            
            $orders = $ordersQuery->with(['orderItems', 'invoice'])->get();
            
            // Filter hanya WIP dan Finished (exclude pending & cancelled)
            $validOrders = $orders->whereIn('production_status', ['wip', 'finished']);
            
            // Calculate totals hanya dari WIP & Finished
            $totalOrders = $validOrders->count();
            $totalQty = $validOrders->sum(function($order) {
                return $order->orderItems->sum('qty');
            });
            
            // Revenue dari order WIP dan Finished
            $revenue = $validOrders->sum(function($order) {
                return $order->invoice->total_bill ?? 0;
            });
            
            return (object) [
                'id' => $sale->id,
                'sales_name' => $sale->sales_name,
                'total_orders' => $totalOrders,
                'total_qty' => $totalQty,
                'revenue' => $revenue,
            ];
        })
        ->filter(function($sale) {
            // Only include sales yang punya order
            return $sale->total_orders > 0;
        })
        ->sortByDesc('revenue')
        ->values();
        
        return $salesData;
    }

    /**
     * Get Order Trend data for chart (per day in a month)
     */
    public function getOrderTrendData(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        
        // Get number of days in the selected month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        // Initialize arrays for labels and values
        $labels = [];
        $values = [];
        
        // Loop through each day of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            
            // Count orders for this specific date
            $orderCount = Order::whereDate('order_date', $date)->count();
            
            $labels[] = (string)$day; // Day number (1, 2, 3, ...)
            $values[] = $orderCount;
        }
        
        return response()->json([
            'labels' => $labels,
            'values' => $values,
        ]);
    }
}
