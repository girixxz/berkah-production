<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

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

        return view('pages.owner.dashboard', compact('stats', 'dateRange', 'startDate', 'endDate'));
    }
}
