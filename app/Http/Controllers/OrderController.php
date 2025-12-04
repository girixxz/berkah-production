<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\ProductCategory;
use App\Models\MaterialCategory;
use App\Models\MaterialTexture;
use App\Models\MaterialSleeve;
use App\Models\MaterialSize;
use App\Models\Service;
use App\Models\DesignVariant;
use App\Models\OrderItem;
use App\Models\ExtraService;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'default');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');

        $query = Order::with([
            'customer',
            'sales',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'invoice'
        ]);

        // Apply filter based on production status or payment status
        if ($filter === 'pending') {
            $query->where('production_status', 'pending');
        } elseif ($filter === 'dp') {
            $query->whereHas('invoice', function ($q) {
                $q->where('status', 'dp');
            });
        } elseif ($filter === 'wip') {
            $query->where('production_status', 'wip');
        } elseif ($filter === 'finished') {
            $query->where('production_status', 'finished');
        } elseif ($filter === 'cancelled') {
            $query->where('production_status', 'cancelled');
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

        // Apply date range filter
        // Default to this month if no date filter is applied
        if ($dateRange) {
            $today = now();
            switch ($dateRange) {
                case 'last_month':
                    // Bulan kemarin - dari tanggal 1 sampai akhir bulan kemarin
                    $startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_7_days':
                    // 1 minggu yang lalu - dari 7 hari lalu sampai hari ini
                    $startDate = $today->copy()->subDays(7)->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'yesterday':
                    // Kemarin saja
                    $startDate = $today->copy()->subDay()->format('Y-m-d');
                    $endDate = $today->copy()->subDay()->format('Y-m-d');
                    break;
                case 'today':
                    // Hari ini saja
                    $startDate = $today->copy()->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'this_month':
                    // Bulan ini - dari tanggal 1 sampai akhir bulan ini
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }
        
        // Set default to this month if no date parameters at all
        if (!$dateRange && !$startDate && !$endDate) {
            // Redirect dengan parameter this_month agar terlihat di URL
            // Preserve session flash message if exists
            $redirect = redirect()->route('admin.orders.index', [
                'filter' => $filter,
                'search' => $search,
                'date_range' => 'this_month',
            ]);
            
            // Re-flash session message if it exists (so it survives the redirect)
            if (session()->has('message')) {
                $redirect->with('message', session('message'))
                        ->with('alert-type', session('alert-type', 'success'));
            }
            
            return $redirect;
        }

        if ($startDate) {
            $query->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('order_date', '<=', $endDate);
        }

        // Sort by wip_date (DESC) if filter is 'wip', finished_date if 'finished', cancelled_date if 'cancelled', otherwise by created_at
        if ($filter === 'wip') {
            $orders = $query->orderBy('wip_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->appends($request->except('page'));
        } elseif ($filter === 'finished') {
            $orders = $query->orderBy('finished_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->appends($request->except('page'));
        } elseif ($filter === 'cancelled') {
            $orders = $query->orderBy('cancelled_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->appends($request->except('page'));
        } else {
            $orders = $query->orderBy('created_at', 'desc')
                ->paginate(15)
                ->appends($request->except('page'));
        }

        // Calculate statistics based on the same filters (no cache, real-time)
        $statsQuery = Order::query();
        
        // Apply same date filter to stats
        if ($startDate) {
            $statsQuery->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $statsQuery->whereDate('order_date', '<=', $endDate);
        }

        $stats = [
            'total_orders' => (clone $statsQuery)->count(),
            'total_qty' => (clone $statsQuery)->join('order_items', 'orders.id', '=', 'order_items.order_id')->sum('order_items.qty'),
            'total_bill' => (clone $statsQuery)->join('invoices', 'orders.id', '=', 'invoices.order_id')->sum('invoices.total_bill'),
            'remaining_due' => (clone $statsQuery)->join('invoices', 'orders.id', '=', 'invoices.order_id')->sum('invoices.amount_due'),
            'pending' => (clone $statsQuery)->where('production_status', 'pending')->count(),
            'wip' => (clone $statsQuery)->where('production_status', 'wip')->count(),
            'finished' => (clone $statsQuery)->where('production_status', 'finished')->count(),
            'cancelled' => (clone $statsQuery)->where('production_status', 'cancelled')->count(),
        ];

        return view('pages.admin.orders.index', compact('orders', 'stats', 'dateRange', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = [
            'customers' => Customer::orderBy('customer_name')->get(),
            'sales' => Cache::remember('sales_list', 86400, fn() => Sale::orderBy('sales_name')->get()),
            'productCategories' => Cache::remember('product_categories', 86400, fn() => ProductCategory::orderBy('product_name')->get()),
            'materialCategories' => Cache::remember('material_categories', 86400, fn() => MaterialCategory::orderBy('material_name')->get()),
            'materialTextures' => Cache::remember('material_textures', 86400, fn() => MaterialTexture::orderBy('texture_name')->get()),
            'materialSleeves' => Cache::remember('material_sleeves', 86400, fn() => MaterialSleeve::orderBy('id')->get()),
            'materialSizes' => Cache::remember('material_sizes', 86400, fn() => MaterialSize::orderBy('id')->get()),
            'services' => Cache::remember('services', 86400, fn() => Service::orderBy('service_name')->get()),
            'provinces' => $this->getProvinces(),
        ];

        return view('pages.admin.orders.create', $data);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        // Load all relationships
        $order->load([
            'customer',
            'sale',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'invoice.payments',
            'orderItems.designVariant',
            'orderItems.size',
            'orderItems.sleeve',
            'extraServices.service'
        ]);

        // Group order items by design variant and sleeve
        $designVariants = [];
        
        foreach ($order->orderItems as $item) {
            $designName = $item->designVariant->design_name;
            $sleeveId = $item->sleeve_id;
            $sleeveName = $item->sleeve->sleeve_name;
            
            if (!isset($designVariants[$designName])) {
                $designVariants[$designName] = [];
            }
            
            if (!isset($designVariants[$designName][$sleeveId])) {
                // Calculate base price from first item (unit_price - extra_price)
                $basePrice = $item->unit_price - ($item->size->extra_price ?? 0);
                
                $designVariants[$designName][$sleeveId] = [
                    'sleeve_name' => $sleeveName,
                    'base_price' => $basePrice,
                    'items' => []
                ];
            }
            
            $designVariants[$designName][$sleeveId]['items'][] = $item;
        }

        return view('pages.admin.orders.show', [
            'order' => $order,
            'designVariants' => $designVariants
        ]);
    }

    /**
     * Get customer location data via AJAX (with cache)
     */
    public function getCustomerLocation($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Cache location data for 24 hours per customer
        $locationData = Cache::remember("customer_location_{$customerId}", 86400, function () use ($customer) {
            return $this->getCustomerLocationNames($customer);
        });

        return response()->json($locationData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'priority' => 'required|in:normal,high',
            'customer_id' => 'required|exists:customers,id',
            'sales_id' => 'required|exists:sales,id',
            'order_date' => 'required|date',
            'deadline' => 'required|date|after_or_equal:order_date',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_color' => 'required|string|max:100',
            'material_category_id' => 'required|exists:material_categories,id',
            'material_texture_id' => 'required|exists:material_textures,id',
            'shipping_type' => 'required|in:pickup,delivery',
            'notes' => 'nullable|string',
            'total_qty' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'order_image' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'designs' => 'required|array|min:1',
            'designs.*.items' => 'required|array|min:1',
            'designs.*.items.*.design_name' => 'required|string',
            'designs.*.items.*.sleeve_id' => 'required|exists:material_sleeves,id',
            'designs.*.items.*.size_id' => 'required|exists:material_sizes,id',
            'designs.*.items.*.qty' => 'required|integer|min:1',
            'designs.*.items.*.unit_price' => 'required|numeric|min:0',
            'additionals' => 'nullable|array',
            'additionals.*.service_id' => 'required_with:additionals|exists:services,id',
            'additionals.*.price' => 'required_with:additionals|numeric|min:0',
        ], [
            'priority.required' => 'Priority is required.',
            'customer_id.required' => 'Customer is required.',
            'sales_id.required' => 'Sales person is required.',
            'order_date.required' => 'Order date is required.',
            'deadline.required' => 'Deadline is required.',
            'deadline.after_or_equal' => 'Deadline must be the same as or after the order date.',
            'product_category_id.required' => 'Product category is required.',
            'product_color.required' => 'Product color is required.',
            'material_category_id.required' => 'Material category is required.',
            'material_texture_id.required' => 'Material texture is required.',
            'shipping_type.required' => 'Shipping type is required.',
            'shipping_type.in' => 'Shipping type must be either pickup or delivery.',
            'designs.required' => 'At least one design variant is required.',
            'designs.*.items.required' => 'At least one item is required for each design.',
            'additionals.*.service_id.required_with' => 'Service selection is required.',
            'additionals.*.service_id.exists' => 'Selected service is invalid.',
            'additionals.*.price.required_with' => 'Price is required.',
            'additionals.*.price.min' => 'Price must be at least 0.',
        ]);

        DB::beginTransaction();

        try {
            // Handle Order Image Upload to PRIVATE storage
            $imagePath = null;
            if ($request->hasFile('order_image')) {
                $image = $request->file('order_image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('orders', $imageName, 'local');
                $imagePath = 'orders/' . $imageName;
            }

            // Create Order
            $order = Order::create([
                'priority' => $validated['priority'],
                'customer_id' => $validated['customer_id'],
                'sales_id' => $validated['sales_id'],
                'order_date' => $validated['order_date'],
                'deadline' => $validated['deadline'],
                'product_category_id' => $validated['product_category_id'],
                'product_color' => $validated['product_color'],
                'material_category_id' => $validated['material_category_id'],
                'material_texture_id' => $validated['material_texture_id'],
                'shipping_type' => $validated['shipping_type'],
                'notes' => $validated['notes'],
                'total_qty' => $validated['total_qty'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'grand_total' => $validated['grand_total'],
                'production_status' => 'pending',
                'img_url' => $imagePath,
            ]);

            // Create Design Variants and Order Items
            foreach ($request->designs as $designData) {
                // Group items by design_name
                $designName = $designData['items'][0]['design_name'];
                
                $designVariant = DesignVariant::create([
                    'order_id' => $order->id,
                    'design_name' => $designName,
                ]);

                foreach ($designData['items'] as $item) {
                    $subtotal = $item['qty'] * $item['unit_price'];
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'design_variant_id' => $designVariant->id,
                        'sleeve_id' => $item['sleeve_id'],
                        'size_id' => $item['size_id'],
                        'qty' => $item['qty'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            // Create Extra Services
            if (!empty($request->additionals)) {
                foreach ($request->additionals as $additional) {
                    if (!empty($additional['service_id']) && isset($additional['price'])) {
                        ExtraService::create([
                            'order_id' => $order->id,
                            'service_id' => $additional['service_id'],
                            'price' => $additional['price'],
                        ]);
                    }
                }
            }

            // Create Invoice
            $invoiceNo = $this->generateInvoiceNumber();
            Invoice::create([
                'order_id' => $order->id,
                'invoice_no' => $invoiceNo,
                'total_bill' => $validated['grand_total'],
                'amount_paid' => 0,
                'amount_due' => $validated['grand_total'],
                'status' => 'unpaid',
                'notes' => null,
            ]);

            DB::commit();

            // Clear cache after order created
            Cache::forget('order_statistics');

            return redirect()->route('admin.orders.index', ['date_range' => 'this_month'])
                ->with('message', 'Order and invoice created successfully.')
                ->with('alert-type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('message', 'Failed to create order: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber()
    {
        // Get the last invoice number
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        
        if (!$lastInvoice) {
            // First invoice
            return 'INV-STGR-0001';
        }
        
        // Extract number from last invoice (INV-STGR-0001 -> 0001)
        $lastNumber = (int) substr($lastInvoice->invoice_no, -4);
        $newNumber = $lastNumber + 1;
        
        // Format with leading zeros (pad to 4 digits)
        return 'INV-STGR-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $data = [
            'order' => $order->load(['orderItems.designVariant', 'orderItems.size', 'orderItems.sleeve', 'extraServices']),
            'customers' => Customer::orderBy('customer_name')->get(),
            'sales' => Cache::remember('sales_list', 86400, fn() => Sale::orderBy('sales_name')->get()),
            'productCategories' => Cache::remember('product_categories', 86400, fn() => ProductCategory::orderBy('product_name')->get()),
            'materialCategories' => Cache::remember('material_categories', 86400, fn() => MaterialCategory::orderBy('material_name')->get()),
            'materialTextures' => Cache::remember('material_textures', 86400, fn() => MaterialTexture::orderBy('texture_name')->get()),
            'materialSleeves' => Cache::remember('material_sleeves', 86400, fn() => MaterialSleeve::orderBy('id')->get()),
            'materialSizes' => Cache::remember('material_sizes', 86400, fn() => MaterialSize::orderBy('id')->get()),
            'services' => Cache::remember('services', 86400, fn() => Service::orderBy('service_name')->get()),
        ];

        return view('pages.admin.orders.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // Validation
        $validated = $request->validate([
            'priority' => 'required|in:normal,high',
            'customer_id' => 'required|exists:customers,id',
            'sales_id' => 'required|exists:sales,id',
            'order_date' => 'required|date',
            'deadline' => 'required|date|after_or_equal:order_date',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_color' => 'required|string|max:100',
            'material_category_id' => 'required|exists:material_categories,id',
            'material_texture_id' => 'required|exists:material_textures,id',
            'shipping_type' => 'required|in:pickup,delivery',
            'notes' => 'nullable|string',
            'total_qty' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'designs' => 'required|array|min:1',
            'designs.*.items' => 'required|array|min:1',
            'designs.*.items.*.design_name' => 'required|string',
            'designs.*.items.*.sleeve_id' => 'required|exists:material_sleeves,id',
            'designs.*.items.*.size_id' => 'required|exists:material_sizes,id',
            'designs.*.items.*.qty' => 'required|integer|min:1',
            'designs.*.items.*.unit_price' => 'required|numeric|min:0',
            'additionals' => 'nullable|array',
            'additionals.*.service_id' => 'required_with:additionals|exists:services,id',
            'additionals.*.price' => 'required_with:additionals|numeric|min:0',
        ], [
            'priority.required' => 'Priority is required.',
            'customer_id.required' => 'Customer is required.',
            'sales_id.required' => 'Sales person is required.',
            'order_date.required' => 'Order date is required.',
            'deadline.required' => 'Deadline is required.',
            'deadline.after_or_equal' => 'Deadline must be the same as or after the order date.',
            'product_category_id.required' => 'Product category is required.',
            'product_color.required' => 'Product color is required.',
            'material_category_id.required' => 'Material category is required.',
            'material_texture_id.required' => 'Material texture is required.',
            'shipping_type.required' => 'Shipping type is required.',
            'shipping_type.in' => 'Shipping type must be either pickup or delivery.',
            'designs.required' => 'At least one design variant is required.',
            'designs.*.items.required' => 'At least one item is required for each design.',
            'additionals.*.service_id.required_with' => 'Service selection is required.',
            'additionals.*.service_id.exists' => 'Selected service is invalid.',
            'additionals.*.price.required_with' => 'Price is required.',
            'additionals.*.price.min' => 'Price must be at least 0.',
        ]);

        DB::beginTransaction();

        try {
            // Handle Order Image Upload to PRIVATE storage
            $imagePath = $order->img_url; // Keep existing image by default
            
            // Check if user wants to remove image
            if ($request->input('remove_order_image') === '1') {
                // Delete old image if exists
                if ($order->img_url && Storage::disk('local')->exists($order->img_url)) {
                    Storage::disk('local')->delete($order->img_url);
                }
                $imagePath = null;
            }
            
            // Check if user uploads new image
            if ($request->hasFile('order_image')) {
                // Delete old image if exists
                if ($order->img_url && Storage::disk('local')->exists($order->img_url)) {
                    Storage::disk('local')->delete($order->img_url);
                }

                // Upload new image to private storage
                $image = $request->file('order_image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('orders', $imageName, 'local');
                $imagePath = 'orders/' . $imageName;
            }

            // Update Order
            $order->update([
                'priority' => $validated['priority'],
                'customer_id' => $validated['customer_id'],
                'sales_id' => $validated['sales_id'],
                'order_date' => $validated['order_date'],
                'deadline' => $validated['deadline'],
                'product_category_id' => $validated['product_category_id'],
                'product_color' => $validated['product_color'],
                'material_category_id' => $validated['material_category_id'],
                'material_texture_id' => $validated['material_texture_id'],
                'shipping_type' => $validated['shipping_type'],
                'notes' => $validated['notes'],
                'total_qty' => $validated['total_qty'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'grand_total' => $validated['grand_total'],
                'img_url' => $imagePath,
            ]);

            // ==========================================
            // SMART UPDATE: PRESERVE WORK ORDERS
            // ==========================================
            
            // Get existing design variants with work orders
            $existingDesigns = $order->designVariants()->with('workOrder')->get();
            
            // Map existing designs by name for quick lookup
            $existingDesignsMap = $existingDesigns->keyBy('design_name');
            
            // Track which design names are in the new data
            $newDesignNames = collect($request->designs)->map(function($designData) {
                return $designData['items'][0]['design_name'];
            })->unique();
            
            // Delete ONLY design variants that:
            // 1. Don't exist in new data (removed by user)
            // 2. OR don't have work orders yet (safe to recreate)
            foreach ($existingDesigns as $existingDesign) {
                $shouldDelete = !$newDesignNames->contains($existingDesign->design_name) || 
                               !$existingDesign->workOrder;
                
                if ($shouldDelete) {
                    // Delete order items for this design first
                    OrderItem::where('design_variant_id', $existingDesign->id)->delete();
                    
                    // Then delete the design (if no work order, will be recreated)
                    if (!$existingDesign->workOrder) {
                        $existingDesign->delete();
                    }
                }
            }
            
            // Delete order items for designs that will be updated (have work orders)
            foreach ($newDesignNames as $designName) {
                if ($existingDesignsMap->has($designName)) {
                    $existingDesign = $existingDesignsMap->get($designName);
                    if ($existingDesign->workOrder) {
                        // Only delete order items, keep the design variant
                        OrderItem::where('design_variant_id', $existingDesign->id)->delete();
                    }
                }
            }

            // Process each design from request
            foreach ($request->designs as $designData) {
                $designName = $designData['items'][0]['design_name'];
                
                // Use existing design variant if it has work orders, otherwise create new
                if ($existingDesignsMap->has($designName) && $existingDesignsMap->get($designName)->workOrder) {
                    // REUSE existing design variant (preserves work orders via FK)
                    $designVariant = $existingDesignsMap->get($designName);
                } else {
                    // CREATE new design variant (no work order exists)
                    $designVariant = DesignVariant::create([
                        'order_id' => $order->id,
                        'design_name' => $designName,
                    ]);
                }

                // Create order items (always recreate for updated quantities/sizes)
                foreach ($designData['items'] as $item) {
                    $subtotal = $item['qty'] * $item['unit_price'];
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'design_variant_id' => $designVariant->id,
                        'sleeve_id' => $item['sleeve_id'],
                        'size_id' => $item['size_id'],
                        'qty' => $item['qty'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            // Delete existing extra services
            $order->extraServices()->delete();

            // Recreate Extra Services
            if (!empty($request->additionals)) {
                foreach ($request->additionals as $additional) {
                    if (!empty($additional['service_id']) && isset($additional['price'])) {
                        ExtraService::create([
                            'order_id' => $order->id,
                            'service_id' => $additional['service_id'],
                            'price' => $additional['price'],
                        ]);
                    }
                }
            }

            // Update Invoice if exists
            if ($order->invoice) {
                $order->invoice->update([
                    'total_bill' => $validated['grand_total'],
                    'amount_due' => $validated['grand_total'] - $order->invoice->amount_paid,
                ]);
            }

            DB::commit();

            // Clear cache after order updated
            Cache::forget('order_statistics');

            return redirect()->route('admin.orders.index', ['date_range' => 'this_month'])
                ->with('message', 'Order updated successfully.')
                ->with('alert-type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('message', 'Failed to update order: ' . $e->getMessage())
                ->with('alert-type', 'error');
        }
    }

    /**
     * Cancel the specified order.
     */
    public function cancel(Order $order)
    {
        if ($order->production_status === 'cancelled') {
            return redirect()->back()
                ->with('message', 'Order is already cancelled.')
                ->with('alert-type', 'warning');
        }

        $order->update([
            'production_status' => 'cancelled',
            'cancelled_date' => now()
        ]);

        return redirect()->route('admin.orders.index', ['date_range' => 'this_month'])
            ->with('message', 'Order cancelled successfully.')
            ->with('alert-type', 'success');
    }

    /**
     * Move order to shipping
     */
    public function moveToShipping(Order $order)
    {
        // Check if order is finished and not yet shipped
        if ($order->production_status !== 'finished') {
            return redirect()->back()
                ->with('message', 'Only finished orders can be moved to shipping.')
                ->with('alert-type', 'warning');
        }

        if ($order->shipping_status === 'shipped') {
            return redirect()->back()
                ->with('message', 'Order is already shipped.')
                ->with('alert-type', 'warning');
        }

        // Check if remaining due is 0 (payment completed)
        if ($order->invoice && $order->invoice->amount_due > 0) {
            return redirect()->back()
                ->with('message', 'Cannot move to shipping. Remaining payment is Rp ' . number_format($order->invoice->amount_due, 0, ',', '.') . '. Please complete payment first.')
                ->with('alert-type', 'error');
        }

        // Update shipping status and date
        $order->update([
            'shipping_status' => 'shipped',
            'shipping_date' => now()
        ]);

        return redirect()->back()
            ->with('message', 'Order moved to shipping successfully.')
            ->with('alert-type', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();

        // Clear cache after order deleted
        Cache::forget('order_statistics');

        return redirect()->route('admin.orders.index', ['date_range' => 'this_month'])
            ->with('message', 'Order deleted successfully.')
            ->with('alert-type', 'success');
    }

    /**
     * Download invoice PDF for the specified order.
     */
    public function downloadInvoice(Order $order)
    {
        $order->load([
            'invoice.payments',
            'customer',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'sale',
            'orderItems.designVariant',
            'orderItems.size',
            'orderItems.sleeve',
            'extraServices.service'
        ]);

        // Group order items by design variant and sleeve
        $designVariants = $order->designVariants->map(function ($variant) {
            $groupedItems = $variant->orderItems->groupBy(function ($item) {
                return $item->sleeve->name;
            })->map(function ($items, $sleeveName) {
                $sizes = $items->map(function ($item) {
                    return [
                        'size' => $item->size->name,
                        'qty' => $item->qty,
                        'price' => $item->price,
                        'total' => $item->total,
                    ];
                });

                return [
                    'sleeve' => $sleeveName,
                    'sizes' => $sizes,
                ];
            });

            return [
                'variant' => $variant,
                'grouped_items' => $groupedItems,
            ];
        });

        // Filter payments > 10 for display (exclude fiktif)
        $allApprovedPayments = $order->invoice->payments->where('status', 'approved');
        $approvedPayments = $allApprovedPayments->filter(function ($payment) {
            return $payment->amount > 10;
        });
        $totalPaid = $allApprovedPayments->sum('amount');

        // Generate PDF using Spatie Laravel PDF
        return \Spatie\LaravelPdf\Facades\Pdf::view('pages.admin.orders.invoice-pdf', [
            'order' => $order,
            'designVariants' => $designVariants,
            'approvedPayments' => $approvedPayments,
            'totalPaid' => $totalPaid,
        ])
        ->withBrowsershot(function ($browsershot) {
            $browsershot->setChromePath('/usr/bin/google-chrome-stable')
                        ->noSandbox()
                        ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox']);
        })
        ->format('a4')
        ->name("Invoice-{$order->invoice->invoice_no}.pdf");
    }

    /**
     * Get provinces from API
     */
    private function getProvinces()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json");

            if ($response->successful()) {
                $data = $response->json();
                
                // Transform to match expected format with object-like access
                return collect($data)->map(function ($item) {
                    return (object) [
                        'id' => $item['id'],
                        'province_name' => $item['name']
                    ];
                });
            }

            return collect([]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching provinces from API: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get location names for customer from API
     */
    private function getCustomerLocationNames($customer)
    {
        $locationData = [
            'province_name' => '-',
            'city_name' => '-',
            'district_name' => '-',
            'village_name' => '-'
        ];

        try {
            // Fetch village data (includes district info)
            if ($customer->village_id) {
                $villageResponse = \Illuminate\Support\Facades\Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/village/{$customer->village_id}.json");
                if ($villageResponse->successful()) {
                    $villageData = $villageResponse->json();
                    $locationData['village_name'] = $villageData['name'] ?? '-';
                }
            }

            // Fetch district data
            if ($customer->district_id) {
                $districtResponse = \Illuminate\Support\Facades\Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/district/{$customer->district_id}.json");
                if ($districtResponse->successful()) {
                    $districtData = $districtResponse->json();
                    $locationData['district_name'] = $districtData['name'] ?? '-';
                }
            }

            // Fetch city data
            if ($customer->city_id) {
                $cityResponse = \Illuminate\Support\Facades\Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/regency/{$customer->city_id}.json");
                if ($cityResponse->successful()) {
                    $cityData = $cityResponse->json();
                    $locationData['city_name'] = $cityData['name'] ?? '-';
                }
            }

            // Fetch province data
            if ($customer->province_id) {
                $provinceResponse = \Illuminate\Support\Facades\Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/province/{$customer->province_id}.json");
                if ($provinceResponse->successful()) {
                    $provinceData = $provinceResponse->json();
                    $locationData['province_name'] = $provinceData['name'] ?? '-';
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching location data: ' . $e->getMessage());
        }

        return $locationData;
    }

    /**
     * Serve order image (private file)
     * Only accessible by authenticated users
     * 
     * @param \App\Models\Order $order
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function serveOrderImage(Order $order)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        // Check if file exists
        if (!$order->img_url || !Storage::disk('local')->exists($order->img_url)) {
            abort(404, 'Order image not found');
        }

        // Get file path
        $path = Storage::disk('local')->path($order->img_url);
        
        // Get mime type from file extension
        $mimeType = Storage::disk('local')->mimeType($order->img_url) ?: 'application/octet-stream';

        // Return file response
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
