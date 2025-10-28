<?php

namespace App\Http\Controllers;

use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaterialCategoryController extends Controller
{
    public function store(Request $request)
    {
        // simpan state modal
        session()->flash('openModal', 'addMaterial');

        $validated = $request->validateWithBag('addMaterial', [
            'material_name' => 'required|max:255|unique:material_categories,material_name',
        ]);

        MaterialCategory::create($validated);

        // Clear cache after material category created
        Cache::forget('material_categories');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-categories')
            ->with('message', 'Material added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, MaterialCategory $materialCategory)
    {
        // supaya modal edit tetap kebuka kalau error
        session()->flash('openModal', 'editMaterial');
        session()->flash('editMaterialId', $materialCategory->id);

        $validated = $request->validateWithBag('editMaterial', [
            'material_name' => 'required|max:255|unique:material_categories,material_name,' . $materialCategory->id,
        ]);

        $materialCategory->update(array_filter($validated));

        // Clear cache after material category updated
        Cache::forget('material_categories');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-categories')
            ->with('message', 'Material updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(MaterialCategory $materialCategory)
    {
        $materialCategory->delete();

        // Clear cache after material category deleted
        Cache::forget('material_categories');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-categories')
            ->with('message', 'Material Category deleted successfully.')
            ->with('alert-type', 'success');
    }
}
