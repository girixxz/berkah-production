<?php

namespace App\Http\Controllers;

use App\Models\MaterialTexture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaterialTextureController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addTexture', [
            'texture_name' => 'required|max:255|unique:material_textures,texture_name',
        ]);

        MaterialTexture::create($validated);

        // Clear cache
        Cache::forget('material_textures');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-textures')
            ->with('message', 'Material Texture added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, MaterialTexture $material_texture)
    {
        $validated = $request->validateWithBag('editTexture', [
            'texture_name' => 'required|max:255|unique:material_textures,texture_name,' . $material_texture->id,
        ]);

        $material_texture->update(array_filter($validated));

        // Clear cache
        Cache::forget('material_textures');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-textures')
            ->with('message', 'Material Texture updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(MaterialTexture $material_texture)
    {
        $material_texture->delete();

        // Clear cache
        Cache::forget('material_textures');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-textures')
            ->with('message', 'Material Texture deleted successfully.')
            ->with('alert-type', 'success');
    }
}
