<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Invoice;
use App\Models\OrderMaterialReport;
use App\Models\OrderPartnerReport;
use App\Models\OrderReport;
use App\Models\OperationalReport;
use App\Models\SalaryReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $currentDate = Carbon::createFromDate($year, $month, 1);
        $periodStart = $currentDate->copy()->startOfMonth()->toDateString();
        $periodEnd = $currentDate->copy()->endOfMonth()->toDateString();

        // ==================== CARD STATS ====================

        // Get reported order invoices for this period
        $reportedInvoiceIds = OrderReport::where('period_start', $periodStart)
            ->whereNotNull('invoice_id')
            ->pluck('invoice_id')
            ->unique();

        // Total Bill: sum of total_bill from reported invoices
        $totalBill = Invoice::whereIn('id', $reportedInvoiceIds)->sum('total_bill');

        // Total Income: sum of approved payments from reported invoices in this period
        $totalIncome = Invoice::whereIn('id', $reportedInvoiceIds)->sum('amount_paid');

        // Remaining: unpaid from reported invoices
        $remaining = Invoice::whereIn('id', $reportedInvoiceIds)->sum('amount_due');

        // Total Expense breakdown (filter by report period, not by transaction date)
        $materialExpense = OrderMaterialReport::whereHas('orderReport', function ($q) use ($periodStart) {
            $q->where('period_start', $periodStart);
        })->sum('amount');

        $partnerExpense = OrderPartnerReport::whereHas('orderReport', function ($q) use ($periodStart) {
            $q->where('period_start', $periodStart);
        })->sum('amount');

        $operationalExpense = OperationalReport::whereHas('balance', function ($q) use ($periodStart) {
            $q->where('period_start', $periodStart);
        })->sum('amount');

        $salaryExpense = SalaryReport::whereHas('balance', function ($q) use ($periodStart) {
            $q->where('period_start', $periodStart);
        })->sum('amount');

        $totalExpense = $materialExpense + $partnerExpense + $operationalExpense + $salaryExpense;

        // Net Profit: Total Bill - Total Expense
        $netIncome = $totalBill - $totalExpense;

        // Saving Rate: (Net Income / Total Bill) * 100%
        $savingRate = $totalBill > 0 ? ($netIncome / $totalBill) * 100 : 0;

        // Balance for this period
        $balance = Balance::where('period_start', $periodStart)->first();
        $totalBalance = $balance ? $balance->total_balance : 0;
        $transferBalance = $balance ? $balance->transfer_balance : 0;
        $cashBalance = $balance ? $balance->cash_balance : 0;

        // ==================== PRODUCT CATEGORY SUMMARY ====================

        // Fixed 4 categories matching order report product types
        $categoryTypes = [
            't-shirt'           => 'T-Shirt',
            'makloon'           => 'Makloon',
            'hoodie_polo_jersey' => 'Hoodie / Polo / Jersey',
            'pants'             => 'Pants',
        ];

        // Get aggregated data from order_reports → orders for this period
        $categoryData = DB::table('order_reports')
            ->join('orders', 'orders.id', '=', 'order_reports.order_id')
            ->where('order_reports.period_start', $periodStart)
            ->select(
                'order_reports.product_type',
                DB::raw('SUM(orders.total_qty) as total_qty'),
                DB::raw('SUM(orders.grand_total) as total_bill')
            )
            ->groupBy('order_reports.product_type')
            ->get()
            ->keyBy('product_type');

        // Build fixed collection — always 4 rows
        $categorySummary = collect($categoryTypes)->map(function ($label, $key) use ($categoryData) {
            $data = $categoryData[$key] ?? null;
            return (object) [
                'product_type' => $key,
                'label'        => $label,
                'total_qty'    => $data ? (int) $data->total_qty : 0,
                'total_bill'   => $data ? (float) $data->total_bill : 0,
            ];
        })->values();

        return view('pages.finance.dashboard', compact(
            'currentDate',
            'totalBill',
            'totalExpense',
            'netIncome',
            'totalIncome',
            'remaining',
            'savingRate',
            'totalBalance',
            'transferBalance',
            'cashBalance',
            'materialExpense',
            'partnerExpense',
            'operationalExpense',
            'salaryExpense',
            'categorySummary'
        ));
    }
}
