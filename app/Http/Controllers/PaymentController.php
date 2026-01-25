<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\OrderStage;
use Illuminate\Http\Request;
use App\Models\ProductionStage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Payment Controller
 * 
 * Handles payment management including create, approve, reject, and delete operations.
 * Uses route model binding for automatic model resolution.
 */
class PaymentController extends Controller
{
    /**
     * Compress image if larger than 200KB
     * 
     * @param UploadedFile $file
     * @return UploadedFile|null
     */
    private function compressImage(UploadedFile $file)
    {
        $originalSize = $file->getSize();
        $originalSizeKB = round($originalSize / 1024, 2);
        
        // Check if file size is greater than 200KB (200 * 1024 bytes)
        if ($originalSize <= 200 * 1024) {
            Log::info("Image compression skipped - file already small", [
                'filename' => $file->getClientOriginalName(),
                'size_kb' => $originalSizeKB
            ]);
            return $file; // No compression needed
        }

        Log::info("Starting image compression", [
            'filename' => $file->getClientOriginalName(),
            'original_size_kb' => $originalSizeKB
        ]);

        $extension = strtolower($file->getClientOriginalExtension());
        $originalPath = $file->getRealPath();
        
        // Load image based on type
        $image = null;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($originalPath);
                break;
            case 'png':
                $image = @imagecreatefrompng($originalPath);
                break;
            case 'gif':
                $image = @imagecreatefromgif($originalPath);
                break;
            case 'webp':
                $image = @imagecreatefromwebp($originalPath);
                break;
            default:
                Log::warning("Unsupported image format", ['extension' => $extension]);
                return $file; // Unsupported format, return original
        }

        if (!$image) {
            Log::warning("Failed to load image with GD", ['filename' => $file->getClientOriginalName()]);
            return $file; // Failed to load image, return original
        }

        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // For PNG with transparency, convert to white background
        if ($extension === 'png') {
            // Create a new true color image
            $newImage = imagecreatetruecolor($width, $height);
            // Fill with white background
            $white = imagecolorallocate($newImage, 255, 255, 255);
            imagefill($newImage, 0, 0, $white);
            // Copy original image onto white background
            imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
            $image = $newImage;
        }

        // Save as JPEG for better compression (convert PNG/GIF/WebP to JPEG)
        $tempPath = sys_get_temp_dir() . '/' . uniqid('compressed_') . '.jpg';

        // Try different quality levels until we get under 200KB
        $quality = 85;
        $compressed = false;
        $attempts = 0;
        $maxAttempts = 10;

        while ($quality >= 30 && !$compressed && $attempts < $maxAttempts) {
            $attempts++;
            
            // Save as JPEG with current quality
            $result = @imagejpeg($image, $tempPath, $quality);
            
            if (!$result) {
                Log::warning("Failed to save compressed image", [
                    'attempt' => $attempts,
                    'quality' => $quality
                ]);
                break;
            }

            // Check if compressed size is under 200KB
            if (file_exists($tempPath) && filesize($tempPath) <= 200 * 1024) {
                $compressed = true;
                Log::info("Compression successful at quality {$quality}", [
                    'attempt' => $attempts,
                    'size_kb' => round(filesize($tempPath) / 1024, 2)
                ]);
            } else {
                $quality -= 5; // Reduce quality more gradually
            }
        }

        // Note: In PHP 8+, GdImage objects are automatically destroyed when they go out of scope

        // If compression successful, create new UploadedFile
        if ($compressed && file_exists($tempPath)) {
            $compressedSize = filesize($tempPath);
            $compressedSizeKB = round($compressedSize / 1024, 2);
            $reduction = round((($originalSize - $compressedSize) / $originalSize) * 100, 2);
            
            Log::info("Image compression successful", [
                'filename' => $file->getClientOriginalName(),
                'original_size_kb' => $originalSizeKB,
                'compressed_size_kb' => $compressedSizeKB,
                'reduction_percent' => $reduction . '%',
                'final_quality' => $quality,
                'converted_to' => 'jpg'
            ]);
            
            // Create new UploadedFile from compressed image
            $compressedFile = new UploadedFile(
                $tempPath,
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg',
                'image/jpeg',
                null,
                true
            );
            
            return $compressedFile;
        }

        Log::warning("Image compression failed - returning original", [
            'filename' => $file->getClientOriginalName(),
            'original_size_kb' => $originalSizeKB,
            'attempts' => $attempts
        ]);

        return $file;
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'payment_method' => 'required|in:transfer,cash',
                'payment_type' => 'required|in:dp,repayment,full_payment',
                'amount' => 'required|numeric|min:1',
                'notes' => 'nullable|string|max:1000',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:25600', // Single image only (25MB, will be compressed)
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

            // Upload single image to local storage (private)
            $imagePath = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                try {
                    $file = $request->file('image');
                    
                    // Compress image if larger than 200KB
                    $file = $this->compressImage($file);
                    
                    // Generate unique filename with timestamp
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    
                    // Store in private storage (storage/app/private/payments/)
                    $path = $file->storeAs('payments', $filename, 'local');
                    
                    $imagePath = $path;
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
                'img_url' => $imagePath, // Store file path (not URL)
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
     * 
     * @param \App\Models\Invoice $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentsByInvoice(Invoice $invoice)
    {
        $payments = $invoice->payments()
            ->orderBy('paid_at', 'desc')
            ->get()
            ->map(function (Payment $payment) {
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
     * 
     * @param \App\Models\Payment $payment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Payment $payment)
    {
        DB::beginTransaction();

        try {
            /** @var \App\Models\Invoice $invoice */
            $invoice = $payment->invoice;

            // Delete image from local storage (single image)
            if ($payment->img_url) {
                try {
                    // Delete file from storage/app/private/
                    if (Storage::disk('local')->exists($payment->img_url)) {
                        Storage::disk('local')->delete($payment->img_url);
                    }
                } catch (\Exception $e) {
                    // Log error but continue with deletion
                    Log::warning('Failed to delete image from local storage: ' . $e->getMessage());
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
     * 
     * @param \App\Models\Payment $payment
     * @return \Illuminate\Http\RedirectResponse
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
            /** @var \App\Models\Invoice $invoice */
            $invoice = $payment->invoice;
            /** @var \App\Models\Order $order */
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
                    'production_status' => 'wip',
                    'wip_date' => now()
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

                // Set deadline for PO and Material stages based on order priority
                // Priority normal: +3 days, Priority high: +1 day
                $daysToAdd = ($order->priority === 'high') ? 1 : 3;
                $startDate = now();
                $deadline = now()->addDays($daysToAdd);

                // Update PO stage: set start_date, deadline, and status
                $poStage = ProductionStage::where('stage_name', 'PO')->first();
                if ($poStage) {
                    OrderStage::where('order_id', $order->id)
                        ->where('stage_id', $poStage->id)
                        ->update([
                            'start_date' => $startDate,
                            'deadline' => $deadline,
                            'status' => 'in_progress'
                        ]);
                }

                // Update Material stage: set start_date, deadline, and status
                $materialStage = ProductionStage::where('stage_name', 'Material')->first();
                if ($materialStage) {
                    OrderStage::where('order_id', $order->id)
                        ->where('stage_id', $materialStage->id)
                        ->update([
                            'start_date' => $startDate,
                            'deadline' => $deadline,
                            'status' => 'in_progress'
                        ]);
                }
            }

            // Auto-update balance if order already reported
            if ($order && $order->report_status === 'reported') {
                // Get the latest order report for this order
                $orderReport = $order->orderReports()
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($orderReport) {
                    // Find or create balance for the report period
                    $balance = \App\Models\Balance::firstOrCreate(
                        [
                            'period_start' => $orderReport->period_start,
                            'period_end' => $orderReport->period_end,
                        ],
                        [
                            'total_balance' => 0,
                            'transfer_balance' => 0,
                            'cash_balance' => 0,
                        ]
                    );

                    // Add the newly approved payment to balance
                    if ($payment->payment_method === 'transfer') {
                        $balance->increment('transfer_balance', $payment->amount);
                    } else {
                        $balance->increment('cash_balance', $payment->amount);
                    }
                    $balance->increment('total_balance', $payment->amount);
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
     * 
     * @param \App\Models\Payment $payment
     * @return \Illuminate\Http\RedirectResponse
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
            /** @var \App\Models\Invoice $invoice */
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
     * Serve payment proof image (private file)
     * Only accessible by authenticated users
     * 
     * @param \App\Models\Payment $payment
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function serveImage(Payment $payment)
    {
        // Check if user is authenticated
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        if (!$auth->check()) {
            abort(403, 'Unauthorized');
        }

        // Check if file exists
        if (!$payment->img_url || !Storage::disk('local')->exists($payment->img_url)) {
            abort(404, 'Image not found');
        }

        // Get file path
        $path = Storage::disk('local')->path($payment->img_url);
        
        // Get mime type from file extension
        $mimeType = Storage::disk('local')->mimeType($payment->img_url) ?: 'application/octet-stream';

        // Return file response
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Get count of pending payments (for notification badge)
     */
    public function getPendingCount()
    {
        $count = Payment::where('status', 'pending')->count();
        
        return response()->json([
            'count' => $count
        ]);
    }

    /**
     * Get pending payments list (for notification dropdown)
     */
    public function getPendingList()
    {
        $payments = Payment::with(['invoice.order.customer', 'invoice.order.productCategory'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                $order = $payment->invoice->order ?? null;
                $productCategory = $order && $order->productCategory 
                    ? $order->productCategory->product_name 
                    : '-';
                
                return [
                    'id' => $payment->id,
                    'invoice_no' => $payment->invoice->invoice_no ?? '-',
                    'customer_name' => $payment->invoice->order->customer->customer_name ?? 'Unknown',
                    'product_category' => $productCategory,
                    'amount' => $payment->amount,
                    'created_at' => $payment->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'payments' => $payments
        ]);
    }
}
