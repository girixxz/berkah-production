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
use App\Models\Shipping;
use App\Models\Service;
use App\Models\DesignVariant;
use App\Models\OrderItem;
use App\Models\ExtraService;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
            'shipping',
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

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->except('page'));

        // Calculate statistics - Cache for 5 minutes
        $stats = Cache::remember('order_statistics', 300, function () {
            return [
                'total_orders' => Order::count(),
                'total_qty' => OrderItem::sum('qty'),
                'total_bill' => Invoice::sum('total_bill'),
                'remaining_due' => Invoice::sum('amount_due'),
                'pending' => Order::where('production_status', 'pending')->count(),
                'wip' => Order::where('production_status', 'wip')->count(),
                'finished' => Order::where('production_status', 'finished')->count(),
                'cancelled' => Order::where('production_status', 'cancelled')->count(),
            ];
        });

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
            'materialSleeves' => Cache::remember('material_sleeves', 86400, fn() => MaterialSleeve::orderBy('sleeve_name')->get()),
            'materialSizes' => Cache::remember('material_sizes', 86400, fn() => MaterialSize::orderBy('size_name')->get()),
            'services' => Cache::remember('services', 86400, fn() => Service::orderBy('service_name')->get()),
            'shippings' => Cache::remember('shippings', 86400, fn() => Shipping::orderBy('shipping_name')->get()),
            'provinces' => Cache::remember('provinces', 86400 * 7, fn() => \App\Models\Province::orderBy('province_name')->get()),
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
            'customer.village.district',
            'sale',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'shipping',
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
            'shipping_id' => 'required|exists:shippings,id',
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
            'shipping_id.required' => 'Shipping option is required.',
            'designs.required' => 'At least one design variant is required.',
            'designs.*.items.required' => 'At least one item is required for each design.',
            'additionals.*.service_id.required_with' => 'Service selection is required.',
            'additionals.*.service_id.exists' => 'Selected service is invalid.',
            'additionals.*.price.required_with' => 'Price is required.',
            'additionals.*.price.min' => 'Price must be at least 0.',
        ]);

        DB::beginTransaction();

        try {
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
                'shipping_id' => $validated['shipping_id'],
                'notes' => $validated['notes'],
                'total_qty' => $validated['total_qty'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'grand_total' => $validated['grand_total'],
                'production_status' => 'pending',
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
            'materialSleeves' => Cache::remember('material_sleeves', 86400, fn() => MaterialSleeve::orderBy('sleeve_name')->get()),
            'materialSizes' => Cache::remember('material_sizes', 86400, fn() => MaterialSize::orderBy('size_name')->get()),
            'services' => Cache::remember('services', 86400, fn() => Service::orderBy('service_name')->get()),
            'shippings' => Cache::remember('shippings', 86400, fn() => Shipping::orderBy('shipping_name')->get()),
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
            'shipping_id' => 'required|exists:shippings,id',
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
            'shipping_id.required' => 'Shipping option is required.',
            'designs.required' => 'At least one design variant is required.',
            'designs.*.items.required' => 'At least one item is required for each design.',
            'additionals.*.service_id.required_with' => 'Service selection is required.',
            'additionals.*.service_id.exists' => 'Selected service is invalid.',
            'additionals.*.price.required_with' => 'Price is required.',
            'additionals.*.price.min' => 'Price must be at least 0.',
        ]);

        DB::beginTransaction();

        try {
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
                'shipping_id' => $validated['shipping_id'],
                'notes' => $validated['notes'],
                'total_qty' => $validated['total_qty'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'grand_total' => $validated['grand_total'],
            ]);

            // Delete existing design variants, order items will be cascade deleted
            $order->designVariants()->delete();
            $order->orderItems()->delete();

            // Recreate Design Variants and Order Items
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
            'production_status' => 'cancelled'
        ]);

        return redirect()->route('admin.orders.index', ['date_range' => 'this_month'])
            ->with('message', 'Order cancelled successfully.')
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
}
