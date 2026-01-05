<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class ShippingOrderController extends Controller
{
    /**
     * Display a listing of shipped orders.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');
        
        // Get per_page value with validation
        $perPage = $request->input('per_page', 25);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25, 50, 100]) ? $perPage : 25;

        // Query orders that are finished and shipped
        $query = Order::with([
            'customer',
            'productCategory',
            'invoice',
            'orderItems',
            'designVariants'
        ])
        ->where('production_status', 'finished')
        ->where('shipping_status', 'shipped');

        // Apply filter based on shipping type
        if ($filter === 'pickup') {
            $query->where('shipping_type', 'pickup');
        } elseif ($filter === 'delivery') {
            $query->where('shipping_type', 'delivery');
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
                })
                ->orWhereHas('productCategory', function ($productQuery) use ($search) {
                    $productQuery->where('product_name', 'like', "%{$search}%");
                })
                ->orWhereHas('designVariants', function ($designQuery) use ($search) {
                    $designQuery->where('design_name', 'like', "%{$search}%");
                });
            });
        }

        // Apply date range filter - default to 45 days
        if (!$dateRange && !$startDate && !$endDate) {
            $dateRange = 'default';
        }
        
        if ($dateRange) {
            $today = now();
            switch ($dateRange) {
                case 'default':
                    // Default: Last 45 days - dari 45 hari lalu sampai hari ini
                    $startDate = $today->copy()->subDays(45)->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'last_month':
                    $startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
                case 'this_month':
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        if ($startDate) {
            $query->whereDate('shipping_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('shipping_date', '<=', $endDate);
        }

        // Order by shipping date DESC (newest first)
        $orders = $query->orderBy('shipping_date', 'desc')
            ->paginate($perPage)
            ->appends($request->except('page'));

        // Get all orders with same filters for client-side search
        $allOrdersQuery = Order::with([
            'customer',
            'productCategory',
            'invoice',
            'orderItems',
            'designVariants'
        ])
        ->where('production_status', 'finished')
        ->where('shipping_status', 'shipped');

        // Apply same filter based on shipping type
        if ($filter === 'pickup') {
            $allOrdersQuery->where('shipping_type', 'pickup');
        } elseif ($filter === 'delivery') {
            $allOrdersQuery->where('shipping_type', 'delivery');
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
                })
                ->orWhereHas('productCategory', function ($productQuery) use ($search) {
                    $productQuery->where('product_name', 'like', "%{$search}%");
                })
                ->orWhereHas('designVariants', function ($designQuery) use ($search) {
                    $designQuery->where('design_name', 'like', "%{$search}%");
                });
            });
        }

        // Apply same date range filter
        if ($startDate) {
            $allOrdersQuery->whereDate('shipping_date', '>=', $startDate);
        }
        if ($endDate) {
            $allOrdersQuery->whereDate('shipping_date', '<=', $endDate);
        }

        // Same ordering
        $allOrders = $allOrdersQuery->orderBy('shipping_date', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total_shipped' => Order::where('production_status', 'finished')
                ->where('shipping_status', 'shipped')
                ->count(),
            'pickup' => Order::where('production_status', 'finished')
                ->where('shipping_status', 'shipped')
                ->where('shipping_type', 'pickup')
                ->count(),
            'delivery' => Order::where('production_status', 'finished')
                ->where('shipping_status', 'shipped')
                ->where('shipping_type', 'delivery')
                ->count(),
        ];

        // Check if AJAX request
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('pages.admin.shipping-orders.index', compact('orders', 'allOrders', 'stats', 'dateRange', 'startDate', 'endDate'))->render();
        }

        return view('pages.admin.shipping-orders.index', compact('orders', 'allOrders', 'stats', 'dateRange', 'startDate', 'endDate'));
    }
}
