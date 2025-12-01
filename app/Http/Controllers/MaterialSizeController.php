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
            ]);

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
            $validated = $request->validateWithBag('editSize', [
                'size_name' => 'required|max:255|unique:material_sizes,size_name,' . $materialSize->id,
                'extra_price' => 'required|numeric|min:0',
            ]);

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
        $materialSize->delete();

        // Clear cache
        Cache::forget('material_sizes');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Material Size deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-sizes');
    }
}
