<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HighlightController extends Controller
{
    public function index(Request $request)
    {
        // Base query for WIP and Finished orders
        $query = Order::with([
            'customer',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'invoice',
            'designVariants',
            'orderStages'
        ])->whereIn('production_status', ['wip', 'finished']);

        // Apply filter
        $filter = $request->get('filter', 'all');
        if ($filter === 'wip') {
            $query->where('production_status', 'wip');
        } elseif ($filter === 'finished') {
            $query->where('production_status', 'finished');
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%");
                })
                ->orWhereHas('productCategory', function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%");
                })
                ->orWhereHas('invoice', function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Apply date filter - Default to This Month
        $startDate = null;
        $endDate = null;
        $dateRange = $request->get('date_range', 'this_month'); // Default to this_month

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } elseif (!$request->filled('start_date') && !$request->filled('end_date')) {
            // Apply default This Month if no custom dates
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        // Get orders with pagination
        $orders = $query->orderBy('order_date', 'desc')->paginate(15);

        // Calculate statistics
        $allOrders = Order::whereIn('production_status', ['wip', 'finished']);
        
        // Apply same date filter to stats if exists
        if ($startDate && $endDate) {
            $allOrders->whereBetween('order_date', [$startDate, $endDate]);
        }

        $stats = [
            'total_orders' => $allOrders->count(),
            'wip' => (clone $allOrders)->where('production_status', 'wip')->count(),
            'finished' => (clone $allOrders)->where('production_status', 'finished')->count(),
        ];

        // Handle AJAX requests for pagination
        if ($request->ajax()) {
            return view('pages.highlights', compact('orders', 'stats', 'startDate', 'endDate', 'dateRange'))->render();
        }

        return view('pages.highlights', compact('orders', 'stats', 'startDate', 'endDate', 'dateRange'));
    }
}
