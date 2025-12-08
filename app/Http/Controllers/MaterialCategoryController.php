<?php

namespace App\Http\Controllers;

use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaterialCategoryController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addMaterial', [
                'material_name' => 'required|max:255|unique:material_categories,material_name',
            ], [
                'material_name.required' => 'Material name is required.',
                'material_name.max' => 'Material name must not exceed 255 characters.',
                'material_name.unique' => 'This material name already exists.',
            ]);

            // Auto-generate sort_order: max + 1
            $maxSortOrder = MaterialCategory::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            MaterialCategory::create($validated);

            // Clear cache after material category created
            Cache::forget('material_categories');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-categories');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'addMaterial')
                ->withInput()
                ->with('openModal', 'addMaterial')
                ->with('scrollToSection', 'material-categories');
        }
    }

    public function update(Request $request, MaterialCategory $materialCategory)
    {
        try {
            // Get total count
            $totalMaterials = MaterialCategory::count();
            
            $validated = $request->validateWithBag('editMaterial', [
                'material_name' => 'required|max:255|unique:material_categories,material_name,' . $materialCategory->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalMaterials,
            ], [
                'material_name.required' => 'Material name is required.',
                'material_name.max' => 'Material name must not exceed 255 characters.',
                'material_name.unique' => 'This material name already exists.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total materials (' . $totalMaterials . ').',
            ]);

            $oldSortOrder = $materialCategory->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    MaterialCategory::where('id', '!=', $materialCategory->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    MaterialCategory::where('id', '!=', $materialCategory->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the material
            $materialCategory->update($validated);

            // Clear cache after material category updated
            Cache::forget('material_categories');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-categories');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'editMaterial')
                ->withInput()
                ->with('openModal', 'editMaterial')
                ->with('editMaterialId', $materialCategory->id)
                ->with('scrollToSection', 'material-categories');
        }
    }

    public function destroy(MaterialCategory $materialCategory)
    {
        $deletedSortOrder = $materialCategory->sort_order;
        
        // Delete the material
        $materialCategory->delete();

        // Reorder: decrement sort_order for all items after deleted item
        MaterialCategory::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        // Clear cache after material category deleted
        Cache::forget('material_categories');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Material Category deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-categories');
    }
}
