<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    /**
     * Display a listing of sales
     */
    public function index(Request $request)
    {
        $sales = Sale::orderBy('created_at', 'desc')->paginate(10);

        // Handle AJAX request for pagination
        if ($request->ajax()) {
            return view('pages.owner.manage-data.sales', compact('sales'));
        }

        return view('pages.owner.manage-data.sales', compact('sales'));
    }

    /**
     * Store a newly created sale
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:100',
        ], [], [
            'sales_name' => 'Sales Name',
            'phone' => 'Phone',
        ]);

        Sale::create($validated);

        return redirect()->route('owner.manage-data.sales.index')
            ->with('message', 'Sales created successfully')
            ->with('alert-type', 'success');
    }

    /**
     * Update the specified sale
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'sales_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:100',
        ], [], [
            'sales_name' => 'Sales Name',
            'phone' => 'Phone',
        ]);

        $sale->update($validated);

        return redirect()->route('owner.manage-data.sales.index')
            ->with('message', 'Sales updated successfully')
            ->with('alert-type', 'success');
    }

    /**
     * Remove the specified sale
     */
    public function destroy(Sale $sale)
    {
        $sale->delete();

        return redirect()->route('owner.manage-data.sales.index')
            ->with('message', 'Sales deleted successfully')
            ->with('alert-type', 'success');
    }
}
