<?php

namespace App\Http\Controllers;

use App\Models\PlasticPacking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlasticPackingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addPlasticPacking', [
                'name' => 'required|max:100|unique:plastic_packings,name',
            ], [
                'name.required' => 'Plastic Packing name is required.',
                'name.max' => 'Plastic Packing name must not exceed 100 characters.',
                'name.unique' => 'This plastic packing name already exists.',
            ]);

            $maxSortOrder = PlasticPacking::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            PlasticPacking::create($validated);

            Cache::forget('plastic_packings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Plastic Packing added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'plastic-packings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addPlasticPacking')
                ->withInput()
                ->with('openModal', 'addPlasticPacking')
                ->with('scrollToSection', 'plastic-packings');
        }
    }

    public function update(Request $request, PlasticPacking $plasticPacking)
    {
        try {
            $totalCount = PlasticPacking::count();
            $validated = $request->validateWithBag('editPlasticPacking', [
                'name' => 'required|max:100|unique:plastic_packings,name,' . $plasticPacking->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Plastic Packing name is required.',
                'name.max' => 'Plastic Packing name must not exceed 100 characters.',
                'name.unique' => 'This plastic packing name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $plasticPacking->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    PlasticPacking::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    PlasticPacking::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $plasticPacking->update(array_filter($validated));

            Cache::forget('plastic_packings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Plastic Packing updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'plastic-packings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editPlasticPacking')
                ->withInput()
                ->with('openModal', 'editPlasticPacking')
                ->with('editPlasticPackingId', $plasticPacking->id)
                ->with('scrollToSection', 'plastic-packings');
        }
    }

    public function destroy(PlasticPacking $plasticPacking)
    {
        $deletedSortOrder = $plasticPacking->sort_order;
        $plasticPacking->delete();

        PlasticPacking::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('plastic_packings');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Plastic Packing deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'plastic-packings');
    }
}
