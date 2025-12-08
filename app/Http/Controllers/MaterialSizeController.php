<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialSize;
use Illuminate\Support\Facades\Cache;

class MaterialSizeController extends Controller
{

    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSize', [
                'size_name' => 'required|max:255|unique:material_sizes,size_name',
                'extra_price' => 'required|numeric|min:0',
            ], [
                'size_name.required' => 'Size name is required.',
                'size_name.max' => 'Size name must not exceed 255 characters.',
                'size_name.unique' => 'This size name already exists.',
                'extra_price.required' => 'Extra price is required.',
                'extra_price.numeric' => 'Extra price must be a number.',
                'extra_price.min' => 'Extra price must be at least 0.',
            ]);

            // Auto-generate sort_order: max + 1
            $maxSortOrder = MaterialSize::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            MaterialSize::create($validated);

            // Clear cache
            Cache::forget('material_sizes');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material Size added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-sizes');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'addSize')
                ->withInput()
                ->with('openModal', 'addSize')
                ->with('scrollToSection', 'material-sizes');
        }
    }

    public function update(Request $request, MaterialSize $materialSize)
    {
        try {
            // Get total count of sizes
            $totalSizes = MaterialSize::count();
            
            $validated = $request->validateWithBag('editSize', [
                'size_name' => 'required|max:255|unique:material_sizes,size_name,' . $materialSize->id,
                'extra_price' => 'required|numeric|min:0',
                'sort_order' => 'required|integer|min:1|max:' . $totalSizes,
            ], [
                'size_name.required' => 'Size name is required.',
                'size_name.max' => 'Size name must not exceed 255 characters.',
                'size_name.unique' => 'This size name already exists.',
                'extra_price.required' => 'Extra price is required.',
                'extra_price.numeric' => 'Extra price must be a number.',
                'extra_price.min' => 'Extra price must be at least 0.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total sizes (' . $totalSizes . ').',
            ]);

            $oldSortOrder = $materialSize->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment automatically
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP (ke posisi lebih kecil): 5 → 2
                    // Semua size dengan sort_order >= newSortOrder dan < oldSortOrder akan +1
                    MaterialSize::where('id', '!=', $materialSize->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN (ke posisi lebih besar): 2 → 5
                    // Semua size dengan sort_order > oldSortOrder dan <= newSortOrder akan -1
                    MaterialSize::where('id', '!=', $materialSize->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the main size
            $materialSize->update($validated);

            // Clear cache
            Cache::forget('material_sizes');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material Size updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-sizes');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'editSize')
                ->withInput()
                ->with('openModal', 'editSize')
                ->with('editSizeId', $materialSize->id)
                ->with('scrollToSection', 'material-sizes');
        }
    }

    public function destroy(MaterialSize $materialSize)
    {
        $deletedSortOrder = $materialSize->sort_order;
        
        // Delete the size
        $materialSize->delete();

        // Reorder: decrement sort_order for all items that were after the deleted item
        MaterialSize::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        // Clear cache
        Cache::forget('material_sizes');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Material Size deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-sizes');
    }
}
