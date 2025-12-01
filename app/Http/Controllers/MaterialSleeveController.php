<?php

namespace App\Http\Controllers;

use App\Models\MaterialSleeve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaterialSleeveController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSleeve', [
                'sleeve_name' => 'required|string|max:100|unique:material_sleeves,sleeve_name',
            ]);

            MaterialSleeve::create($validated);

            // Clear cache
            Cache::forget('material_sleeves');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material Sleeve added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-sleeves');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'addSleeve')
                ->withInput()
                ->with('openModal', 'addSleeve')
                ->with('scrollToSection', 'material-sleeves');
        }
    }

    public function update(Request $request, MaterialSleeve $materialSleeve)
    {
        try {
            $validated = $request->validateWithBag('editSleeve', [
                'sleeve_name' => 'required|string|max:100|unique:material_sleeves,sleeve_name,' . $materialSleeve->id,
            ]);

            $materialSleeve->update(array_filter($validated));

            // Clear cache
            Cache::forget('material_sleeves');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Material Sleeve updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'material-sleeves');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'editSleeve')
                ->withInput()
                ->with('openModal', 'editSleeve')
                ->with('editSleeveId', $materialSleeve->id)
                ->with('scrollToSection', 'material-sleeves');
        }
    }

    public function destroy(MaterialSleeve $materialSleeve)
    {
        $materialSleeve->delete();

        // Clear cache
        Cache::forget('material_sleeves');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Material Sleeve deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-sleeves');
    }
}
