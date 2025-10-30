<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialSize;
use Illuminate\Support\Facades\Cache;

class MaterialSizeController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addSize', [
            'size_name' => 'required|max:255|unique:material_sizes,size_name',
            'extra_price' => 'required|numeric|min:0',
        ]);

        MaterialSize::create($validated);

        // Clear cache
        Cache::forget('material_sizes');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-sizes')
            ->with('message', 'Material Size added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, MaterialSize $materialSize)
    {
        $validated = $request->validateWithBag('editSize', [
            'size_name' => 'required|max:255|unique:material_sizes,size_name,' . $materialSize->id,
            'extra_price' => 'required|numeric|min:0',
        ]);

        $materialSize->update($validated);

        // Clear cache
        Cache::forget('material_sizes');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-sizes')
            ->with('message', 'Material Size updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(MaterialSize $materialSize)
    {
        $materialSize->delete();

        // Clear cache
        Cache::forget('material_sizes');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-sizes')
            ->with('message', 'Material Size deleted successfully.')
            ->with('alert-type', 'success');
    }
}
