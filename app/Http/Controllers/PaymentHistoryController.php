<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'default');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');

        $query = Payment::with(['invoice.order.customer', 'invoice.order.productCategory']);

        // Apply payment type filter or status filter
        if ($filter === 'pending' || $filter === 'approved' || $filter === 'rejected') {
            // Status filter
            $query->where('status', $filter);
        } elseif ($filter !== 'default') {
            // Payment type filter
            $query->where('payment_type', $filter);
        }

        // Apply search (customer name or invoice no)
        if ($search) {
            $query->whereHas('invoice', function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('order.customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('customer_name', 'like', "%{$search}%");
                    });
            });
        }

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
                    $endDate = $today->format('Y-m-d');
                    break;
                case 'yesterday':
                    $startDate = $endDate = $today->copy()->subDay()->format('Y-m-d');
                    break;
                case 'today':
                    $startDate = $endDate = $today->format('Y-m-d');
                    break;
                case 'this_month':
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        // Set default to this month if no date parameters at all
        if (!$dateRange && !$startDate && !$endDate) {
            $role = Auth::user()->role;
            $routeName = $role === 'owner' ? 'owner.payment-history' : 'admin.payment-history';
            
            return redirect()->route($routeName, [
                'filter' => $filter,
                'search' => $search,
                'date_range' => 'this_month',
            ]);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('paid_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // Calculate statistics based on the same filters (no cache, real-time)
        $statsQuery = Payment::query();
        
        // Apply same date filter to stats
        if ($startDate && $endDate) {
            $statsQuery->whereBetween('paid_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }
        
        $stats = [
            'total_transactions' => (clone $statsQuery)->count(),
            'total_balance' => (clone $statsQuery)->where('status', 'approved')->sum('amount'), // Only approved
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
        ];

        // Get paginated payments
        $payments = $query->orderBy('paid_at', 'desc')
            ->paginate(15)
            ->appends($request->except('page'));

        return view('pages.admin.payment-history', compact('payments', 'stats', 'startDate', 'endDate', 'dateRange'));
    }
}
