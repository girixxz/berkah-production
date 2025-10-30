<?php

namespace App\Http\Controllers;

use App\Models\MaterialSleeve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaterialSleeveController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addSleeve', [
            'sleeve_name' => 'required|string|max:100|unique:material_sleeves,sleeve_name',
        ]);

        MaterialSleeve::create($validated);

        // Clear cache
        Cache::forget('material_sleeves');

        return redirect()
            ->to(url()->previous() . '#material-sleeves')
            ->with('message', 'Material Sleeve added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, MaterialSleeve $materialSleeve)
    {
        $validated = $request->validateWithBag('editSleeve', [
            'sleeve_name' => 'required|string|max:100|unique:material_sleeves,sleeve_name,' . $materialSleeve->id,
        ]);

        $materialSleeve->update(array_filter($validated));

        // Clear cache
        Cache::forget('material_sleeves');

        return redirect()->to(route('owner.manage-data.products.index') . '#material-sleeves')
            ->with('message', 'Material Sleeve updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(MaterialSleeve $materialSleeve)
    {
        $materialSleeve->delete();

        // Clear cache
        Cache::forget('material_sleeves');

        return redirect()
            ->to(url()->previous() . '#material-sleeves')
            ->with('message', 'Material Sleeve deleted successfully.')
            ->with('alert-type', 'success');
    }
}
