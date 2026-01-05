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
        
        // Per page validation
        $perPage = $request->input('per_page', 25);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25, 50, 100]) ? $perPage : 25;

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

        // Set default to 45 days if no date parameters at all
        if (!$dateRange && !$startDate && !$endDate) {
            $dateRange = 'default';
        }

        // Apply date range filter
        if ($dateRange) {
            $today = now();
            switch ($dateRange) {
                case 'default':
                    $startDate = $today->copy()->subDays(45)->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'this_month':
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_month':
                    $startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
            }
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
            ->paginate($perPage)
            ->appends($request->except('page'));

        // Get all payments for search functionality (with same filters)
        $allPaymentsQuery = Payment::with(['invoice.order.customer', 'invoice.order.productCategory']);

        // Apply same filter to allPayments
        if ($filter === 'pending' || $filter === 'approved' || $filter === 'rejected') {
            $allPaymentsQuery->where('status', $filter);
        } elseif ($filter !== 'default') {
            $allPaymentsQuery->where('payment_type', $filter);
        }

        // Apply same date filter to allPayments
        if ($startDate && $endDate) {
            $allPaymentsQuery->whereBetween('paid_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // Get all payments with same sorting
        $allPayments = $allPaymentsQuery->orderBy('paid_at', 'desc')->get();

        // AJAX support - return rendered HTML for AJAX requests
        if ($request->ajax() || $request->wantsJson() || 
            $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('pages.admin.payment-history', compact('payments', 'allPayments', 'stats', 'startDate', 'endDate', 'dateRange'))->render();
        }
        
        return view('pages.admin.payment-history', compact('payments', 'allPayments', 'stats', 'startDate', 'endDate', 'dateRange'));
    }
}
