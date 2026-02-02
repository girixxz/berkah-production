<?php

namespace App\Http\Controllers;

use App\Models\OrderMaterialReport;
use App\Models\OrderReport;
use App\Models\MaterialSupplier;
use App\Models\Balance;
use App\Models\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MaterialReportController extends Controller
{
    /**
     * Display a listing of material reports
     */
    public function index(Request $request)
    {
        // Get month and year from request or use current
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');

        // Get order_report_ids that have material purchases for this period
        $orderReportIds = OrderMaterialReport::whereYear('purchase_date', $year)
            ->whereMonth('purchase_date', $month)
            ->distinct()
            ->pluck('order_report_id');

        // Build query - Get order reports with their material purchases
        $query = OrderReport::with([
            'order.customer',
            'order.productCategory',
            'order.orderItems',
            'invoice',
            'materialReports.materialSupplier'
        ])
        ->select('order_reports.*') // Ensure all columns including production_status and lock_status
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

        // Get paginated data - Latest first
        $materials = $query->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Calculate statistics (all order reports for the period)
        $allMaterials = OrderMaterialReport::whereYear('purchase_date', $year)
            ->whereMonth('purchase_date', $month)
            ->get();

        $stats = [
            'total_transactions' => $allMaterials->count(),
            'balance_used' => $allMaterials->sum('amount'),
        ];

        // If AJAX request, return only the section
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('pages.finance.report.material', compact(
                'materials',
                'stats',
                'month',
                'year',
                'search'
            ))->render();
        }

        return view('pages.finance.report.material', compact(
            'materials',
            'stats',
            'month',
            'year',
            'search'
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

        // Get order reports that don't have first_purchase material yet
        $orderReports = OrderReport::with(['order.customer', 'order.productCategory', 'invoice'])
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->whereDoesntHave('materialReports', function ($query) {
                $query->where('purchase_type', 'first_purchase');
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
     * Get order report data for extra purchase
     */
    public function getOrderReport(OrderReport $orderReport)
    {
        try {
            $orderReport->load(['order.customer', 'order.productCategory', 'invoice', 'materialReports']);

            // Get first material purchase to determine the balance period
            $firstPurchase = $orderReport->materialReports()
                ->where('purchase_type', 'first_purchase')
                ->first();

            if (!$firstPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'No first purchase found for this order'
                ], 404);
            }

            $balance = Balance::find($firstPurchase->balance_id);
            
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
     * Check period status before creating material purchase
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

        // Calculate period
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Check if period exists in report_periods
        $reportPeriod = ReportPeriod::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        // Validation 1: Period must exist
        if (!$reportPeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found. Please navigate to Order List for this period first to create the period.'
            ], 404);
        }

        // Validation 2: Period must not be locked
        if ($reportPeriod->lock_status === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'Period is locked. Cannot add material purchase to a locked period.'
            ], 403);
        }

        // Period is valid (exists and draft)
        return response()->json([
            'success' => true,
            'message' => 'Period is valid and ready for material purchase'
        ]);
    }

    /**
     * Get all material suppliers
     */
    public function getSuppliers()
    {
        $suppliers = MaterialSupplier::orderBy('supplier_name', 'asc')->get(['id', 'supplier_name']);

        return response()->json([
            'success' => true,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * Store material purchase
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'balance_month' => 'required|integer|min:1|max:12',
            'balance_year' => 'required|integer|min:2020',
            'order_report_id' => 'required|exists:order_reports,id',
            'material_name' => 'required|string|max:50',
            'material_supplier_id' => 'required|exists:material_suppliers,id',
            'payment_method' => 'required|in:cash,transfer',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'proof_image' => 'required|image|mimes:jpeg,jpg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find balance by period
            $balance = Balance::whereYear('period_start', $request->balance_year)
                ->whereMonth('period_start', $request->balance_month)
                ->first();

            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance not found for selected period'
                ], 400);
            }

            // Check balance sufficiency
            $amount = $request->amount;
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
            $proofImagePath = null;
            if ($request->hasFile('proof_image')) {
                $file = $request->file('proof_image');
                $filename = 'material_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofImagePath = $file->storeAs('material_proofs', $filename, 'local');
            }

            // Create material report
            $materialReport = OrderMaterialReport::create([
                'balance_id' => $balance->id,
                'order_report_id' => $request->order_report_id,
                'purchase_date' => now(),
                'purchase_type' => 'first_purchase',
                'material_name' => $request->material_name,
                'material_supplier_id' => $request->material_supplier_id,
                'amount' => $amount,
                'notes' => $request->notes,
                'payment_method' => $request->payment_method,
                'proof_img' => $proofImagePath
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
                'message' => 'Material purchase created successfully!',
                'data' => $materialReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store extra material purchase
     */
    public function storeExtra(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_report_id' => 'required|exists:order_reports,id',
                'material_name' => 'required|string|max:255',
                'material_supplier_id' => 'required|exists:material_suppliers,id',
                'payment_method' => 'required|in:cash,transfer',
                'amount' => 'required|numeric|min:1',
                'proof_image' => 'required|image|mimes:jpeg,jpg,png|max:5120',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $amount = (float) $request->amount;

            // Get balance_id from first_purchase of the same order_report
            $orderReport = OrderReport::findOrFail($request->order_report_id);
            $firstPurchase = $orderReport->materialReports()
                ->where('purchase_type', 'first_purchase')
                ->first();

            if (!$firstPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'First purchase not found for this order'
                ], 404);
            }

            $balance = Balance::find($firstPurchase->balance_id);

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
            $proofImagePath = null;
            if ($request->hasFile('proof_image')) {
                $file = $request->file('proof_image');
                $filename = 'material_extra_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofImagePath = $file->storeAs('material_proofs', $filename, 'local');
            }

            // Create extra material report
            $materialReport = OrderMaterialReport::create([
                'balance_id' => $balance->id,
                'order_report_id' => $request->order_report_id,
                'purchase_date' => now(),
                'purchase_type' => 'extra_purchase',
                'material_name' => $request->material_name,
                'material_supplier_id' => $request->material_supplier_id,
                'amount' => $amount,
                'notes' => $request->notes,
                'payment_method' => $request->payment_method,
                'proof_img' => $proofImagePath
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
                'message' => 'Extra material purchase created successfully!',
                'data' => $materialReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create extra material purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update material report
     */
    public function update(Request $request, OrderMaterialReport $materialReport)
    {
        try {
            $validator = Validator::make($request->all(), [
                'material_name' => 'required|string|max:255',
                'material_supplier_id' => 'required|exists:material_suppliers,id',
                'payment_method' => 'required|in:cash,transfer',
                'amount' => 'required|numeric|min:1',
                'proof_image' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldAmount = $materialReport->amount;
            $oldPaymentMethod = $materialReport->payment_method;
            $newAmount = (float) $request->amount;
            $newPaymentMethod = $request->payment_method;

            // Get balance
            $balance = Balance::find($materialReport->balance_id);
            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance not found'
                ], 404);
            }

            // Restore old balance amount
            if ($oldPaymentMethod === 'cash') {
                $balance->cash_balance += $oldAmount;
            } else {
                $balance->transfer_balance += $oldAmount;
            }

            // Check if new balance is sufficient
            if ($newPaymentMethod === 'cash') {
                if ($balance->cash_balance < $newAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient cash balance'
                    ], 400);
                }
                $balance->cash_balance -= $newAmount;
            } else {
                if ($balance->transfer_balance < $newAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient transfer balance'
                    ], 400);
                }
                $balance->transfer_balance -= $newAmount;
            }

            // Handle image upload if new image provided
            if ($request->hasFile('proof_image')) {
                // Delete old image
                if ($materialReport->proof_img && Storage::disk('local')->exists($materialReport->proof_img)) {
                    Storage::disk('local')->delete($materialReport->proof_img);
                }
                
                $file = $request->file('proof_image');
                $filename = 'material_edit_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofImagePath = $file->storeAs('material_proofs', $filename, 'local');
                $materialReport->proof_img = $proofImagePath;
            }

            // Update material report
            $materialReport->material_name = $request->material_name;
            $materialReport->material_supplier_id = $request->material_supplier_id;
            $materialReport->payment_method = $newPaymentMethod;
            $materialReport->amount = $newAmount;
            $materialReport->notes = $request->notes;
            $materialReport->save();

            // Update balance
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            return response()->json([
                'success' => true,
                'message' => 'Material purchase updated successfully!',
                'data' => $materialReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve material proof image from private storage
     */
    public function serveProofImage(OrderMaterialReport $materialReport)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        // Check if file exists
        if (!$materialReport->proof_img || !Storage::disk('local')->exists($materialReport->proof_img)) {
            abort(404, 'Image not found');
        }

        // Get file path
        $path = Storage::disk('local')->path($materialReport->proof_img);
        
        // Get mime type from file extension
        $mimeType = Storage::disk('local')->mimeType($materialReport->proof_img) ?: 'application/octet-stream';

        // Return file response
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Delete material purchase and restore balance
     */
    public function destroy(OrderMaterialReport $materialReport)
    {
        try {
            // Get the balance
            $balance = Balance::find($materialReport->balance_id);
            
            if (!$balance) {
                return redirect()
                    ->back()
                    ->with('toast_message', 'Balance not found!')
                    ->with('toast_type', 'error');
            }

            // Get the amount
            $amount = (float) $materialReport->amount;

            // Restore balance based on payment method
            if ($materialReport->payment_method === 'cash') {
                $balance->cash_balance += $amount;
            } else {
                $balance->transfer_balance += $amount;
            }
            $balance->total_balance = $balance->cash_balance + $balance->transfer_balance;
            $balance->save();

            // Delete proof image from storage if exists
            if ($materialReport->proof_img && Storage::disk('local')->exists($materialReport->proof_img)) {
                Storage::disk('local')->delete($materialReport->proof_img);
            }

            // Delete the material report
            $materialReport->delete();

            return redirect()
                ->back()
                ->with('toast_message', 'Material purchase deleted and balance restored successfully!')
                ->with('toast_type', 'success');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('toast_message', 'Failed to delete material purchase: ' . $e->getMessage())
                ->with('toast_type', 'error');
        }
    }
}
