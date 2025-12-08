<?php

namespace App\Http\Controllers;

use App\Models\NeckOverdeck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NeckOverdeckController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addNeckOverdeck', [
                'name' => 'required|max:100|unique:neck_overdecks,name',
            ], [
                'name.required' => 'NeckOverdeck name is required.',
                'name.max' => 'NeckOverdeck name must not exceed 100 characters.',
                'name.unique' => 'This neck overdeck name already exists.',
            ]);

            $maxSortOrder = NeckOverdeck::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            NeckOverdeck::create($validated);

            Cache::forget('neck_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Neck Overdeck added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'neck-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addNeckOverdeck')
                ->withInput()
                ->with('openModal', 'addNeckOverdeck')
                ->with('scrollToSection', 'neck-overdecks');
        }
    }

    public function update(Request $request, NeckOverdeck $neckOverdeck)
    {
        try {
            $totalCount = NeckOverdeck::count();
            $validated = $request->validateWithBag('editNeckOverdeck', [
                'name' => 'required|max:100|unique:neck_overdecks,name,' . $neckOverdeck->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'NeckOverdeck name is required.',
                'name.max' => 'NeckOverdeck name must not exceed 100 characters.',
                'name.unique' => 'This neck overdeck name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $neckOverdeck->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    NeckOverdeck::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    NeckOverdeck::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $neckOverdeck->update(array_filter($validated));

            Cache::forget('neck_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Neck Overdeck updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'neck-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editNeckOverdeck')
                ->withInput()
                ->with('openModal', 'editNeckOverdeck')
                ->with('editNeckOverdeckId', $neckOverdeck->id)
                ->with('scrollToSection', 'neck-overdecks');
        }
    }

    public function destroy(NeckOverdeck $neckOverdeck)
    {
        $deletedSortOrder = $neckOverdeck->sort_order;
        $neckOverdeck->delete();

        NeckOverdeck::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('neck_overdecks');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Neck Overdeck deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'neck-overdecks');
    }
}
