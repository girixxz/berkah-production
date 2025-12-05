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
            $validated = $request->validateWithBag('editMaterial', [
                'material_name' => 'required|max:255|unique:material_categories,material_name,' . $materialCategory->id,
            ], [
                'material_name.required' => 'Material name is required.',
                'material_name.max' => 'Material name must not exceed 255 characters.',
                'material_name.unique' => 'This material name already exists.',
            ]);

            $materialCategory->update(array_filter($validated));

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
        $materialCategory->delete();

        // Clear cache after material category deleted
        Cache::forget('material_categories');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Material Category deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-categories');
    }
}
