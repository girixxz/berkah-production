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
            ], [
                'sleeve_name.required' => 'Sleeve name is required.',
                'sleeve_name.max' => 'Sleeve name must not exceed 100 characters.',
                'sleeve_name.unique' => 'This sleeve name already exists.',
            ]);

            // Auto-generate sort_order: max + 1
            $maxSortOrder = MaterialSleeve::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

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
            // Get total count
            $totalSleeves = MaterialSleeve::count();
            
            $validated = $request->validateWithBag('editSleeve', [
                'sleeve_name' => 'required|string|max:100|unique:material_sleeves,sleeve_name,' . $materialSleeve->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalSleeves,
            ], [
                'sleeve_name.required' => 'Sleeve name is required.',
                'sleeve_name.max' => 'Sleeve name must not exceed 100 characters.',
                'sleeve_name.unique' => 'This sleeve name already exists.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total sleeves (' . $totalSleeves . ').',
            ]);

            $oldSortOrder = $materialSleeve->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    MaterialSleeve::where('id', '!=', $materialSleeve->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    MaterialSleeve::where('id', '!=', $materialSleeve->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the sleeve
            $materialSleeve->update($validated);

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
        $deletedSortOrder = $materialSleeve->sort_order;
        
        // Delete the sleeve
        $materialSleeve->delete();

        // Reorder: decrement sort_order for all items after deleted item
        MaterialSleeve::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        // Clear cache
        Cache::forget('material_sleeves');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Material Sleeve deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'material-sleeves');
    }
}
