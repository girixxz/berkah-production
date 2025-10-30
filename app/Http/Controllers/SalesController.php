<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SalesController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSales', [
                'sales_name' => 'required|max:100|unique:sales,sales_name',
                'phone' => 'nullable|max:100',
            ]);

            Sale::create($validated);

            // Clear cache
            Cache::forget('sales_list');

            // Success - jangan flash openModal, biar modal tertutup
            return redirect(route('owner.manage-data.users-sales.index') . '#sales')
                ->with('message', 'Sales added successfully.')
                ->with('alert-type', 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error - flash openModal biar modal tetap terbuka
            session()->flash('openModal', 'addSales');
            throw $e;
        }
    }

    public function update(Request $request, Sale $sale)
    {
        try {
            $validated = $request->validateWithBag('editSales', [
                'sales_name' => 'required|max:100|unique:sales,sales_name,' . $sale->id,
                'phone' => 'nullable|max:100',
            ]);

            $sale->update($validated);

            // Clear cache
            Cache::forget('sales_list');

            // Success - jangan flash openModal, biar modal tertutup
            return redirect(route('owner.manage-data.users-sales.index') . '#sales')
                ->with('message', 'Sales updated successfully.')
                ->with('alert-type', 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error - flash openModal dan salesId biar modal tetap terbuka
            session()->flash('openModal', 'editSales');
            session()->flash('editSalesId', $sale->id);
            throw $e;
        }
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();

        // Clear cache
        Cache::forget('sales_list');

        return redirect()->route('owner.manage-data.users-sales.index')
            ->with('message', 'Sales deleted successfully.')
            ->with('alert-type', 'success');
    }
}
