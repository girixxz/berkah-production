<?php

namespace App\Http\Controllers;

use App\Models\MaterialSupplier;
use Illuminate\Http\Request;

class MaterialSupplierController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSupplier', [
                'supplier_name' => 'required|string|max:100|unique:material_suppliers,supplier_name',
                'notes' => 'nullable|string',
            ], [
                'supplier_name.required' => 'Supplier name is required.',
                'supplier_name.max' => 'Supplier name must not exceed 100 characters.',
                'supplier_name.unique' => 'This supplier name already exists.',
            ]);

            MaterialSupplier::create($validated);

            return back()
                ->with('message', 'Material supplier added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-suppliers');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'addSupplier')
                ->withInput()
                ->with('openModal', 'addSupplier')
                ->with('scrollToSection', 'material-suppliers');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaterialSupplier $materialSupplier)
    {
        try {
            $validated = $request->validateWithBag('editSupplier', [
                'supplier_name' => 'required|string|max:100|unique:material_suppliers,supplier_name,' . $materialSupplier->id,
                'notes' => 'nullable|string',
            ], [
                'supplier_name.required' => 'Supplier name is required.',
                'supplier_name.max' => 'Supplier name must not exceed 100 characters.',
                'supplier_name.unique' => 'This supplier name already exists.',
            ]);

            $materialSupplier->update($validated);

            return back()
                ->with('message', 'Material supplier updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-suppliers');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'editSupplier')
                ->withInput()
                ->with('openModal', 'editSupplier')
                ->with('editSupplierId', $materialSupplier->id)
                ->with('scrollToSection', 'material-suppliers');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialSupplier $materialSupplier)
    {
        $materialSupplier->delete();

        return back()
            ->with('message', 'Material supplier deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-suppliers');
    }
}
