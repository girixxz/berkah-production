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

        $customers = Customer::with(['orders'])
            ->when($search, function ($query, $search) {
                return $query->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->withCount('orders')
            ->withSum('orders', 'total_qty')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'customer_page');

        // Fetch provinces from API
        $provinces = $this->getProvinces();

        return view('pages.admin.customers', compact('customers', 'provinces'));
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
     * Get cities by province (for AJAX)
     */
    public function getCities($provinceId)
    {
        try {
            $response = Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/regencies/{$provinceId}.json");

            if ($response->successful()) {
                $data = $response->json();
                
                // Transform to match expected format
                $cities = collect($data)->map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'city_name' => $item['name']
                    ];
                });

                return response()->json($cities);
            }

            return response()->json([]);
        } catch (\Exception $e) {
            Log::error('Error fetching cities from API: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get districts by city (for AJAX)
     */
    public function getDistricts($cityId)
    {
        try {
            $response = Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/districts/{$cityId}.json");

            if ($response->successful()) {
                $data = $response->json();
                
                // Transform to match expected format
                $districts = collect($data)->map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'district_name' => $item['name']
                    ];
                });

                return response()->json($districts);
            }

            return response()->json([]);
        } catch (\Exception $e) {
            Log::error('Error fetching districts from API: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get villages by district (for AJAX)
     */
    public function getVillages($districtId)
    {
        try {
            $response = Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/villages/{$districtId}.json");

            if ($response->successful()) {
                $data = $response->json();
                
                // Transform to match expected format
                $villages = collect($data)->map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'village_name' => $item['name']
                    ];
                });

                return response()->json($villages);
            }

            return response()->json([]);
        } catch (\Exception $e) {
            Log::error('Error fetching villages from API: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get provinces from API
     */
    private function getProvinces()
    {
        try {
            $response = Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json");

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
            Log::error('Error fetching provinces from API: ' . $e->getMessage());
            return collect([]);
        }
    }
}
