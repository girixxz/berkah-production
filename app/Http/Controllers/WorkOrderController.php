<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WorkOrderController extends Controller
{
    /**
     * Display a listing of WIP orders for work order management.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');

        // Query orders that are in WIP status
        $query = Order::with([
            'customer',
            'productCategory',
            'invoice',
            'orderItems',
            'designVariants',
            'workOrders'
        ])
        ->where('production_status', 'wip');

        // Apply filter based on work order status
        if ($filter === 'pending') {
            $query->where('work_order_status', 'pending');
        } elseif ($filter === 'created') {
            $query->where('work_order_status', 'created');
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('invoice', function ($invoiceQuery) use ($search) {
                    $invoiceQuery->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        // Apply date range filter - default to this month
        if ($dateRange) {
            $today = now();
            switch ($dateRange) {
                case 'last_month':
                    $startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_7_days':
                    $startDate = $today->copy()->subDays(7)->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'yesterday':
                    $startDate = $today->copy()->subDay()->format('Y-m-d');
                    $endDate = $today->copy()->subDay()->format('Y-m-d');
                    break;
                case 'today':
                    $startDate = $today->copy()->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'this_month':
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        // Set default to this month if no date parameters
        if (!$dateRange && !$startDate && !$endDate) {
            return redirect()->route('admin.work-orders.index', [
                'filter' => $filter,
                'search' => $search,
                'date_range' => 'this_month',
            ]);
        }

        if ($startDate) {
            $query->whereDate('wip_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('wip_date', '<=', $endDate);
        }

        // Order by wip_date DESC (newest first)
        $orders = $query->orderBy('wip_date', 'desc')
            ->paginate(15)
            ->appends($request->except('page'));

        // Calculate statistics
        $stats = [
            'total_orders' => Order::where('production_status', 'wip')->count(),
            'pending' => Order::where('production_status', 'wip')
                ->where('work_order_status', 'pending')
                ->count(),
            'created' => Order::where('production_status', 'wip')
                ->where('work_order_status', 'created')
                ->count(),
        ];

        return view('pages.admin.work-orders.index', compact('orders', 'stats', 'dateRange', 'startDate', 'endDate'));
    }

    /**
     * Show the form for managing work orders for a specific order.
     */
    public function manage($orderId)
    {
        // Load order with all necessary relationships
        $order = Order::with([
            'customer',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'invoice',
            'designVariants.workOrder.cutting.cuttingPattern',
            'designVariants.workOrder.cutting.chainCloth',
            'designVariants.workOrder.cutting.ribSize',
            'designVariants.workOrder.printing.printInk',
            'designVariants.workOrder.printing.finishing',
            'designVariants.workOrder.printingPlacement',
            'designVariants.workOrder.sewing.neckOverdeck',
            'designVariants.workOrder.sewing.underarmOverdeck',
            'designVariants.workOrder.sewing.sideSplit',
            'designVariants.workOrder.sewing.sewingLabel',
            'designVariants.workOrder.packing.plasticPacking',
            'designVariants.workOrder.packing.sticker',
            'orderItems.size',
            'orderItems.sleeve'
        ])->findOrFail($orderId);

        // Check if order is in WIP status
        if ($order->production_status !== 'wip') {
            return redirect()->route('admin.work-orders.index')
                ->with('error', 'Order must be in WIP status to manage work orders');
        }

        // Auto-create work orders with status='pending' if not exist
        foreach ($order->designVariants as $designVariant) {
            if (!$designVariant->workOrder) {
                \App\Models\WorkOrder::create([
                    'order_id' => $order->id,
                    'design_variant_id' => $designVariant->id,
                    'status' => 'pending',
                ]);
            }
        }

        // Reload order with work orders AND all relationships
        $order->load([
            'designVariants.workOrder.cutting',
            'designVariants.workOrder.printing',
            'designVariants.workOrder.printingPlacement',
            'designVariants.workOrder.sewing',
            'designVariants.workOrder.packing',
        ]);

        // Load all master data for dropdowns
        $masterData = [
            'cuttingPatterns' => \App\Models\CuttingPattern::all(),
            'chainCloths' => \App\Models\ChainCloth::all(),
            'ribSizes' => \App\Models\RibSize::all(),
            'printInks' => \App\Models\PrintInk::all(),
            'finishings' => \App\Models\Finishing::all(),
            'neckOverdecks' => \App\Models\NeckOverdeck::all(),
            'underarmOverdecks' => \App\Models\UnderarmOverdeck::all(),
            'sideSplits' => \App\Models\SideSplit::all(),
            'sewingLabels' => \App\Models\SewingLabel::all(),
            'plasticPackings' => \App\Models\PlasticPacking::all(),
            'stickers' => \App\Models\Sticker::all(),
        ];

        return view('pages.admin.work-orders.manage', compact('order', 'masterData'));
    }

    /**
     * Store a new work order for a design.
     */
    public function store(Request $request)
    {
        // VALIDASI BACKEND - 2x KEAMANAN AGAR TIDAK TERJEBIOL!!!
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'design_variant_id' => 'required|integer|exists:design_variants,id',
            'mockup_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            
            // Cutting - SEMUA REQUIRED!
            'cutting_pattern_id' => 'required|integer|exists:cutting_patterns,id',
            'chain_cloth_id' => 'required|integer|exists:chain_cloths,id',
            'rib_size_id' => 'required|integer|exists:rib_sizes,id',
            'custom_size_chart_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'cutting_notes' => 'nullable|string|max:1000',
            
            // Printing - SEMUA REQUIRED!
            'print_ink_id' => 'required|integer|exists:print_inks,id',
            'finishing_id' => 'required|integer|exists:finishings,id',
            'printing_detail_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'printing_notes' => 'nullable|string|max:1000',
            
            // Printing Placement - IMG OPTIONAL (nullable)
            'placement_detail_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'placement_notes' => 'nullable|string|max:1000',
            
            // Sewing - SEMUA REQUIRED!
            'neck_overdeck_id' => 'required|integer|exists:neck_overdecks,id',
            'underarm_overdeck_id' => 'required|integer|exists:underarm_overdecks,id',
            'side_split_id' => 'required|integer|exists:side_splits,id',
            'sewing_label_id' => 'required|integer|exists:sewing_labels,id',
            'sewing_detail_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'sewing_notes' => 'nullable|string|max:1000',
            
            // Packing - SEMUA REQUIRED!
            'plastic_packing_id' => 'required|integer|exists:plastic_packings,id',
            'sticker_id' => 'required|integer|exists:stickers,id',
            'hangtag_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'packing_notes' => 'nullable|string|max:1000',
        ], [
            // Custom error messages in Indonesian
            'required' => ':attribute harus diisi!',
            'integer' => ':attribute harus berupa angka!',
            'exists' => ':attribute yang dipilih tidak valid!',
            'image' => ':attribute harus berupa gambar!',
            'mimes' => ':attribute harus berformat: jpeg, png, jpg, gif, atau webp!',
            'max' => ':attribute tidak boleh lebih dari :max KB!',
        ], [
            // Custom attribute names
            'order_id' => 'Order',
            'design_variant_id' => 'Design Variant',
            'cutting_pattern_id' => 'Pola Potong',
            'chain_cloth_id' => 'Rantai Kain',
            'rib_size_id' => 'Ukuran Rib',
            'print_ink_id' => 'Tinta Sablon',
            'finishing_id' => 'Finishing',
            'neck_overdeck_id' => 'Overdeck Leher',
            'underarm_overdeck_id' => 'Overdeck Ketiak',
            'side_split_id' => 'Belahan Samping',
            'sewing_label_id' => 'Label Jahit',
            'plastic_packing_id' => 'Plastik Packing',
            'sticker_id' => 'Stiker',
            'mockup_img' => 'Gambar Mockup',
            'custom_size_chart_img' => 'Gambar Size Chart',
            'printing_detail_img' => 'Gambar Detail Sablon',
            'placement_detail_img' => 'Gambar Penempatan Sablon',
            'sewing_detail_img' => 'Gambar Detail Jahit',
            'hangtag_img' => 'Gambar Hangtag',
        ]);
        
        // EXTRA VALIDATION - Pastikan design_variant_id benar-benar milik order_id ini
        $designVariant = \App\Models\DesignVariant::where('id', $validated['design_variant_id'])
            ->where('order_id', $validated['order_id'])
            ->first();
            
        if (!$designVariant) {
            return back()->withErrors([
                'design_variant_id' => 'Design Variant tidak valid untuk Order ini!'
            ])->withInput();
        }

        try {
            DB::beginTransaction();

            // Find or create work order
            $workOrder = \App\Models\WorkOrder::firstOrCreate(
                [
                    'order_id' => $validated['order_id'],
                    'design_variant_id' => $validated['design_variant_id'],
                ],
                ['status' => 'created']
            );

            // Handle mockup image upload to local storage
            if ($request->hasFile('mockup_img')) {
                // Delete old image if exists
                if ($workOrder->mockup_img_url && Storage::disk('local')->exists($workOrder->mockup_img_url)) {
                    Storage::disk('local')->delete($workOrder->mockup_img_url);
                }
                
                $file = $request->file('mockup_img');
                $filename = time() . '_mockup_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('work-orders/mockup', $filename, 'local');
                
                $workOrder->update([
                    'mockup_img_url' => 'work-orders/mockup/' . $filename,
                    'status' => 'created'
                ]);
            } elseif ($request->input('delete_mockup_img') === 'true') {
                // User explicitly deleted the image
                if ($workOrder->mockup_img_url && Storage::disk('local')->exists($workOrder->mockup_img_url)) {
                    Storage::disk('local')->delete($workOrder->mockup_img_url);
                }
                $workOrder->update([
                    'mockup_img_url' => null,
                    'status' => 'created'
                ]);
            } else {
                $workOrder->update(['status' => 'created']);
            }

            // Handle cutting
            $cuttingData = [
                'work_order_id' => $workOrder->id,
                'cutting_pattern_id' => $validated['cutting_pattern_id'],
                'chain_cloth_id' => $validated['chain_cloth_id'],
                'rib_size_id' => $validated['rib_size_id'],
                'notes' => $validated['cutting_notes'] ?? null,
            ];

            $existingCutting = \App\Models\WorkOrderCutting::where('work_order_id', $workOrder->id)->first();

            if ($request->hasFile('custom_size_chart_img')) {
                // Delete old image if exists
                if ($existingCutting && $existingCutting->custom_size_chart_img_url && Storage::disk('local')->exists($existingCutting->custom_size_chart_img_url)) {
                    Storage::disk('local')->delete($existingCutting->custom_size_chart_img_url);
                }
                
                $file = $request->file('custom_size_chart_img');
                $filename = time() . '_cutting_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('work-orders/cutting', $filename, 'local');
                $cuttingData['custom_size_chart_img_url'] = 'work-orders/cutting/' . $filename;
            } elseif ($request->input('delete_custom_size_chart_img') === 'true') {
                // User explicitly deleted the image
                if ($existingCutting && $existingCutting->custom_size_chart_img_url && Storage::disk('local')->exists($existingCutting->custom_size_chart_img_url)) {
                    Storage::disk('local')->delete($existingCutting->custom_size_chart_img_url);
                }
                $cuttingData['custom_size_chart_img_url'] = null;
            }

            \App\Models\WorkOrderCutting::updateOrCreate(
                ['work_order_id' => $workOrder->id],
                $cuttingData
            );

            // Handle printing
            $printingData = [
                'work_order_id' => $workOrder->id,
                'print_ink_id' => $validated['print_ink_id'],
                'finishing_id' => $validated['finishing_id'],
                'notes' => $validated['printing_notes'] ?? null,
            ];

            $existingPrinting = \App\Models\WorkOrderPrinting::where('work_order_id', $workOrder->id)->first();

            if ($request->hasFile('printing_detail_img')) {
                // Delete old image if exists
                if ($existingPrinting && $existingPrinting->detail_img_url && Storage::disk('local')->exists($existingPrinting->detail_img_url)) {
                    Storage::disk('local')->delete($existingPrinting->detail_img_url);
                }
                
                $file = $request->file('printing_detail_img');
                $filename = time() . '_printing_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('work-orders/printing', $filename, 'local');
                $printingData['detail_img_url'] = 'work-orders/printing/' . $filename;
            } elseif ($request->input('delete_printing_detail_img') === 'true') {
                // User explicitly deleted the image
                if ($existingPrinting && $existingPrinting->detail_img_url && Storage::disk('local')->exists($existingPrinting->detail_img_url)) {
                    Storage::disk('local')->delete($existingPrinting->detail_img_url);
                }
                $printingData['detail_img_url'] = null;
            }

            \App\Models\WorkOrderPrinting::updateOrCreate(
                ['work_order_id' => $workOrder->id],
                $printingData
            );

            // Handle printing placement
            $placementData = [
                'work_order_id' => $workOrder->id,
                'notes' => $validated['placement_notes'] ?? null,
            ];

            $existingPlacement = \App\Models\WorkOrderPrintingPlacement::where('work_order_id', $workOrder->id)->first();

            if ($request->hasFile('placement_detail_img')) {
                // Delete old image if exists
                if ($existingPlacement && $existingPlacement->detail_img_url && Storage::disk('local')->exists($existingPlacement->detail_img_url)) {
                    Storage::disk('local')->delete($existingPlacement->detail_img_url);
                }
                
                $file = $request->file('placement_detail_img');
                $filename = time() . '_placement_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('work-orders/placement', $filename, 'local');
                $placementData['detail_img_url'] = 'work-orders/placement/' . $filename;
            } elseif ($request->input('delete_placement_detail_img') === 'true') {
                // User explicitly deleted the image
                if ($existingPlacement && $existingPlacement->detail_img_url && Storage::disk('local')->exists($existingPlacement->detail_img_url)) {
                    Storage::disk('local')->delete($existingPlacement->detail_img_url);
                }
                $placementData['detail_img_url'] = null;
            }

            \App\Models\WorkOrderPrintingPlacement::updateOrCreate(
                ['work_order_id' => $workOrder->id],
                $placementData
            );

            // Handle sewing
            $sewingData = [
                'work_order_id' => $workOrder->id,
                'neck_overdeck_id' => $validated['neck_overdeck_id'],
                'underarm_overdeck_id' => $validated['underarm_overdeck_id'],
                'side_split_id' => $validated['side_split_id'],
                'sewing_label_id' => $validated['sewing_label_id'],
                'notes' => $validated['sewing_notes'] ?? null,
            ];

            $existingSewing = \App\Models\WorkOrderSewing::where('work_order_id', $workOrder->id)->first();

            if ($request->hasFile('sewing_detail_img')) {
                // Delete old image if exists
                if ($existingSewing && $existingSewing->detail_img_url && Storage::disk('local')->exists($existingSewing->detail_img_url)) {
                    Storage::disk('local')->delete($existingSewing->detail_img_url);
                }
                
                $file = $request->file('sewing_detail_img');
                $filename = time() . '_sewing_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('work-orders/sewing', $filename, 'local');
                $sewingData['detail_img_url'] = 'work-orders/sewing/' . $filename;
            } elseif ($request->input('delete_sewing_detail_img') === 'true') {
                // User explicitly deleted the image
                if ($existingSewing && $existingSewing->detail_img_url && Storage::disk('local')->exists($existingSewing->detail_img_url)) {
                    Storage::disk('local')->delete($existingSewing->detail_img_url);
                }
                $sewingData['detail_img_url'] = null;
            }

            \App\Models\WorkOrderSewing::updateOrCreate(
                ['work_order_id' => $workOrder->id],
                $sewingData
            );

            // Handle packing
            $packingData = [
                'work_order_id' => $workOrder->id,
                'plastic_packing_id' => $validated['plastic_packing_id'],
                'sticker_id' => $validated['sticker_id'],
                'notes' => $validated['packing_notes'] ?? null,
            ];

            $existingPacking = \App\Models\WorkOrderPacking::where('work_order_id', $workOrder->id)->first();

            if ($request->hasFile('hangtag_img')) {
                // Delete old image if exists
                if ($existingPacking && $existingPacking->hangtag_img_url && Storage::disk('local')->exists($existingPacking->hangtag_img_url)) {
                    Storage::disk('local')->delete($existingPacking->hangtag_img_url);
                }
                
                $file = $request->file('hangtag_img');
                $filename = time() . '_hangtag_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('work-orders/packing', $filename, 'local');
                $packingData['hangtag_img_url'] = 'work-orders/packing/' . $filename;
            } elseif ($request->input('delete_hangtag_img') === 'true') {
                // User explicitly deleted the image
                if ($existingPacking && $existingPacking->hangtag_img_url && Storage::disk('local')->exists($existingPacking->hangtag_img_url)) {
                    Storage::disk('local')->delete($existingPacking->hangtag_img_url);
                }
                $packingData['hangtag_img_url'] = null;
            }

            \App\Models\WorkOrderPacking::updateOrCreate(
                ['work_order_id' => $workOrder->id],
                $packingData
            );

            // ✅ AUTO-CHECK: Update order work_order_status if all designs completed
            $statusUpdated = $this->checkAndUpdateOrderWorkOrderStatus($validated['order_id']);

            DB::commit();

            // Prepare success message
            $successMessage = 'Work order berhasil disimpan!';
            if ($statusUpdated) {
                $successMessage .= ' 🎉 Semua design telah selesai - Status order otomatis diupdate ke CREATED!';
            }

            return redirect()
                ->route('admin.work-orders.manage', ['order' => $validated['order_id']])
                ->with('success', $successMessage);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            // Re-throw validation errors
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Work Order Save Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'order_id' => $request->order_id ?? 'N/A',
                'design_variant_id' => $request->design_variant_id ?? 'N/A',
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menyimpan work order. Silakan coba lagi atau hubungi admin.']);
        }
    }

    /**
     * Update an existing work order.
     */
    public function update(Request $request, $workOrderId)
    {
        // Update menggunakan logic yang sama dengan store
        // Karena form submit ke store route dengan _method=PUT
        return $this->store($request);
    }

    /**
     * Finalize all work orders and update order status.
     */
    public function finalize($orderId)
    {
        try {
            $order = Order::with('designVariants.workOrder')->findOrFail($orderId);

            // Check if all designs have completed work orders
            $allCompleted = $order->designVariants->every(function ($design) {
                return $design->workOrder && $design->workOrder->status === 'created';
            });

            if (!$allCompleted) {
                return redirect()
                    ->route('admin.work-orders.manage', $orderId)
                    ->with('error', 'All designs must have completed work orders before finalizing');
            }

            // Update order work_order_status
            $order->update(['work_order_status' => 'created']);

            // TODO: Generate PDFs for all designs here
            // This will be implemented in a future update

            return redirect()
                ->route('admin.work-orders.index')
                ->with('success', 'All work orders finalized successfully');

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.work-orders.manage', $orderId)
                ->with('error', 'Failed to finalize work orders: ' . $e->getMessage());
        }
    }

    /**
     * Serve work order mockup image (USING MODEL BINDING - same pattern as Payment)
     * 
     * @param \App\Models\WorkOrder $workOrder
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function serveMockupImage(\App\Models\WorkOrder $workOrder)
    {
        // Check if user is authenticated
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        if (!$auth->check()) {
            abort(403, 'Unauthorized');
        }

        // Check if file exists
        if (!$workOrder->mockup_img_url || !Storage::disk('local')->exists($workOrder->mockup_img_url)) {
            abort(404, 'Mockup image not found');
        }

        // Get file path
        $path = Storage::disk('local')->path($workOrder->mockup_img_url);
        
        // Get mime type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $path) : 'application/octet-stream';

        // Return file response
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Serve cutting size chart image (USING MODEL BINDING)
     * 
     * @param \App\Models\WorkOrderCutting $cutting
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function serveCuttingImage(\App\Models\WorkOrderCutting $cutting)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        if (!$auth->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$cutting->custom_size_chart_img_url || !Storage::disk('local')->exists($cutting->custom_size_chart_img_url)) {
            abort(404, 'Cutting image not found');
        }

        $path = Storage::disk('local')->path($cutting->custom_size_chart_img_url);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $path) : 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Serve printing detail image (USING MODEL BINDING)
     * 
     * @param \App\Models\WorkOrderPrinting $printing
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function servePrintingImage(\App\Models\WorkOrderPrinting $printing)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        if (!$auth->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$printing->detail_img_url || !Storage::disk('local')->exists($printing->detail_img_url)) {
            abort(404, 'Printing image not found');
        }

        $path = Storage::disk('local')->path($printing->detail_img_url);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $path) : 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Serve printing placement image (USING MODEL BINDING)
     * 
     * @param \App\Models\WorkOrderPrintingPlacement $placement
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function servePlacementImage(\App\Models\WorkOrderPrintingPlacement $placement)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        if (!$auth->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$placement->detail_img_url || !Storage::disk('local')->exists($placement->detail_img_url)) {
            abort(404, 'Placement image not found');
        }

        $path = Storage::disk('local')->path($placement->detail_img_url);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $path) : 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Serve sewing detail image (USING MODEL BINDING)
     * 
     * @param \App\Models\WorkOrderSewing $sewing
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function serveSewingImage(\App\Models\WorkOrderSewing $sewing)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        if (!$auth->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$sewing->detail_img_url || !Storage::disk('local')->exists($sewing->detail_img_url)) {
            abort(404, 'Sewing image not found');
        }

        $path = Storage::disk('local')->path($sewing->detail_img_url);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $path) : 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Serve packing hangtag image (USING MODEL BINDING)
     * 
     * @param \App\Models\WorkOrderPacking $packing
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function servePackingImage(\App\Models\WorkOrderPacking $packing)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        if (!$auth->check()) {
            abort(403, 'Unauthorized');
        }

        if (!$packing->hangtag_img_url || !Storage::disk('local')->exists($packing->hangtag_img_url)) {
            abort(404, 'Packing image not found');
        }

        $path = Storage::disk('local')->path($packing->hangtag_img_url);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $path) : 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Check if all design variants have completed work orders and auto-update order status.
     * This is triggered every time a work order is created/updated.
     * 
     * @param int $orderId
     * @return bool True if status was updated, false otherwise
     */
    private function checkAndUpdateOrderWorkOrderStatus($orderId)
    {
        try {
            $order = Order::with('designVariants.workOrder')->findOrFail($orderId);

            // Count total design variants
            $totalDesigns = $order->designVariants->count();

            // Count completed work orders (status = 'created')
            $completedWorkOrders = $order->designVariants->filter(function ($design) {
                return $design->workOrder && $design->workOrder->status === 'created';
            })->count();

            // If all designs have completed work orders, update order status
            if ($totalDesigns > 0 && $completedWorkOrders === $totalDesigns) {
                // Only update if current status is 'pending'
                if ($order->work_order_status === 'pending') {
                    $order->update(['work_order_status' => 'created']);
                    
                    Log::info("Order #{$orderId} work_order_status auto-updated to 'created' - All {$totalDesigns} designs completed");
                    
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to auto-update work_order_status for Order #{$orderId}: " . $e->getMessage());
            return false;
        }
    }
}
