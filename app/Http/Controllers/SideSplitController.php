<?php

namespace App\Http\Controllers;

use App\Models\SideSplit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SideSplitController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSideSplit', [
                'name' => 'required|max:100|unique:side_splits,name',
            ], [
                'name.required' => 'Side Split name is required.',
                'name.max' => 'Side Split name must not exceed 100 characters.',
                'name.unique' => 'This side split name already exists.',
            ]);

            $maxSortOrder = SideSplit::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            SideSplit::create($validated);

            Cache::forget('side_splits');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Side Split added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'side-splits');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addSideSplit')
                ->withInput()
                ->with('openModal', 'addSideSplit')
                ->with('scrollToSection', 'side-splits');
        }
    }

    public function update(Request $request, SideSplit $sideSplit)
    {
        try {
            $totalCount = SideSplit::count();
            $validated = $request->validateWithBag('editSideSplit', [
                'name' => 'required|max:100|unique:side_splits,name,' . $sideSplit->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Side Split name is required.',
                'name.max' => 'Side Split name must not exceed 100 characters.',
                'name.unique' => 'This side split name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $sideSplit->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    SideSplit::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    SideSplit::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $sideSplit->update(array_filter($validated));

            Cache::forget('side_splits');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Side Split updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'side-splits');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editSideSplit')
                ->withInput()
                ->with('openModal', 'editSideSplit')
                ->with('editSideSplitId', $sideSplit->id)
                ->with('scrollToSection', 'side-splits');
        }
    }

    public function destroy(SideSplit $sideSplit)
    {
        $deletedSortOrder = $sideSplit->sort_order;
        $sideSplit->delete();

        SideSplit::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('side_splits');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Side Split deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'side-splits');
    }
}
