<?php

namespace App\Http\Controllers;

use App\Models\OperationalList;
use Illuminate\Http\Request;

class OperationalListController extends Controller
{
    /**
     * Map category to section ID for scroll-to functionality
     */
    private function getSectionId($category)
    {
        return match($category) {
            'fix_cost_1' => 'fix-cost-1',
            'fix_cost_2' => 'fix-cost-2',
            'printing_supply' => 'printing-supply',
            default => 'fix-cost-1',
        };
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addFixCost', [
                'category' => 'required|in:fix_cost_1,fix_cost_2,printing_supply',
                'list_name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($request) {
                        $exists = OperationalList::where('category', $request->category)
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

            // Auto-generate sort_order: max + 1 per category
            $maxSortOrder = OperationalList::where('category', $validated['category'])
                ->max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            OperationalList::create($validated);

            return back()
                ->with('message', 'Operational list added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', $this->getSectionId($validated['category']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'addFixCost')
                ->withInput()
                ->with('openModal', 'addFixCost')
                ->with('scrollToSection', $this->getSectionId($request->category ?? 'fix_cost_1'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OperationalList $operationalList)
    {
        try {
            // Get total count per category
            $totalCount = OperationalList::where('category', $request->category)->count();
            
            $validated = $request->validateWithBag('editFixCost', [
                'category' => 'required|in:fix_cost_1,fix_cost_2,printing_supply',
                'list_name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($request, $operationalList) {
                        $exists = OperationalList::where('category', $request->category)
                            ->where('list_name', $value)
                            ->where('id', '!=', $operationalList->id)
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
                'sort_order.max' => 'Sort order cannot exceed total items in this category (' . $totalCount . ').',
            ]);

            $oldCategory = $operationalList->category;
            $newCategory = $validated['category'];
            $oldSortOrder = $operationalList->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle category change
            if ($oldCategory !== $newCategory) {
                // Decrement sort_order for items after the old position in old category
                OperationalList::where('category', $oldCategory)
                    ->where('sort_order', '>', $oldSortOrder)
                    ->decrement('sort_order');

                // Get max sort_order in new category and set new item at the end
                $maxInNew = OperationalList::where('category', $newCategory)->max('sort_order') ?? 0;
                $validated['sort_order'] = $maxInNew + 1;
            } elseif ($oldSortOrder !== $newSortOrder) {
                // Handle sort order adjustment within same category
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    OperationalList::where('category', $newCategory)
                        ->where('id', '!=', $operationalList->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    OperationalList::where('category', $newCategory)
                        ->where('id', '!=', $operationalList->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the operational list
            $operationalList->update($validated);

            return back()
                ->with('message', 'Operational list updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', $this->getSectionId($validated['category']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'editFixCost')
                ->withInput()
                ->with('openModal', 'editFixCost')
                ->with('editFixCostId', $operationalList->id)
                ->with('scrollToSection', $this->getSectionId($request->category ?? $operationalList->category));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OperationalList $operationalList)
    {
        $category = $operationalList->category;
        $sortOrder = $operationalList->sort_order;
        
        $operationalList->delete();

        // Decrement sort_order for items after deleted one in same category
        OperationalList::where('category', $category)
            ->where('sort_order', '>', $sortOrder)
            ->decrement('sort_order');

        return back()
            ->with('message', 'Operational list deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', $this->getSectionId($category));
    }
}
