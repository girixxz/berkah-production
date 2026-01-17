<?php

namespace App\Http\Controllers;

use App\Models\FixCostList;
use Illuminate\Http\Request;

class FixCostListController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addFixCost', [
                'category' => 'required|in:fix_cost_1,fix_cost_2,screening',
                'list_name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($request) {
                        $exists = FixCostList::where('category', $request->category)
                            ->where('list_name', $value)
                            ->exists();
                        if ($exists) {
                            $fail('This list name already exists in the selected category.');
                        }
                    },
                ],
            ], [
                'category.required' => 'Category is required.',
                'category.in' => 'Invalid category selected.',
                'list_name.required' => 'List name is required.',
                'list_name.max' => 'List name must not exceed 100 characters.',
            ]);

            // Auto-generate sort_order: max + 1 (global, not per category)
            $maxSortOrder = FixCostList::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            FixCostList::create($validated);

            return back()
                ->with('message', 'Fix cost list added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'fix-cost-lists');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'addFixCost')
                ->withInput()
                ->with('openModal', 'addFixCost')
                ->with('scrollToSection', 'fix-cost-lists');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FixCostList $fixCostList)
    {
        try {
            // Get total count (global)
            $totalCount = FixCostList::count();
            
            $validated = $request->validateWithBag('editFixCost', [
                'category' => 'required|in:fix_cost_1,fix_cost_2,screening',
                'list_name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($request, $fixCostList) {
                        $exists = FixCostList::where('category', $request->category)
                            ->where('list_name', $value)
                            ->where('id', '!=', $fixCostList->id)
                            ->exists();
                        if ($exists) {
                            $fail('This list name already exists in the selected category.');
                        }
                    },
                ],
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'category.required' => 'Category is required.',
                'category.in' => 'Invalid category selected.',
                'list_name.required' => 'List name is required.',
                'list_name.max' => 'List name must not exceed 100 characters.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total items (' . $totalCount . ').',
            ]);

            $oldSortOrder = $fixCostList->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment (global, regardless of category)
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    FixCostList::where('id', '!=', $fixCostList->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    FixCostList::where('id', '!=', $fixCostList->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the fix cost list
            $fixCostList->update($validated);

            return back()
                ->with('message', 'Fix cost list updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'fix-cost-lists');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'editFixCost')
                ->withInput()
                ->with('openModal', 'editFixCost')
                ->with('editFixCostId', $fixCostList->id)
                ->with('scrollToSection', 'fix-cost-lists');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FixCostList $fixCostList)
    {
        $sortOrder = $fixCostList->sort_order;
        
        $fixCostList->delete();

        // Decrement sort_order for items after deleted one (global)
        FixCostList::where('sort_order', '>', $sortOrder)
            ->decrement('sort_order');

        return back()
            ->with('message', 'Fix cost list deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'fix-cost-lists');
    }
}
