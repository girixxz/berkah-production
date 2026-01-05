<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HighlightController extends Controller
{
    public function index(Request $request)
    {
        // Per page validation
        $perPage = $request->input('per_page', 25);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25, 50, 100]) ? $perPage : 25;

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

        // Apply date filter - Default to 45 days
        $startDate = null;
        $endDate = null;
        $dateRange = $request->get('date_range');

        // Set default to 45 days if no date parameters at all
        if (!$dateRange && !$request->filled('start_date') && !$request->filled('end_date')) {
            $dateRange = 'default';
        }

        // Apply date range filter
        if ($dateRange) {
            $today = now();
            switch ($dateRange) {
                case 'default':
                    $startDate = $today->copy()->subDays(45);
                    $endDate = $today->copy();
                    break;
                case 'this_month':
                    $startDate = $today->copy()->startOfMonth();
                    $endDate = $today->copy()->endOfMonth();
                    break;
                case 'last_month':
                    $startDate = $today->copy()->subMonth()->startOfMonth();
                    $endDate = $today->copy()->subMonth()->endOfMonth();
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $startDate = Carbon::parse($request->start_date)->startOfDay();
                        $endDate = Carbon::parse($request->end_date)->endOfDay();
                    }
                    break;
            }
        }
        
        // Apply date filter based on status
        if ($startDate && $endDate) {
            if ($filter === 'finished') {
                $query->whereBetween('finished_date', [$startDate, $endDate]);
            } else {
                // For all & wip - use wip_date
                $query->whereBetween('wip_date', [$startDate, $endDate]);
            }
        }

        // Apply sorting based on filter with id DESC for consistency
        // All & WIP: sort by wip_date DESC (terbaru di atas)
        // Finished: sort by finished_date DESC
        if ($filter === 'finished') {
            $query->orderBy('finished_date', 'desc')->orderBy('id', 'desc');
        } else {
            // 'all' atau 'wip' pakai wip_date
            $query->orderBy('wip_date', 'desc')->orderBy('id', 'desc');
        }

        // Get orders with pagination
        $orders = $query->paginate($perPage)->appends($request->except('page'));

        // Get all orders for search functionality (with same filters)
        $allOrdersQuery = Order::with([
            'customer',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'invoice',
            'designVariants',
            'orderStages'
        ])->whereIn('production_status', ['wip', 'finished']);

        // Apply same filter to allOrders
        if ($filter === 'wip') {
            $allOrdersQuery->where('production_status', 'wip');
        } elseif ($filter === 'finished') {
            $allOrdersQuery->where('production_status', 'finished');
        }

        // Apply same date filter to allOrders based on status
        if ($startDate && $endDate) {
            if ($filter === 'finished') {
                $allOrdersQuery->whereBetween('finished_date', [$startDate, $endDate]);
            } else {
                // For all & wip - use wip_date
                $allOrdersQuery->whereBetween('wip_date', [$startDate, $endDate]);
            }
        }

        // Apply same sorting to allOrders
        if ($filter === 'finished') {
            $allOrders = $allOrdersQuery->orderBy('finished_date', 'desc')->orderBy('id', 'desc')->get();
        } else {
            $allOrders = $allOrdersQuery->orderBy('wip_date', 'desc')->orderBy('id', 'desc')->get();
        }

        // Calculate statistics
        $statsQuery = Order::whereIn('production_status', ['wip', 'finished']);
        
        // Apply same date filter to stats if exists
        if ($startDate && $endDate) {
            $statsQuery->whereBetween('order_date', [$startDate, $endDate]);
        }

        $stats = [
            'total_orders' => $statsQuery->count(),
            'wip' => (clone $statsQuery)->where('production_status', 'wip')->count(),
            'finished' => (clone $statsQuery)->where('production_status', 'finished')->count(),
        ];

        // Handle AJAX requests for pagination
        if ($request->ajax()) {
            return view('pages.highlights', compact('orders', 'allOrders', 'stats', 'startDate', 'endDate', 'dateRange'))->render();
        }

        return view('pages.highlights', compact('orders', 'allOrders', 'stats', 'startDate', 'endDate', 'dateRange'));
    }
}
