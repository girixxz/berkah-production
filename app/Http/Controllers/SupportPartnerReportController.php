<?php

namespace App\Http\Controllers;

use App\Models\OrderPartnerReport;
use App\Models\OrderReport;
use App\Models\SupportPartner;
use App\Models\Balance;
use App\Models\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SupportPartnerReportController extends Controller
{
    /**
     * Display a listing of partner reports
     */
    public function index(Request $request)
    {
        // Get month and year from request or use current
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');

        // Get order_report_ids that have partner services linked to the balance period for this month/year
        $orderReportIds = OrderPartnerReport::whereHas('balance', function ($q) use ($month, $year) {
                $q->whereYear('period_start', $year)
                  ->whereMonth('period_start', $month);
            })
            ->distinct()
            ->pluck('order_report_id');

        // Build query - Get order reports with their partner services FOR THIS PERIOD ONLY
        $query = OrderReport::with([
            'order.customer',
            'order.productCategory',
            'order.orderItems',
            'invoice',
            'partnerReports' => function ($q) use ($month, $year) {
                $q->whereHas('balance', function ($b) use ($month, $year) {
                    $b->whereYear('period_start', $year)
                      ->whereMonth('period_start', $month);
                })->with(['supportPartner', 'balance']);
            },
        ])
        ->select('order_reports.*')
        ->whereIn('id', $orderReportIds);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('invoice', function ($q) use ($search) {
                      $q->where('invoice_no', 'like', "%{$search}%");
                  })
                  ->orWhereHas('order.customer', function ($q) use ($search) {
                      $q->where('customer_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('order.productCategory', function ($q) use ($search) {
                      $q->where('product_name', 'like', "%{$search}%");
                  });
            });
        }

        // Get paginated data - sorted by most recently added/modified service within the balance period
        $services = $query
            ->addSelect(DB::raw("(
                SELECT MAX(opr.updated_at)
                FROM order_partner_reports opr
                INNER JOIN balances b ON b.id = opr.balance_id
                WHERE opr.order_report_id = order_reports.id
                AND YEAR(b.period_start) = {$year}
                AND MONTH(b.period_start) = {$month}
            ) as period_last_updated_at"))
            ->orderBy('period_last_updated_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Calculate statistics (all partner services linked to the balance period)
        $allServices = OrderPartnerReport::whereHas('balance', function ($q) use ($month, $year) {
                $q->whereYear('period_start', $year)
                  ->whereMonth('period_start', $month);
            })
            ->get();

        $stats = [
            'total_transactions' => $allServices->count(),
            'balance_used' => $allServices->sum('amount'),
        ];

        // Get report period lock status for current month/year
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $reportPeriod = ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodStart->copy()->endOfMonth()->toDateString())
            ->first();

        // If AJAX request, return only the section
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('pages.finance.report.support-partner', compact(
                'services',
                'stats',
                'month',
                'year',
                'search',
                'reportPeriod'
            ))->render();
        }

        return view('pages.finance.report.support-partner', compact(
            'services',
            'stats',
            'month',
            'year',
            'search',
            'reportPeriod'
        ));
    }

    /**
     * Get available order reports (without first_purchase)
     */
    public function getAvailableOrders(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!$month || !$year) {
            return response()->json([
                'success' => false,
                'message' => 'Month and year are required'
            ], 400);
        }

        // Get order reports that don't have first_purchase service yet
        $orderReports = OrderReport::with(['order.customer', 'order.productCategory', 'invoice'])
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->whereDoesntHave('partnerReports', function ($query) {
                $query->where('service_type', 'first_service');
            })
            ->get()
            ->map(function ($report) {
                $order = $report->order;
                $invoiceNo = $report->invoice->invoice_no ?? 'N/A';
                $invoice = str_replace('INV-', '', $invoiceNo);
                $customer = $order->customer->customer_name ?? 'N/A';
                $product = $order->productCategory->product_name ?? 'N/A';

                return [
                    'id' => $report->id,
                    'invoice' => $invoice,
                    'customer' => $customer,
                    'product' => $product,
                    'display_name' => "{$invoice} {$customer} ({$product})"
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $orderReports
        ]);
    }

    /**
     * Get support partners
     */
    public function getPartners()
    {
        try {
            $partners = SupportPartner::orderBy('sort_order')->orderBy('partner_name')->get();
            
            return response()->json([
                'success' => true,
                'partners' => $partners
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch partners: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order report data for extra service modal
     */
    public function getOrderReport(OrderReport $orderReport)
    {
        try {
            $firstService = $orderReport->partnerReports()
                ->where('service_type', 'first_service')
                ->first();

            if (!$firstService) {
                return response()->json([
                    'success' => false,
                    'message' => 'No first service found for this order'
                ], 404);
            }

            $balance = Balance::find($firstService->balance_id);
            
            $order = $orderReport->order;
            $invoiceNo = $orderReport->invoice->invoice_no ?? 'N/A';
            $invoice = str_replace('INV-', '', $invoiceNo);
            $customer = $order->customer->customer_name ?? 'N/A';
            $product = $order->productCategory->product_name ?? 'N/A';

            // Extract month and year from period_start
            $periodStart = $balance ? \Carbon\Carbon::parse($balance->period_start) : now();
            
            return response()->json([
                'success' => true,
                'order_report' => [
                    'id' => $orderReport->id,
                    'invoice' => $invoice,
                    'customer' => $customer,
                    'product' => $product,
                    'display_name' => "{$invoice} - {$customer} ({$product})"
                ],
                'balance' => [
                    'id' => $balance ? $balance->id : null,
                    'month' => $periodStart->month,
                    'year' => $periodStart->year,
                    'transfer_balance' => $balance ? $balance->transfer_balance : 0,
                    'cash_balance' => $balance ? $balance->cash_balance : 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check period status before creating service purchase
     */
    public function checkPeriodStatus(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!$month || !$year) {
            return response()->json([
                'success' => false,
                'message' => 'Month and year are required'
            ], 400);
        }

        // Calculate period with proper formatting
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Check if period exists in report_periods using date string comparison
        $reportPeriod = ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        // If period doesn't exist
        if (!$reportPeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Report period not found. Please create this period first in Order List.'
            ], 404);
        }

        // If period is locked
        if ($reportPeriod->lock_status === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'This period is locked. Cannot add new service purchases.'
            ], 403);
        }

        // Period is valid (draft status)
        return response()->json([
            'success' => true,
            'message' => 'Period is available for service purchases'
        ]);
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'balance_month' => 'required|integer|min:1|max:12',
            'balance_year' => 'required|integer|min:2020',
            'order_report_id' => 'required|exists:order_reports,id',
            'service_name' => 'required|string|max:50',
            'support_partner_id' => 'required|exists:support_partners,id',
            'payment_method' => 'required|in:cash,transfer',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'proof_image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
            'proof_image2' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find balance by period using whereYear and whereMonth
            $balance = Balance::whereYear('period_start', $request->balance_year)
                ->whereMonth('period_start', $request->balance_month)
                ->first();

            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance not found for selected period'
                ], 400);
            }

            // Check if balance is sufficient
            $amount = $request->amount;
            $paymentMethod = $request->payment_method;
            
            if ($paymentMethod === 'transfer' && $balance->transfer_balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient transfer balance'
                ], 400);
            }

            if ($paymentMethod === 'cash' && $balance->cash_balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient cash balance'
                ], 400);
            }

            // Upload proof image
            $proofImage = $request->file('proof_image');
            $filename = 'partner_' . time() . '_' . uniqid() . '.' . $proofImage->getClientOriginalExtension();
            $proofImagePath = $proofImage->storeAs('partner_report_proofs/proof1', $filename, 'local');

            // Upload proof image 2 if provided
            $proofImagePath2 = null;
            if ($request->hasFile('proof_image2')) {
                $proofImage2 = $request->file('proof_image2');
                $filename2 = 'partner2_' . time() . '_' . uniqid() . '.' . $proofImage2->getClientOriginalExtension();
                $proofImagePath2 = $proofImage2->storeAs('partner_report_proofs/proof2', $filename2, 'local');
            }

            // Create partner report
            $partnerReport = OrderPartnerReport::create([
                'balance_id' => $balance->id,
                'order_report_id' => $request->order_report_id,
                'service_date' => now()->toDateString(),
                'service_type' => 'first_service',
                'service_name' => $request->service_name,
                'support_partner_id' => $request->support_partner_id,
                'amount' => $amount,
                'notes' => $request->notes,
                'payment_method' => $paymentMethod,
                'proof_img' => $proofImagePath,
                'proof_img2' => $proofImagePath2,
                'report_status' => $proofImagePath2 ? 'fixed' : 'draft',
            ]);

            // Update balance
            if ($paymentMethod === 'transfer') {
                $balance->transfer_balance -= $amount;
            } else {
                $balance->cash_balance -= $amount;
            }
            $balance->total_balance = $balance->transfer_balance + $balance->cash_balance;
            $balance->save();

            return response()->json([
                'success' => true,
                'message' => 'Service purchase created successfully!',
                'data' => $partnerReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store extra partner purchase
     */
    public function storeExtra(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_report_id' => 'required|exists:order_reports,id',
                'service_name' => 'required|string|max:255',
                'support_partner_id' => 'required|exists:support_partners,id',
                'payment_method' => 'required|in:cash,transfer',
                'amount' => 'required|numeric|min:1',
                'proof_image' => 'required|image|mimes:jpeg,jpg,png|max:5120',
                'proof_image2' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $amount = (float) $request->amount;

            // Get balance_id from first_service of the same order_report
            $orderReport = OrderReport::findOrFail($request->order_report_id);
            $firstService = $orderReport->partnerReports()
                ->where('service_type', 'first_service')
                ->first();

            if (!$firstService) {
                return response()->json([
                    'success' => false,
                    'message' => 'First service not found for this order'
                ], 404);
            }

            $balance = Balance::find($firstService->balance_id);

            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance not found'
                ], 404);
            }

            // Check if balance is sufficient
            if ($request->payment_method === 'cash') {
                if ($balance->cash_balance < $amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient cash balance'
                    ], 400);
                }
            } else {
                if ($balance->transfer_balance < $amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient transfer balance'
                    ], 400);
                }
            }

            // Handle image upload
            $proofImage = $request->file('proof_image');
            $filename = 'partner_extra_' . time() . '_' . uniqid() . '.' . $proofImage->getClientOriginalExtension();
            $proofImagePath = $proofImage->storeAs('partner_report_proofs/proof1', $filename, 'local');

            // Upload proof image 2 if provided
            $proofImagePath2 = null;
            if ($request->hasFile('proof_image2')) {
                $proofImage2 = $request->file('proof_image2');
                $filename2 = 'partner_extra2_' . time() . '_' . uniqid() . '.' . $proofImage2->getClientOriginalExtension();
                $proofImagePath2 = $proofImage2->storeAs('partner_report_proofs/proof2', $filename2, 'local');
            }

            // Create extra partner report
            $partnerReport = OrderPartnerReport::create([
                'balance_id' => $balance->id,
                'order_report_id' => $request->order_report_id,
                'service_date' => now(),
                'service_type' => 'extra_service',
                'service_name' => $request->service_name,
                'support_partner_id' => $request->support_partner_id,
                'amount' => $amount,
                'notes' => $request->notes,
                'payment_method' => $request->payment_method,
                'proof_img' => $proofImagePath,
                'proof_img2' => $proofImagePath2,
                'report_status' => $proofImagePath2 ? 'fixed' : 'draft',
            ]);

            // Update balance
            if ($request->payment_method === 'cash') {
                $balance->cash_balance -= $amount;
            } else {
                $balance->transfer_balance -= $amount;
            }
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            return response()->json([
                'success' => true,
                'message' => 'Extra service purchase created successfully!',
                'data' => $partnerReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create extra service purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update partner report
     */
    public function update(Request $request, OrderPartnerReport $partnerReport)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_name' => 'required|string|max:255',
                'support_partner_id' => 'nullable|exists:support_partners,id',
                'payment_method' => 'required|in:cash,transfer',
                'amount' => 'required|numeric|min:1',
                'notes' => 'nullable|string',
                'proof_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'proof_image2' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'remove_proof_image2' => 'nullable|in:1,true',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldAmount = $partnerReport->amount;
            $oldPaymentMethod = $partnerReport->payment_method;
            $newAmount = $request->amount;
            $newPaymentMethod = $request->payment_method;

            $balance = $partnerReport->balance;

            // Revert old transaction
            if ($oldPaymentMethod === 'transfer') {
                $balance->transfer_balance += $oldAmount;
            } else {
                $balance->cash_balance += $oldAmount;
            }

            // Check new balance
            if ($newPaymentMethod === 'transfer' && $balance->transfer_balance < $newAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient transfer balance'
                ], 400);
            }

            if ($newPaymentMethod === 'cash' && $balance->cash_balance < $newAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient cash balance'
                ], 400);
            }

            // Update partner report
            $partnerReport->service_name = $request->service_name;
            $partnerReport->support_partner_id = $request->support_partner_id;
            $partnerReport->payment_method = $newPaymentMethod;
            $partnerReport->amount = $newAmount;
            $partnerReport->notes = $request->notes;

            // Upload new proof image if provided
            if ($request->hasFile('proof_image')) {
                // Delete old image
                if ($partnerReport->proof_img) {
                    Storage::disk('local')->delete($partnerReport->proof_img);
                }

                $proofImage = $request->file('proof_image');
                $filename = 'partner_' . time() . '_' . uniqid() . '.' . $proofImage->getClientOriginalExtension();
                $proofImagePath = $proofImage->storeAs('partner_report_proofs/proof1', $filename, 'local');
                $partnerReport->proof_img = $proofImagePath;
            }

            // Handle proof image 2
            if ($request->hasFile('proof_image2')) {
                if ($partnerReport->proof_img2) {
                    Storage::disk('local')->delete($partnerReport->proof_img2);
                }
                $proofImage2 = $request->file('proof_image2');
                $filename2 = 'partner2_' . time() . '_' . uniqid() . '.' . $proofImage2->getClientOriginalExtension();
                $proofImagePath2 = $proofImage2->storeAs('partner_report_proofs/proof2', $filename2, 'local');
                $partnerReport->proof_img2 = $proofImagePath2;
            } elseif ($request->input('remove_proof_image2')) {
                if ($partnerReport->proof_img2) {
                    Storage::disk('local')->delete($partnerReport->proof_img2);
                }
                $partnerReport->proof_img2 = null;
            }

            // Auto-set report_status based on whether proof_img2 exists
            $partnerReport->report_status = $partnerReport->proof_img2 ? 'fixed' : 'draft';

            $partnerReport->save();

            // Apply new transaction
            if ($newPaymentMethod === 'transfer') {
                $balance->transfer_balance -= $newAmount;
            } else {
                $balance->cash_balance -= $newAmount;
            }
            $balance->total_balance = $balance->transfer_balance + $balance->cash_balance;
            $balance->save();

            return response()->json([
                'success' => true,
                'message' => 'Service purchase updated successfully!',
                'data' => $partnerReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete partner report
     */
    public function destroy(OrderPartnerReport $partnerReport)
    {
        try {
            // Get the balance
            $balance = Balance::find($partnerReport->balance_id);
            
            if (!$balance) {
                return redirect()
                    ->back()
                    ->with('toast_message', 'Balance not found!')
                    ->with('toast_type', 'error');
            }

            // Get the amount
            $amount = (float) $partnerReport->amount;

            // Restore balance based on payment method
            if ($partnerReport->payment_method === 'cash') {
                $balance->cash_balance += $amount;
            } else {
                $balance->transfer_balance += $amount;
            }
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            // Delete proof images from storage if they exist
            if ($partnerReport->proof_img && Storage::disk('local')->exists($partnerReport->proof_img)) {
                Storage::disk('local')->delete($partnerReport->proof_img);
            }

            if ($partnerReport->proof_img2 && Storage::disk('local')->exists($partnerReport->proof_img2)) {
                Storage::disk('local')->delete($partnerReport->proof_img2);
            }

            // Delete the partner report
            $partnerReport->delete();

            return redirect()
                ->back()
                ->with('toast_message', 'Service purchase deleted and balance restored successfully!')
                ->with('toast_type', 'success');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('toast_message', 'Failed to delete service purchase: ' . $e->getMessage())
                ->with('toast_type', 'error');
        }
    }

    /**
     * Serve partner proof image from private storage
     */
    public function serveImage(OrderPartnerReport $partnerReport)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        // Check if file exists
        if (!$partnerReport->proof_img || !Storage::disk('local')->exists($partnerReport->proof_img)) {
            abort(404, 'Image not found');
        }

        // Get file path
        $path = Storage::disk('local')->path($partnerReport->proof_img);
        
        // Get mime type from file extension
        $mimeType = Storage::disk('local')->mimeType($partnerReport->proof_img) ?: 'application/octet-stream';

        // Return file response
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Serve partner proof image 2 from private storage
     */
    public function serveImage2(OrderPartnerReport $partnerReport)
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$partnerReport->proof_img2 || !Storage::disk('local')->exists($partnerReport->proof_img2)) {
            abort(404, 'Image not found');
        }

        $path = Storage::disk('local')->path($partnerReport->proof_img2);
        $mimeType = Storage::disk('local')->mimeType($partnerReport->proof_img2) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Toggle report_status between draft and fixed
     */
    public function toggleReportStatus(OrderPartnerReport $partnerReport)
    {
        try {
            $partnerReport->report_status = $partnerReport->report_status === 'fixed' ? 'draft' : 'fixed';
            $partnerReport->save();

            return response()->json([
                'success' => true,
                'new_status' => $partnerReport->report_status,
                'message' => 'Status updated to ' . $partnerReport->report_status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
