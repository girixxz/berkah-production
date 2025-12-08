<?php

namespace App\Http\Controllers;

use App\Models\UnderarmOverdeck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UnderarmOverdeckController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addUnderarmOverdeck', [
                'name' => 'required|max:100|unique:underarm_overdecks,name',
            ], [
                'name.required' => 'Underarm Overdeck name is required.',
                'name.max' => 'Underarm Overdeck name must not exceed 100 characters.',
                'name.unique' => 'This underarm overdeck name already exists.',
            ]);

            $maxSortOrder = UnderarmOverdeck::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            UnderarmOverdeck::create($validated);

            Cache::forget('underarm_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Underarm Overdeck added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'underarm-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addUnderarmOverdeck')
                ->withInput()
                ->with('openModal', 'addUnderarmOverdeck')
                ->with('scrollToSection', 'underarm-overdecks');
        }
    }

    public function update(Request $request, UnderarmOverdeck $underarmOverdeck)
    {
        try {
            $totalCount = UnderarmOverdeck::count();
            $validated = $request->validateWithBag('editUnderarmOverdeck', [
                'name' => 'required|max:100|unique:underarm_overdecks,name,' . $underarmOverdeck->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Underarm Overdeck name is required.',
                'name.max' => 'Underarm Overdeck name must not exceed 100 characters.',
                'name.unique' => 'This underarm overdeck name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $underarmOverdeck->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    UnderarmOverdeck::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    UnderarmOverdeck::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $underarmOverdeck->update(array_filter($validated));

            Cache::forget('underarm_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Underarm Overdeck updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'underarm-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editUnderarmOverdeck')
                ->withInput()
                ->with('openModal', 'editUnderarmOverdeck')
                ->with('editUnderarmOverdeckId', $underarmOverdeck->id)
                ->with('scrollToSection', 'underarm-overdecks');
        }
    }

    public function destroy(UnderarmOverdeck $underarmOverdeck)
    {
        $deletedSortOrder = $underarmOverdeck->sort_order;
        $underarmOverdeck->delete();

        UnderarmOverdeck::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('underarm_overdecks');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Underarm Overdeck deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'underarm-overdecks');
    }
}
