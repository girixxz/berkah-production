<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
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

            // Check if payment amount exceeds remaining due
            $currentPaid = $invoice->payments->sum('amount');
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
                'notes' => $validated['notes'] ?? null,
                'img_url' => $imageUrl, // Store as single string
                'paid_at' => now(),
            ]);

            // Refresh invoice to get updated payments relationship
            $invoice->refresh();

            // Update invoice - recalculate from all payments
            $totalPaid = $invoice->payments()->sum('amount');
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

            // Update order production status to WIP after first payment
            $order = $invoice->order;
            if ($order && $order->production_status === 'pending' && $invoice->payments()->count() >= 1) {
                $order->update([
                    'production_status' => 'wip'
                ]);
            }

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

            // Recalculate invoice
            $totalPaid = $invoice->payments()->sum('amount');
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
