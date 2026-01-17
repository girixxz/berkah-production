<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\LoanCapital;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LoanCapitalController extends Controller
{
    public function index(Request $request)
    {
        // Get month and year from request
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        // Parse to Carbon
        $currentDate = Carbon::create($year, $month, 1);
        
        // Get status filter
        $statusFilter = $request->input('status', 'all');
        
        // Get per_page
        $perPage = $request->input('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25, 50, 100]) ? $perPage : 10;
        
        // Query loans for the current month
        $query = LoanCapital::whereYear('loan_date', $year)
            ->whereMonth('loan_date', $month);
        
        // Apply status filter
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }
        
        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('loan_code', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        
        // Paginate
        $loans = $query->orderBy('loan_date', 'desc')->paginate($perPage);
        
        // Get all loans for search (for current month)
        $allLoans = LoanCapital::whereYear('loan_date', $year)
            ->whereMonth('loan_date', $month)
            ->orderBy('loan_date', 'desc')
            ->get();
        
        // Get balance for current month
        $balance = Balance::whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->first();
        
        $totalBalance = $balance ? $balance->total_balance : 0;
        
        // Calculate stats
        $activeLoan = LoanCapital::where('status', 'outstanding')
            ->whereYear('loan_date', $year)
            ->whereMonth('loan_date', $month)
            ->sum('amount');
        
        $outstanding = LoanCapital::where('status', 'outstanding')
            ->whereYear('loan_date', $year)
            ->whereMonth('loan_date', $month)
            ->sum('remaining_amount');
        
        // Calculate transfer and cash totals for current month
        $transferTotal = LoanCapital::where('payment_method', 'transfer')
            ->whereYear('loan_date', $year)
            ->whereMonth('loan_date', $month)
            ->sum('amount');
            
        $cashTotal = LoanCapital::where('payment_method', 'cash')
            ->whereYear('loan_date', $year)
            ->whereMonth('loan_date', $month)
            ->sum('amount');
        
        return view('pages.finance.loan-capital.index', compact(
            'loans',
            'allLoans',
            'currentDate',
            'balance',
            'totalBalance',
            'activeLoan',
            'outstanding',
            'transferTotal',
            'cashTotal',
            'statusFilter',
            'perPage'
        ));
    }

    public function getNextLoanCode()
    {
        return response()->json([
            'loan_code' => $this->generateLoanCode(),
            'current_date' => now()->format('Y-m-d')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:transfer,cash',
            'amount' => 'required|numeric|min:1',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240', // Max 10MB
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Generate loan code
            $loanCode = $this->generateLoanCode();

            // Handle image upload (store privately)
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('loan_proofs', $imageName, 'local');
            }

            // Create loan capital
            $loan = LoanCapital::create([
                'loan_code' => $loanCode,
                'loan_date' => now(),
                'amount' => $request->amount,
                'remaining_amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'proof_img' => $imagePath,
                'status' => 'outstanding',
                'notes' => $request->notes,
            ]);

            // Update or create balance for current month
            $balance = Balance::whereYear('period_start', now()->year)
                ->whereMonth('period_start', now()->month)
                ->first();

            if (!$balance) {
                // Create new balance record
                $periodStart = now()->startOfMonth();
                $periodEnd = now()->endOfMonth();
                
                $balance = Balance::create([
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'total_balance' => $request->amount,
                    'transfer_balance' => $request->payment_method === 'transfer' ? $request->amount : 0,
                    'cash_balance' => $request->payment_method === 'cash' ? $request->amount : 0,
                ]);
            } else {
                // Update existing balance
                $balance->total_balance += $request->amount;
                
                if ($request->payment_method === 'transfer') {
                    $balance->transfer_balance += $request->amount;
                } else {
                    $balance->cash_balance += $request->amount;
                }
                
                $balance->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan capital added successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add loan capital: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateLoanCode()
    {
        $now = now();
        $month = $now->format('m');
        $year = $now->format('y');
        
        // Get the latest loan code for current month
        $prefix = "LOAN-{$month}-{$year}-";
        
        $lastLoan = LoanCapital::where('loan_code', 'like', $prefix . '%')
            ->orderBy('loan_code', 'desc')
            ->first();
        
        if ($lastLoan) {
            // Extract the number from the last loan code
            $lastNumber = (int) substr($lastLoan->loan_code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        // Format: LOAN-MM-YY-NNNN
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function serveImage(LoanCapital $loan)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        // Check if file exists
        if (!$loan->proof_img || !Storage::disk('local')->exists($loan->proof_img)) {
            abort(404, 'Image not found');
        }

        // Get file path
        $path = Storage::disk('local')->path($loan->proof_img);
        
        // Get mime type from file extension
        $mimeType = Storage::disk('local')->mimeType($loan->proof_img) ?: 'application/octet-stream';

        // Return file response
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function serveRepaymentImage($repaymentId)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        $repayment = \App\Models\LoanRepayment::findOrFail($repaymentId);

        // Check if file exists
        if (!$repayment->proof_img || !Storage::disk('local')->exists($repayment->proof_img)) {
            abort(404, 'Image not found');
        }

        // Get file path
        $path = Storage::disk('local')->path($repayment->proof_img);
        
        // Get mime type from file extension
        $mimeType = Storage::disk('local')->mimeType($repayment->proof_img) ?: 'application/octet-stream';

        // Return file response
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Update the specified loan capital.
     */
    public function update(Request $request, LoanCapital $loanCapital)
    {
        $rules = [
            'payment_method' => 'required|in:transfer,cash',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:1000',
        ];

        // If removing image or uploading new image
        if ($request->has('remove_image') || $request->hasFile('image')) {
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg|max:10240';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $oldAmount = $loanCapital->amount;
            $oldMethod = $loanCapital->payment_method;
            $oldImagePath = $loanCapital->proof_img;

            // Handle image update
            $imagePath = $oldImagePath;
            
            // Remove old image if requested
            if ($request->has('remove_image') && $oldImagePath) {
                if (Storage::disk('local')->exists($oldImagePath)) {
                    Storage::disk('local')->delete($oldImagePath);
                }
                $imagePath = null;
            }

            // Upload new image
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($oldImagePath && Storage::disk('local')->exists($oldImagePath)) {
                    Storage::disk('local')->delete($oldImagePath);
                }
                
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('loan_proofs', $imageName, 'local');
            }

            // Validate that we still have an image
            if (!$imagePath && !$request->hasFile('image')) {
                return response()->json([
                    'success' => false,
                    'errors' => ['image' => ['Attachment is required']]
                ], 422);
            }

            // Update loan capital
            $loanCapital->update([
                'amount' => $validated['amount'],
                'remaining_amount' => $loanCapital->remaining_amount + ($validated['amount'] - $oldAmount),
                'payment_method' => $validated['payment_method'],
                'proof_img' => $imagePath,
                'notes' => $validated['notes'],
            ]);

            // Update balance for the loan's month
            $balance = Balance::whereYear('period_start', $loanCapital->loan_date->year)
                ->whereMonth('period_start', $loanCapital->loan_date->month)
                ->first();

            if ($balance) {
                // Revert old amount and method
                $balance->total_balance -= $oldAmount;
                if ($oldMethod === 'transfer') {
                    $balance->transfer_balance -= $oldAmount;
                } else {
                    $balance->cash_balance -= $oldAmount;
                }

                // Add new amount and method
                $balance->total_balance += $validated['amount'];
                if ($validated['payment_method'] === 'transfer') {
                    $balance->transfer_balance += $validated['amount'];
                } else {
                    $balance->cash_balance += $validated['amount'];
                }

                $balance->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update loan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeRepayment(Request $request, LoanCapital $loanCapital)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'paid_date' => 'required|date',
                'payment_method' => 'required|in:transfer,cash',
                'amount' => 'required|numeric|min:1|max:' . $loanCapital->remaining_amount,
                'notes' => 'nullable|string|max:500',
                'proof_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ], [
                'payment_method.required' => 'Payment method is required',
                'payment_method.in' => 'Payment method must be transfer or cash',
                'amount.required' => 'Amount is required',
                'amount.numeric' => 'Amount must be a number',
                'amount.min' => 'Amount must be at least 1',
                'amount.max' => 'Amount cannot exceed remaining amount (Rp ' . number_format($loanCapital->remaining_amount, 0, ',', '.') . ')',
                'proof_image.image' => 'File must be an image',
                'proof_image.mimes' => 'Image must be jpeg, png, or jpg',
                'proof_image.max' => 'Image size cannot exceed 2MB'
            ]);

            // Get balance for the repayment month
            $paidDate = Carbon::parse($validated['paid_date']);
            $balance = Balance::whereYear('period_start', $paidDate->year)
                ->whereMonth('period_start', $paidDate->month)
                ->first();

            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'amount' => ['Balance record not found for ' . $paidDate->format('F Y')]
                    ]
                ], 422);
            }

            // Validate balance tidak boleh minus
            $currentBalance = $validated['payment_method'] === 'transfer' 
                ? $balance->transfer_balance 
                : $balance->cash_balance;

            if ($validated['amount'] > $currentBalance) {
                $methodName = ucfirst($validated['payment_method']);
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'amount' => [
                            'Insufficient ' . $methodName . ' balance! Available: Rp ' . 
                            number_format($currentBalance, 0, ',', '.') . 
                            ', Required: Rp ' . number_format($validated['amount'], 0, ',', '.')
                        ]
                    ]
                ], 422);
            }

            DB::beginTransaction();

            // Handle image upload if provided
            $imagePath = null;
            if ($request->hasFile('proof_image')) {
                $image = $request->file('proof_image');
                $imageName = 'repayment_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('loan_repayments', $imageName, 'local');
            }

            // Create loan repayment record
            \App\Models\LoanRepayment::create([
                'loan_id' => $loanCapital->id,
                'paid_date' => $validated['paid_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'proof_img' => $imagePath,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update loan capital
            $newRemaining = $loanCapital->remaining_amount - $validated['amount'];
            $newStatus = $newRemaining <= 0 ? 'done' : 'outstanding';

            $loanCapital->update([
                'remaining_amount' => $newRemaining,
                'status' => $newStatus
            ]);

            // Update balance - KURANGI karena uang keluar untuk kembalikan modal
            // Balance sudah pasti cukup karena sudah divalidasi di atas
            $balance->total_balance -= $validated['amount'];
            
            if ($validated['payment_method'] === 'transfer') {
                $balance->transfer_balance -= $validated['amount'];
            } else {
                $balance->cash_balance -= $validated['amount'];
            }
            
            $balance->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Repayment recorded successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record repayment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function repaymentHistory(Request $request)
    {
        // Get month and year from request
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        // Parse to Carbon
        $currentDate = Carbon::create($year, $month, 1);
        
        // Get payment method filter
        $paymentMethodFilter = $request->input('payment_method', 'all');
        
        // Get per_page
        $perPage = $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        // Get search query
        $search = $request->input('search');
        
        // Build query for repayments
        $query = \App\Models\LoanRepayment::with('loanCapital')
            ->whereYear('paid_date', $year)
            ->whereMonth('paid_date', $month);
        
        // Apply payment method filter
        if ($paymentMethodFilter !== 'all') {
            $query->where('payment_method', $paymentMethodFilter);
        }
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('loanCapital', function($subQ) use ($search) {
                    $subQ->where('loan_code', 'like', '%' . $search . '%');
                })
                ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }
        
        // Get paginated repayments
        $repayments = $query->orderBy('paid_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
        
        // Handle AJAX requests
        if ($request->ajax()) {
            return view('pages.finance.loan-capital.repayment-history', compact(
                'repayments',
                'currentDate',
                'paymentMethodFilter',
                'perPage'
            ))->render();
        }
        
        return view('pages.finance.loan-capital.repayment-history', compact(
            'repayments',
            'currentDate',
            'paymentMethodFilter',
            'perPage'
        ));
    }
}
