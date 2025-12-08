<?php

namespace App\Http\Controllers;

use App\Models\MaterialTexture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaterialTextureController extends Controller
{

    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addTexture', [
                'texture_name' => 'required|max:255|unique:material_textures,texture_name',
            ], [
                'texture_name.required' => 'Texture name is required.',
                'texture_name.max' => 'Texture name must not exceed 255 characters.',
                'texture_name.unique' => 'This texture name already exists.',
            ]);

            // Auto-generate sort_order: max + 1
            $maxSortOrder = MaterialTexture::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            MaterialTexture::create($validated);

            // Clear cache
            Cache::forget('material_textures');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material Texture added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-textures');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'addTexture')
                ->withInput()
                ->with('openModal', 'addTexture')
                ->with('scrollToSection', 'material-textures');
        }
    }

    public function update(Request $request, MaterialTexture $material_texture)
    {
        try {
            // Get total count
            $totalTextures = MaterialTexture::count();
            
            $validated = $request->validateWithBag('editTexture', [
                'texture_name' => 'required|max:255|unique:material_textures,texture_name,' . $material_texture->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalTextures,
            ], [
                'texture_name.required' => 'Texture name is required.',
                'texture_name.max' => 'Texture name must not exceed 255 characters.',
                'texture_name.unique' => 'This texture name already exists.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total textures (' . $totalTextures . ').',
            ]);

            $oldSortOrder = $material_texture->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    MaterialTexture::where('id', '!=', $material_texture->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    MaterialTexture::where('id', '!=', $material_texture->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the texture
            $material_texture->update($validated);

            // Clear cache
            Cache::forget('material_textures');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material Texture updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-textures');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'editTexture')
                ->withInput()
                ->with('openModal', 'editTexture')
                ->with('editTextureId', $material_texture->id)
                ->with('scrollToSection', 'material-textures');
        }
    }

    public function destroy(MaterialTexture $material_texture)
    {
        $deletedSortOrder = $material_texture->sort_order;
        
        // Delete the texture
        $material_texture->delete();

        // Reorder: decrement sort_order for all items after deleted item
        MaterialTexture::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        // Clear cache
        Cache::forget('material_textures');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Material Texture deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-textures');
    }
}
