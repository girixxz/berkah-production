<?php

namespace App\Http\Controllers;

use App\Models\MaterialTexture;
use Illuminate\Http\Request;

class MaterialTextureController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addTexture', [
            'texture_name' => 'required|max:255|unique:material_textures,texture_name',
        ]);

        MaterialTexture::create($validated);

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

        return redirect()->to(route('owner.manage-data.products.index') . '#material-textures')
            ->with('message', 'Material Texture updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(MaterialTexture $material_texture)
    {
        $material_texture->delete();

        return redirect()->to(route('owner.manage-data.products.index') . '#material-textures')
            ->with('message', 'Material Texture deleted successfully.')
            ->with('alert-type', 'success');
    }
}
