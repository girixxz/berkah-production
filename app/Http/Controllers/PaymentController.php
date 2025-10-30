<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\OrderStage;
use App\Models\ProductionStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PaymentController extends Controller
{
    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'payment_method' => 'required|in:tranfer,cash',
                'payment_type' => 'required|in:dp,repayment,full_payment',
                'amount' => 'required|numeric|min:1',
                'notes' => 'nullable|string|max:1000',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:10240', // Single image only
            ], [
                'invoice_id.required' => 'Invoice ID is required',
                'invoice_id.exists' => 'Invoice not found',
                'payment_method.required' => 'Payment method is required',
                'payment_method.in' => 'Invalid payment method',
                'payment_type.required' => 'Payment type is required',
                'payment_type.in' => 'Invalid payment type',
                'amount.required' => 'Amount is required',
                'amount.numeric' => 'Amount must be a number',
                'amount.min' => 'Amount must be at least 1',
                'image.required' => 'Payment proof image is required',
                'image.image' => 'File must be an image',
                'image.mimes' => 'Image must be jpeg, png, or jpg',
                'image.max' => 'Image size must not exceed 10MB',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $invoice = Invoice::with(['payments', 'order'])->findOrFail($validated['invoice_id']);

            // Check if payment amount exceeds remaining due (hitung dari APPROVED + PENDING payments)
            // Karena pending payment masih bisa di-approve, jadi harus dihitung juga untuk validasi
            $currentPaid = $invoice->payments->whereIn('status', ['approved', 'pending'])->sum('amount');
            $remainingDue = $invoice->total_bill - $currentPaid;
            
            if ($validated['amount'] > $remainingDue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount (Rp ' . number_format($validated['amount'], 0, ',', '.') . ') exceeds remaining due (Rp ' . number_format($remainingDue, 0, ',', '.') . ')'
                ], 422);
            }

            // Upload single image to Cloudinary
            $imageUrl = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                try {
                    $file = $request->file('image');
                    $uploadedFile = Cloudinary::uploadApi()->upload($file->getRealPath(), [
                        'folder' => 'payments',
                        'resource_type' => 'image',
                        'transformation' => [
                            'quality' => 'auto',
                            'fetch_format' => 'auto'
                        ]
                    ]);
                    $imageUrl = $uploadedFile['secure_url'];
                } catch (\Exception $uploadError) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload image: ' . $uploadError->getMessage(),
                        'errors' => ['image' => ['Failed to upload image. Please try again.']]
                    ], 500);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Image file is required',
                    'errors' => ['image' => ['Please select a valid image file']]
                ], 422);
            }

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $validated['invoice_id'],
                'payment_method' => $validated['payment_method'],
                'payment_type' => $validated['payment_type'],
                'amount' => $validated['amount'],
                'status' => 'pending', // Default status
                'notes' => $validated['notes'] ?? null,
                'img_url' => $imageUrl, // Store as single string
                'paid_at' => now(),
            ]);

            // Refresh invoice to get updated payments relationship
            $invoice->refresh();

            // Update invoice - recalculate ONLY from APPROVED payments
            $totalPaid = $invoice->payments()->where('status', 'approved')->sum('amount');
            $amountDue = $invoice->total_bill - $totalPaid;

            // Determine invoice status based on enum: 'unpaid', 'dp', 'paid'
            $status = 'unpaid';
            if ($totalPaid >= $invoice->total_bill) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'dp';
            }

            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due' => max(0, $amountDue),
                'status' => $status,
            ]);

            // TIDAK langsung update order ke WIP - harus menunggu owner approve payment dulu
            // Logic WIP akan dipindah ke approve method

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment added successfully',
                'payment' => $payment
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all payments for a specific invoice
     */
    public function getPaymentsByInvoice(Invoice $invoice)
    {
        $payments = $invoice->payments()
            ->orderBy('paid_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_method' => $payment->payment_method,
                    'payment_type' => $payment->payment_type,
                    'amount' => $payment->amount,
                    'notes' => $payment->notes,
                    'img_url' => $payment->img_url,
                    'paid_at' => $payment->paid_at,
                ];
            });

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(Payment $payment)
    {
        DB::beginTransaction();

        try {
            $invoice = $payment->invoice;

            // Delete image from Cloudinary (single image)
            if ($payment->img_url) {
                try {
                    // Extract public_id from URL
                    $publicId = $this->getPublicIdFromUrl($payment->img_url);
                    if ($publicId) {
                        Cloudinary::uploadApi()->destroy($publicId);
                    }
                } catch (\Exception $e) {
                    // Log error but continue with deletion
                    Log::warning('Failed to delete image from Cloudinary: ' . $e->getMessage());
                }
            }

            $payment->delete();

            // Recalculate invoice - ONLY from APPROVED payments
            $totalPaid = $invoice->payments()->where('status', 'approved')->sum('amount');
            $amountDue = $invoice->total_bill - $totalPaid;

            // Determine invoice status based on enum: 'unpaid', 'dp', 'paid'
            $status = 'unpaid';
            if ($totalPaid >= $invoice->total_bill) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'dp';
            }

            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due' => $amountDue,
                'status' => $status,
            ]);

            DB::commit();

            return redirect()->back()
                ->with('message', 'Payment deleted successfully.')
                ->with('alert-type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('message', 'Failed to delete payment: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
    }

    /**
     * Approve payment (Owner only)
     */
    public function approve(Payment $payment)
    {
        DB::beginTransaction();

        try {
            // Check if payment already processed
            if ($payment->status !== 'pending') {
                return redirect()->back()
                    ->with('message', 'Payment has already been ' . $payment->status)
                    ->with('alert-type', 'warning');
            }

            // Update payment status
            $payment->update(['status' => 'approved']);

            // Get invoice and order
            $invoice = $payment->invoice;
            $order = $invoice->order;

            // Recalculate invoice with newly approved payment
            $totalPaid = $invoice->payments()->where('status', 'approved')->sum('amount');
            $amountDue = $invoice->total_bill - $totalPaid;

            // Determine invoice status
            $invoiceStatus = 'unpaid';
            if ($totalPaid >= $invoice->total_bill) {
                $invoiceStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $invoiceStatus = 'dp';
            }

            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due' => max(0, $amountDue),
                'status' => $invoiceStatus,
            ]);

            // Check if this is the first approved payment for this order
            $approvedPaymentsCount = $invoice->payments()->where('status', 'approved')->count();

            // If this is the first approved payment and order is still pending, change to WIP
            if ($approvedPaymentsCount === 1 && $order && $order->production_status === 'pending') {
                $order->update([
                    'production_status' => 'wip'
                ]);

                // Auto-create order_stages for all production stages when order becomes WIP
                $productionStages = ProductionStage::all();
                foreach ($productionStages as $stage) {
                    OrderStage::firstOrCreate(
                        [
                            'order_id' => $order->id,
                            'stage_id' => $stage->id,
                        ],
                        [
                            'start_date' => null,
                            'deadline' => null,
                            'status' => 'pending',
                        ]
                    );
                }
            }

            DB::commit();

            return redirect()->back()
                ->with('message', 'Payment approved successfully' . ($approvedPaymentsCount === 1 ? ' and order moved to WIP' : ''))
                ->with('alert-type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('message', 'Failed to approve payment: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
    }

    /**
     * Reject payment (Owner only)
     */
    public function reject(Payment $payment)
    {
        DB::beginTransaction();

        try {
            // Check if payment already processed
            if ($payment->status !== 'pending') {
                return redirect()->back()
                    ->with('message', 'Payment has already been ' . $payment->status)
                    ->with('alert-type', 'warning');
            }

            // Update payment status
            $payment->update(['status' => 'rejected']);

            // Recalculate invoice (rejected payment tidak dihitung)
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->where('status', 'approved')->sum('amount');
            $amountDue = $invoice->total_bill - $totalPaid;

            // Determine invoice status
            $invoiceStatus = 'unpaid';
            if ($totalPaid >= $invoice->total_bill) {
                $invoiceStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $invoiceStatus = 'dp';
            }

            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due' => max(0, $amountDue),
                'status' => $invoiceStatus,
            ]);

            DB::commit();

            return redirect()->back()
                ->with('message', 'Payment rejected successfully')
                ->with('alert-type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('message', 'Failed to reject payment: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
    }

    /**
     * Extract public_id from Cloudinary URL
     */
    private function getPublicIdFromUrl($url)
    {
        // Example URL: https://res.cloudinary.com/demo/image/upload/v1234567890/payments/abc123.jpg
        // Extract: payments/abc123
        
        $parts = parse_url($url);
        if (!isset($parts['path'])) {
            return null;
        }

        $pathParts = explode('/', $parts['path']);
        $versionIndex = array_search('upload', $pathParts);
        
        if ($versionIndex === false) {
            return null;
        }

        // Get everything after version (skip v1234567890)
        $publicIdParts = array_slice($pathParts, $versionIndex + 2);
        $publicId = implode('/', $publicIdParts);
        
        // Remove file extension
        return pathinfo($publicId, PATHINFO_DIRNAME) . '/' . pathinfo($publicId, PATHINFO_FILENAME);
    }
}
