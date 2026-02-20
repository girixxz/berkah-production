<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\InternalTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InternalTransferController extends Controller
{
    public function index(Request $request)
    {
        // Get month and year from request or use current month
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Get balance for selected period (JANGAN auto-create di index)
        $balance = Balance::whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->first();

        // Get transfer type filter
        $transferType = $request->input('transfer_type', 'all');

        // Query internal transfers
        $query = InternalTransfer::with('balance')
            ->whereHas('balance', function ($q) use ($year, $month) {
                $q->whereYear('period_start', $year)
                  ->whereMonth('period_start', $month);
            });

        // Apply transfer type filter
        if ($transferType !== 'all') {
            $query->where('transfer_type', $transferType);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        // Get per page value
        $perPage = $request->input('per_page', 25);

        // Order by transfer_date desc
        $query->orderBy('transfer_date', 'desc')->orderBy('id', 'desc');

        // Paginate
        $transfers = $query->paginate($perPage)->appends($request->except('page'));

        // Get all transfers for search functionality
        $allTransfers = InternalTransfer::with('balance')
            ->whereHas('balance', function ($q) use ($year, $month) {
                $q->whereYear('period_start', $year)
                  ->whereMonth('period_start', $month);
            })
            ->get();

        // If AJAX request, return full page HTML (will be parsed by JavaScript)
        if ($request->wantsJson() || $request->ajax()) {
            return view('pages.finance.internal-transfer.index', compact(
                'transfers',
                'allTransfers',
                'balance',
                'month',
                'year',
                'transferType',
                'perPage'
            ))->render();
        }

        return view('pages.finance.internal-transfer.index', compact(
            'transfers',
            'allTransfers',
            'balance',
            'month',
            'year',
            'transferType',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'balance_month' => 'required|integer|min:1|max:12',
            'balance_year' => 'required|integer|min:2025',
            'transfer_date' => 'required|date',
            'transfer_type' => 'required|in:transfer_to_cash,cash_to_transfer',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'proof_image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
        ], [
            'balance_month.required' => 'Balance month is required',
            'balance_year.required' => 'Balance year is required',
            'balance_year.min' => 'Year must be 2025 or later',
            'transfer_date.required' => 'Transfer date is required',
            'transfer_type.required' => 'Transfer type is required',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be at least Rp 0.01',
            'proof_image.image' => 'File must be an image',
            'proof_image.mimes' => 'Image must be jpeg, jpg, or png',
            'proof_image.max' => 'Image size cannot exceed 2MB'
        ]);

        // Validasi: Tidak boleh create transfer sebelum Feb 2025
        $periodStart = Carbon::create($validated['balance_year'], $validated['balance_month'], 1);
        $minPeriod = Carbon::create(2025, 2, 1);
        
        if ($periodStart->lt($minPeriod)) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'balance_period' => ['This feature is only available from February 2025 onwards']
                ]
            ], 422);
        }
        $balance = Balance::firstOrCreate(
            [
                'period_start' => $periodStart,
            ],
            [
                'period_end' => $periodStart->copy()->endOfMonth(),
                'total_balance' => 0,
                'transfer_balance' => 0,
                'cash_balance' => 0,
            ]
        );

        // Validate sufficient balance based on transfer type
        if ($validated['transfer_type'] === 'transfer_to_cash') {
            // Source: Transfer Balance
            if ($validated['amount'] > $balance->transfer_balance) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'amount' => [
                            'Insufficient Transfer balance! Available: Rp ' . 
                            number_format($balance->transfer_balance, 0, ',', '.') . 
                            ', Required: Rp ' . number_format($validated['amount'], 0, ',', '.')
                        ]
                    ]
                ], 422);
            }
        } else {
            // Source: Cash Balance
            if ($validated['amount'] > $balance->cash_balance) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'amount' => [
                            'Insufficient Cash balance! Available: Rp ' . 
                            number_format($balance->cash_balance, 0, ',', '.') . 
                            ', Required: Rp ' . number_format($validated['amount'], 0, ',', '.')
                        ]
                    ]
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // Handle image upload if provided
            $imagePath = null;
            if ($request->hasFile('proof_image')) {
                $image = $request->file('proof_image');
                $imageName = 'transfer_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('internal_transfers', $imageName, 'local');
            }

            // Create internal transfer record
            $transfer = InternalTransfer::create([
                'transfer_date' => $validated['transfer_date'],
                'balance_id' => $balance->id,
                'transfer_type' => $validated['transfer_type'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'proof_img' => $imagePath,
            ]);

            // Update balance based on transfer type
            if ($validated['transfer_type'] === 'transfer_to_cash') {
                // Transfer → Cash
                $balance->transfer_balance -= $validated['amount'];
                $balance->cash_balance += $validated['amount'];
            } else {
                // Cash → Transfer
                $balance->cash_balance -= $validated['amount'];
                $balance->transfer_balance += $validated['amount'];
            }

            $balance->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Internal transfer created successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded image if exists
            if ($imagePath && Storage::disk('local')->exists($imagePath)) {
                Storage::disk('local')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create internal transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function serveImage($transferId)
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        $transfer = InternalTransfer::findOrFail($transferId);

        if (!$transfer->proof_img || !Storage::disk('local')->exists($transfer->proof_img)) {
            abort(404, 'Image not found');
        }

        $path = Storage::disk('local')->path($transfer->proof_img);

        // Get MIME type
        $mimeType = Storage::disk('local')->mimeType($transfer->proof_img) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'max-age=31536000, public',
        ]);
    }
}
