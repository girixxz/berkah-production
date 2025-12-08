<?php

namespace App\Http\Controllers;

use App\Models\RibSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RibSizeController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addRibSize', [
                'name' => 'required|max:100|unique:rib_sizes,name',
            ], [
                'name.required' => 'Rib Size name is required.',
                'name.max' => 'Rib Size name must not exceed 100 characters.',
                'name.unique' => 'This rib size name already exists.',
            ]);

            $maxSortOrder = RibSize::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            RibSize::create($validated);

            Cache::forget('rib_sizes');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Rib Size added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'rib-sizes');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addRibSize')
                ->withInput()
                ->with('openModal', 'addRibSize')
                ->with('scrollToSection', 'rib-sizes');
        }
    }

    public function update(Request $request, RibSize $ribSize)
    {
        try {
            $totalCount = RibSize::count();
            $validated = $request->validateWithBag('editRibSize', [
                'name' => 'required|max:100|unique:rib_sizes,name,' . $ribSize->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Rib Size name is required.',
                'name.max' => 'Rib Size name must not exceed 100 characters.',
                'name.unique' => 'This rib size name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $ribSize->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    RibSize::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    RibSize::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $ribSize->update(array_filter($validated));

            Cache::forget('rib_sizes');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Rib Size updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'rib-sizes');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editRibSize')
                ->withInput()
                ->with('openModal', 'editRibSize')
                ->with('editRibSizeId', $ribSize->id)
                ->with('scrollToSection', 'rib-sizes');
        }
    }

    public function destroy(RibSize $ribSize)
    {
        $deletedSortOrder = $ribSize->sort_order;
        $ribSize->delete();

        RibSize::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('rib_sizes');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Rib Size deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'rib-sizes');
    }
}
