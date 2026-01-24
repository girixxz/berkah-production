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
use App\Models\OrderReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'wip');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateRange = $request->input('date_range');
        $perPage = $request->input('per_page', 25); // Default 25

        // Validate per_page value
        $perPage = in_array($perPage, [5, 10, 15, 20, 25, 50, 100]) ? $perPage : 25;

        $query = Order::with([
            'customer',
            'sales',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'invoice',
            'designVariants'
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
                })
                ->orWhereHas('designVariants', function ($designQuery) use ($search) {
                    $designQuery->where('design_name', 'like', "%{$search}%");
                })
                ->orWhereHas('productCategory', function ($productQuery) use ($search) {
                    $productQuery->where('product_name', 'like', "%{$search}%");
                });
            });
        }

        // Apply date range filter
        // Default to last 30 days if no date filter is applied
        if (!$dateRange && !$startDate && !$endDate) {
            $dateRange = 'default';
        }
        
        if ($dateRange) {
            $today = now();
            switch ($dateRange) {
                case 'default':
                    // Default: Last 45 days - dari 45 hari lalu sampai hari ini
                    $startDate = $today->copy()->subDays(45)->format('Y-m-d');
                    $endDate = $today->copy()->format('Y-m-d');
                    break;
                case 'this_month':
                    // Bulan ini - dari tanggal 1 sampai akhir bulan ini
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_month':
                    // Bulan kemarin - dari tanggal 1 sampai akhir bulan kemarin
                    $startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        // Apply date filter based on status
        if ($startDate && $endDate) {
            if ($filter === 'wip') {
                $query->whereDate('wip_date', '>=', $startDate)
                      ->whereDate('wip_date', '<=', $endDate);
            } elseif ($filter === 'finished') {
                $query->whereDate('finished_date', '>=', $startDate)
                      ->whereDate('finished_date', '<=', $endDate);
            } elseif ($filter === 'cancelled') {
                $query->whereDate('cancelled_date', '>=', $startDate)
                      ->whereDate('cancelled_date', '<=', $endDate);
            } else {
                // For all, pending, dp - use order_date
                $query->whereDate('order_date', '>=', $startDate)
                      ->whereDate('order_date', '<=', $endDate);
            }
        }

        // Sort by respective date fields based on filter
        if ($filter === 'wip') {
            $orders = $query->orderBy('wip_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage)
                ->appends($request->except('page'));
        } elseif ($filter === 'finished') {
            $orders = $query->orderBy('finished_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage)
                ->appends($request->except('page'));
        } elseif ($filter === 'cancelled') {
            $orders = $query->orderBy('cancelled_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage)
                ->appends($request->except('page'));
        } else {
            // All, Pending - sort by order_date (Orderan Masuk), then by ID for consistency
            $orders = $query->orderBy('order_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage)
                ->appends($request->except('page'));
        }

        // Get all orders for search functionality (with same filters)
        $allOrdersQuery = Order::with([
            'customer',
            'sales',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'invoice',
            'designVariants'
        ]);

        // Apply same filter to allOrders
        if ($filter === 'pending') {
            $allOrdersQuery->where('production_status', 'pending');
        } elseif ($filter === 'dp') {
            $allOrdersQuery->whereHas('invoice', function ($q) {
                $q->where('status', 'dp');
            });
        } elseif ($filter === 'wip') {
            $allOrdersQuery->where('production_status', 'wip');
        } elseif ($filter === 'finished') {
            $allOrdersQuery->where('production_status', 'finished');
        } elseif ($filter === 'cancelled') {
            $allOrdersQuery->where('production_status', 'cancelled');
        }

        // Apply same date filter to allOrders based on status
        if ($startDate && $endDate) {
            if ($filter === 'wip') {
                $allOrdersQuery->whereDate('wip_date', '>=', $startDate)
                               ->whereDate('wip_date', '<=', $endDate);
            } elseif ($filter === 'finished') {
                $allOrdersQuery->whereDate('finished_date', '>=', $startDate)
                               ->whereDate('finished_date', '<=', $endDate);
            } elseif ($filter === 'cancelled') {
                $allOrdersQuery->whereDate('cancelled_date', '>=', $startDate)
                               ->whereDate('cancelled_date', '<=', $endDate);
            } else {
                // For all, pending, dp - use order_date
                $allOrdersQuery->whereDate('order_date', '>=', $startDate)
                               ->whereDate('order_date', '<=', $endDate);
            }
        }

        // Sort same as paginated orders
        if ($filter === 'wip') {
            $allOrders = $allOrdersQuery->orderBy('wip_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        } elseif ($filter === 'finished') {
            $allOrders = $allOrdersQuery->orderBy('finished_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        } elseif ($filter === 'cancelled') {
            $allOrders = $allOrdersQuery->orderBy('cancelled_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        } else {
            // All, Pending - sort by order_date (Orderan Masuk), then by ID for consistency
            $allOrders = $allOrdersQuery->orderBy('order_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        // Calculate statistics based on the same filters (no cache, real-time)
        $statsQuery = Order::query();
        
        // Apply same date filter to stats based on status
        if ($startDate && $endDate) {
            if ($filter === 'wip') {
                $statsQuery->whereDate('wip_date', '>=', $startDate)
                           ->whereDate('wip_date', '<=', $endDate);
            } elseif ($filter === 'finished') {
                $statsQuery->whereDate('finished_date', '>=', $startDate)
                           ->whereDate('finished_date', '<=', $endDate);
            } elseif ($filter === 'cancelled') {
                $statsQuery->whereDate('cancelled_date', '>=', $startDate)
                           ->whereDate('cancelled_date', '<=', $endDate);
            } else {
                // For all, pending, dp - use order_date
                $statsQuery->whereDate('order_date', '>=', $startDate)
                           ->whereDate('order_date', '<=', $endDate);
            }
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

        return view('pages.admin.orders.index', compact('orders', 'allOrders', 'stats', 'dateRange', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = [
            'customers' => Customer::orderBy('customer_name')->get(),
            'sales' => Cache::remember('sales_list', 86400, fn() => Sale::orderBy('sales_name')->get()),
            'productCategories' => Cache::remember('product_categories', 86400, fn() => ProductCategory::orderBy('sort_order', 'asc')->get()),
            'materialCategories' => Cache::remember('material_categories', 86400, fn() => MaterialCategory::orderBy('sort_order', 'asc')->get()),
            'materialTextures' => Cache::remember('material_textures', 86400, fn() => MaterialTexture::orderBy('sort_order', 'asc')->get()),
            'materialSleeves' => Cache::remember('material_sleeves', 86400, fn() => MaterialSleeve::orderBy('id')->get()),
            'materialSizes' => Cache::remember('material_sizes', 86400, fn() => MaterialSize::orderBy('sort_order', 'asc')->get()),
            'services' => Cache::remember('services', 86400, fn() => Service::orderBy('sort_order', 'asc')->get()),
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

        // Group order items by design variant (name + color) and sleeve
        $designVariants = [];
        
        foreach ($order->orderItems as $item) {
            $designName = $item->designVariant->design_name;
            $designColor = $item->designVariant->color;
            // Create unique key: "DesignName|Color"
            $designKey = $designName . '|' . $designColor;
            $sleeveId = $item->sleeve_id;
            $sleeveName = $item->sleeve->sleeve_name;
            
            if (!isset($designVariants[$designKey])) {
                $designVariants[$designKey] = [
                    'name' => $designName,
                    'color' => $designColor,
                    'sleeves' => []
                ];
            }
            
            if (!isset($designVariants[$designKey]['sleeves'][$sleeveId])) {
                // Calculate base price from first item (unit_price - extra_price)
                $basePrice = $item->unit_price - ($item->size->extra_price ?? 0);
                
                $designVariants[$designKey]['sleeves'][$sleeveId] = [
                    'sleeve_name' => $sleeveName,
                    'base_price' => $basePrice,
                    'items' => []
                ];
            }
            
            $designVariants[$designKey]['sleeves'][$sleeveId]['items'][] = $item;
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
            'material_category_id' => 'required|exists:material_categories,id',
            'material_texture_id' => 'required|exists:material_textures,id',
            'shipping_type' => 'required|in:pickup,delivery',
            'notes' => 'nullable|string',
            'total_qty' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'order_image' => 'nullable|image|mimes:jpeg,jpg,png|max:25600',
            'designs' => 'required|array|min:1',
            'designs.*.name' => 'required|string|max:100',
            'designs.*.color' => 'required|string|max:100',
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

        // Custom validation: Check for duplicate design name + color combination
        $designCombinations = [];
        foreach ($request->designs as $index => $design) {
            $combination = strtolower(trim($design['name'])) . '|' . strtolower(trim($design['color']));
            
            if (in_array($combination, $designCombinations)) {
                return back()->withErrors([
                    'designs' => "Duplicate design variant found: '{$design['name']}' with color '{$design['color']}'. Each design name and color combination must be unique."
                ])->withInput();
            }
            
            $designCombinations[] = $combination;
        }

        DB::beginTransaction();

        try {
            // Handle Order Image Upload to PRIVATE storage
            $imagePath = null;
            if ($request->hasFile('order_image')) {
                $image = $request->file('order_image');
                // Compress image if larger than 200KB
                $image = $this->compressImage($image);
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
                // Create design variant with name and color
                $designVariant = DesignVariant::create([
                    'order_id' => $order->id,
                    'design_name' => $designData['name'],
                    'color' => $designData['color'],
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

            return redirect()->route('admin.orders.index', ['date_range' => 'default'])
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
            'productCategories' => Cache::remember('product_categories', 86400, fn() => ProductCategory::orderBy('sort_order', 'asc')->get()),
            'materialCategories' => Cache::remember('material_categories', 86400, fn() => MaterialCategory::orderBy('sort_order', 'asc')->get()),
            'materialTextures' => Cache::remember('material_textures', 86400, fn() => MaterialTexture::orderBy('sort_order', 'asc')->get()),
            'materialSleeves' => Cache::remember('material_sleeves', 86400, fn() => MaterialSleeve::orderBy('id')->get()),
            'materialSizes' => Cache::remember('material_sizes', 86400, fn() => MaterialSize::orderBy('sort_order', 'asc')->get()),
            'services' => Cache::remember('services', 86400, fn() => Service::orderBy('sort_order', 'asc')->get()),
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
            'material_category_id' => 'required|exists:material_categories,id',
            'material_texture_id' => 'required|exists:material_textures,id',
            'shipping_type' => 'required|in:pickup,delivery',
            'notes' => 'nullable|string',
            'total_qty' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'designs' => 'required|array|min:1',
            'designs.*.name' => 'required|string|max:100',
            'designs.*.color' => 'required|string|max:100',
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
                // Compress image if larger than 200KB
                $image = $this->compressImage($image);
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
            
            // Map existing designs by ID for quick lookup
            $existingDesignsMap = $existingDesigns->keyBy('id');
            
            // Track which design IDs are in the new data
            // Filter out null/empty values and convert to integers
            $newDesignIds = collect($request->designs)
                ->pluck('id')
                ->filter(fn($id) => !is_null($id) && $id !== '' && $id !== '0') // Remove null, empty string, and "0"
                ->map(fn($id) => (int)$id)
                ->unique();
            
            Log::info('Order Update - Design Tracking', [
                'order_id' => $order->id,
                'existing_design_ids' => $existingDesigns->pluck('id')->toArray(),
                'new_design_ids' => $newDesignIds->toArray(),
                'designs_data' => collect($request->designs)->map(fn($d) => [
                    'id' => $d['id'] ?? 'NULL',
                    'name' => $d['items'][0]['design_name'] ?? 'N/A'
                ])->toArray()
            ]);
            
            // Delete ONLY design variants that:
            // 1. Don't exist in new data (removed by user)
            foreach ($existingDesigns as $existingDesign) {
                $shouldDelete = !$newDesignIds->contains($existingDesign->id);
                
                if ($shouldDelete) {
                    Log::info('Deleting design variant', [
                        'design_variant_id' => $existingDesign->id,
                        'design_name' => $existingDesign->design_name,
                        'has_work_order' => $existingDesign->workOrder ? 'YES' : 'NO'
                    ]);
                    
                    // Delete order items for this design first
                    OrderItem::where('design_variant_id', $existingDesign->id)->delete();
                    
                    // Delete the design variant (if has work order, will cascade properly)
                    $existingDesign->delete();
                }
            }
            
            // Delete order items for all existing designs that will be updated
            foreach ($newDesignIds as $designId) {
                OrderItem::where('design_variant_id', $designId)->delete();
            }

            // Process each design from request
            foreach ($request->designs as $designData) {
                $designName = $designData['name'];
                $designColor = $designData['color'];
                $designId = !empty($designData['id']) && $designData['id'] !== '0' ? (int)$designData['id'] : null;
                
                // If ID exists and found in existing designs, UPDATE it
                if ($designId && $existingDesignsMap->has($designId)) {
                    // UPDATE existing design variant (preserves work orders via FK)
                    $designVariant = $existingDesignsMap->get($designId);
                    
                    Log::info('Updating design variant', [
                        'design_variant_id' => $designVariant->id,
                        'old_name' => $designVariant->design_name,
                        'new_name' => $designName,
                        'old_color' => $designVariant->color,
                        'new_color' => $designColor,
                        'has_work_order' => $designVariant->workOrder ? 'YES (ID: ' . $designVariant->workOrder->id . ')' : 'NO'
                    ]);
                    
                    $designVariant->update([
                        'design_name' => $designName,
                        'color' => $designColor,
                    ]);
                } else {
                    // CREATE new design variant
                    Log::info('Creating new design variant', [
                        'order_id' => $order->id,
                        'design_name' => $designName,
                        'color' => $designColor
                    ]);
                    
                    $designVariant = DesignVariant::create([
                        'order_id' => $order->id,
                        'design_name' => $designName,
                        'color' => $designColor,
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

            return redirect()->route('admin.orders.index', ['date_range' => 'default'])
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

        return redirect()->route('admin.orders.index', ['date_range' => 'default'])
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
     * Move order to report
     * Create order report record and update order status
     */
    public function moveToReport(Request $request, Order $order)
    {
        // Validate request
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2050',
            'product_type' => 'required|in:t-shirt,makloon,hoodie_polo_jersey,pants',
        ]);

        // Check if order status is WIP or Finished
        if (!in_array($order->production_status, ['wip', 'finished'])) {
            return redirect()->back()
                ->with('message', 'Only WIP or Finished orders can be moved to report.')
                ->with('alert-type', 'warning');
        }

        // Check if order already reported
        if ($order->report_status === 'reported') {
            return redirect()->back()
                ->with('message', 'Order has already been reported.')
                ->with('alert-type', 'warning');
        }

        // Calculate period start and end based on month and year
        $month = (int) $request->month;
        $year = (int) $request->year;
        
        $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        try {
            DB::beginTransaction();

            // Create order report record
            $order->orderReports()->create([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'order_id' => $order->id,
                'invoice_id' => $order->invoice->id,
                'product_type' => $request->product_type,
                'lock_status' => 'draft',
                'note' => null,
            ]);

            // Update order report status and date
            $order->update([
                'report_status' => 'reported',
                'report_date' => now(),
            ]);

            DB::commit();

            return redirect()->back()
                ->with('message', 'Order moved to report successfully.')
                ->with('alert-type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Move to report failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('message', 'Failed to move order to report. Please try again.')
                ->with('alert-type', 'error');
        }
    }

    /**
     * Determine product type based on product category
     */
    private function determineProductType(Order $order): string
    {
        $productName = strtolower($order->productCategory->product_name ?? '');

        if (str_contains($productName, 'kaos') || str_contains($productName, 't-shirt') || str_contains($productName, 'tshirt')) {
            return 't-shirt';
        } elseif (str_contains($productName, 'hoodie') || str_contains($productName, 'polo') || str_contains($productName, 'jersey')) {
            return 'hoodie_polo_jersey';
        } elseif (str_contains($productName, 'celana') || str_contains($productName, 'pants')) {
            return 'pants';
        } else {
            return 'makloon'; // default
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();

        // Clear cache after order deleted
        Cache::forget('order_statistics');

        return redirect()->route('admin.orders.index', ['date_range' => 'default'])
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

        // Generate PDF using DomPDF (simple, no Chrome required)
        $pdf = Pdf::loadView('pages.admin.orders.invoice-pdf', [
            'order' => $order,
            'designVariants' => $designVariants,
            'approvedPayments' => $approvedPayments,
            'totalPaid' => $totalPaid,
        ]);
        
        return $pdf->setPaper('a4', 'portrait')
                   ->download("Invoice-{$order->invoice->invoice_no}.pdf");
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
