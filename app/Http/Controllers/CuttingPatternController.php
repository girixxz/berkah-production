<?php

namespace App\Http\Controllers;

use App\Models\CuttingPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CuttingPatternController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addCuttingPattern', [
                'name' => 'required|max:100|unique:cutting_patterns,name',
            ], [
                'name.required' => 'Cutting Pattern name is required.',
                'name.max' => 'Cutting Pattern name must not exceed 100 characters.',
                'name.unique' => 'This cutting pattern name already exists.',
            ]);

            $maxSortOrder = CuttingPattern::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            CuttingPattern::create($validated);

            Cache::forget('cutting_patterns');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Cutting Pattern added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'cutting-patterns');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addCuttingPattern')
                ->withInput()
                ->with('openModal', 'addCuttingPattern')
                ->with('scrollToSection', 'cutting-patterns');
        }
    }

    public function update(Request $request, CuttingPattern $cuttingPattern)
    {
        try {
            $totalCount = CuttingPattern::count();
            $validated = $request->validateWithBag('editCuttingPattern', [
                'name' => 'required|max:100|unique:cutting_patterns,name,' . $cuttingPattern->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Cutting Pattern name is required.',
                'name.max' => 'Cutting Pattern name must not exceed 100 characters.',
                'name.unique' => 'This cutting pattern name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $cuttingPattern->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    CuttingPattern::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    CuttingPattern::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $cuttingPattern->update(array_filter($validated));

            Cache::forget('cutting_patterns');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Cutting Pattern updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'cutting-patterns');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editCuttingPattern')
                ->withInput()
                ->with('openModal', 'editCuttingPattern')
                ->with('editCuttingPatternId', $cuttingPattern->id)
                ->with('scrollToSection', 'cutting-patterns');
        }
    }

    public function destroy(CuttingPattern $cuttingPattern)
    {
        $deletedSortOrder = $cuttingPattern->sort_order;
        $cuttingPattern->delete();

        CuttingPattern::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('cutting_patterns');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Cutting Pattern deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'cutting-patterns');
    }
}
