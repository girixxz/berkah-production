<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        // Per page validation
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25]) ? $perPage : 15;

        $customers = Customer::with(['orders'])
            ->when($search, function ($query, $search) {
                return $query->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->withCount('orders')
            ->withSum('orders', 'total_qty')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'customer_page');

        // Get all customers with same filters for client-side search
        $allCustomers = Customer::with(['orders'])
            ->when($search, function ($query, $search) {
                return $query->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->withCount('orders')
            ->withSum('orders', 'total_qty')
            ->orderBy('id', 'desc')
            ->get();

        // No longer passing provinces - will be loaded via AJAX
        return view('pages.admin.customers', compact('customers', 'allCustomers'));
    }

    /**
     * Display the specified resource (Customer Detail Page).
     */
    public function show(Customer $customer, Request $request)
    {
        // Per page validation
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [5, 10, 15, 20, 25]) ? $perPage : 15;
        
        // Base query for customer's orders
        $query = $customer->orders()
            ->with([
                'productCategory',
                'materialCategory',
                'materialTexture',
                'invoice',
                'designVariants',
                'orderStages'
            ])
            ->whereIn('production_status', ['wip', 'finished']);

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('productCategory', function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%");
                })
                ->orWhereHas('invoice', function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Apply date filter - Default to This Month
        $startDate = null;
        $endDate = null;
        $dateRange = $request->get('date_range', 'this_month');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
        } elseif (!$request->filled('start_date') && !$request->filled('end_date')) {
            $startDate = \Carbon\Carbon::now()->startOfMonth();
            $endDate = \Carbon\Carbon::now()->endOfMonth();
        }
        
        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        // Apply sorting - WIP by wip_date, others by order_date
        $query->orderBy('wip_date', 'desc');

        // Get orders with pagination
        $orders = $query->paginate($perPage);

        // Get all orders with same filters for client-side search
        $allOrdersQuery = $customer->orders()
            ->with([
                'productCategory',
                'materialCategory',
                'materialTexture',
                'invoice',
                'designVariants',
                'orderStages'
            ])
            ->whereIn('production_status', ['wip', 'finished']);

        // Apply same search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $allOrdersQuery->where(function ($q) use ($search) {
                $q->whereHas('productCategory', function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%");
                })
                ->orWhereHas('invoice', function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Apply same date filter
        if ($startDate && $endDate) {
            $allOrdersQuery->whereBetween('order_date', [$startDate, $endDate]);
        }

        // Apply same sorting
        $allOrdersQuery->orderBy('wip_date', 'desc');

        $allOrders = $allOrdersQuery->get();

        return view('pages.admin.customers.show', compact('customer', 'orders', 'allOrders', 'startDate', 'endDate', 'dateRange'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addCustomer', [
                'customer_name' => 'required|string|max:100',
                'phone' => 'required|string|max:20',
                'province_id' => 'required|string',
                'city_id' => 'required|string',
                'district_id' => 'required|string',
                'village_id' => 'required|string',
                'address' => 'required|string|max:255',
            ], [
                'customer_name.required' => 'Customer name is required.',
                'phone.required' => 'Phone number is required.',
                'province_id.required' => 'Province is required.',
                'city_id.required' => 'City is required.',
                'district_id.required' => 'District is required.',
                'village_id.required' => 'Village is required.',
                'address.required' => 'Address is required.',
            ]);

            $customer = Customer::create($validated);

            // Check if request is from create order page
            if ($request->has('from_create_order') && $request->from_create_order == '1') {
                return redirect()->route('admin.orders.create')
                    ->with('message', 'Customer added successfully.')
                    ->with('alert-type', 'success')
                    ->with('select_customer_id', $customer->id);
            }

            // Success - modal will close automatically (no session flash on success)
            return redirect()->route('admin.customers.index')
                ->with('message', 'Customer added successfully.')
                ->with('alert-type', 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error - flash openModal biar modal tetap terbuka
            session()->flash('openModal', 'addCustomer');
            throw $e;
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        try {
            $validated = $request->validateWithBag('editCustomer', [
                'customer_name' => 'required|string|max:100',
                'phone' => 'required|string|max:20',
                'province_id' => 'required|string',
                'city_id' => 'required|string',
                'district_id' => 'required|string',
                'village_id' => 'required|string',
                'address' => 'required|string|max:255',
            ], [
                'customer_name.required' => 'Customer name is required.',
                'phone.required' => 'Phone number is required.',
                'province_id.required' => 'Province is required.',
                'city_id.required' => 'City is required.',
                'district_id.required' => 'District is required.',
                'village_id.required' => 'Village is required.',
                'address.required' => 'Address is required.',
            ]);

            $customer->update($validated);

            // Success - modal will close automatically (no session flash on success)
            return redirect()->route('admin.customers.index')
                ->with('message', 'Customer updated successfully.')
                ->with('alert-type', 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error - flash openModal dan customerId biar modal tetap terbuka
            session()->flash('openModal', 'editCustomer');
            session()->flash('editCustomerId', $customer->id);
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('message', 'Customer deleted successfully.')
            ->with('alert-type', 'success');
    }

    /**
     * Get provinces from API (for AJAX) - with caching
     */
    public function getProvinces()
    {
        try {
            // Cache for 24 hours (86400 seconds)
            $provinces = cache()->remember('api_provinces', 86400, function () {
                $response = Http::timeout(10)->get("https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return collect($data)->map(function ($item) {
                        return [
                            'id' => $item['id'],
                            'province_name' => $item['name']
                        ];
                    });
                }

                return collect([]);
            });

            return response()->json($provinces);
        } catch (\Exception $e) {
            Log::error('Error fetching provinces from API: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get cities by province (for AJAX) - with caching
     */
    public function getCities($provinceId)
    {
        try {
            // Cache for 24 hours per province
            $cities = cache()->remember("api_cities_{$provinceId}", 86400, function () use ($provinceId) {
                $response = Http::timeout(10)->get("https://www.emsifa.com/api-wilayah-indonesia/api/regencies/{$provinceId}.json");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return collect($data)->map(function ($item) {
                        return [
                            'id' => $item['id'],
                            'city_name' => $item['name']
                        ];
                    });
                }

                return collect([]);
            });

            return response()->json($cities);
        } catch (\Exception $e) {
            Log::error('Error fetching cities from API: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get districts by city (for AJAX) - with caching
     */
    public function getDistricts($cityId)
    {
        try {
            // Cache for 24 hours per city
            $districts = cache()->remember("api_districts_{$cityId}", 86400, function () use ($cityId) {
                $response = Http::timeout(10)->get("https://www.emsifa.com/api-wilayah-indonesia/api/districts/{$cityId}.json");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return collect($data)->map(function ($item) {
                        return [
                            'id' => $item['id'],
                            'district_name' => $item['name']
                        ];
                    });
                }

                return collect([]);
            });

            return response()->json($districts);
        } catch (\Exception $e) {
            Log::error('Error fetching districts from API: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get villages by district (for AJAX) - with caching
     */
    public function getVillages($districtId)
    {
        try {
            // Cache for 24 hours per district
            $villages = cache()->remember("api_villages_{$districtId}", 86400, function () use ($districtId) {
                $response = Http::timeout(10)->get("https://www.emsifa.com/api-wilayah-indonesia/api/villages/{$districtId}.json");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return collect($data)->map(function ($item) {
                        return [
                            'id' => $item['id'],
                            'village_name' => $item['name']
                        ];
                    });
                }

                return collect([]);
            });

            return response()->json($villages);
        } catch (\Exception $e) {
            Log::error('Error fetching villages from API: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}
