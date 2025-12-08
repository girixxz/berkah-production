<?php

namespace App\Http\Controllers;

use App\Models\Finishing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FinishingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addFinishing', [
                'name' => 'required|max:100|unique:finishings,name',
            ], [
                'name.required' => 'Finishing name is required.',
                'name.max' => 'Finishing name must not exceed 100 characters.',
                'name.unique' => 'This finishing name already exists.',
            ]);

            $maxSortOrder = Finishing::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            Finishing::create($validated);

            Cache::forget('finishings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Finishing added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'finishings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addFinishing')
                ->withInput()
                ->with('openModal', 'addFinishing')
                ->with('scrollToSection', 'finishings');
        }
    }

    public function update(Request $request, Finishing $finishing)
    {
        try {
            $totalCount = Finishing::count();
            $validated = $request->validateWithBag('editFinishing', [
                'name' => 'required|max:100|unique:finishings,name,' . $finishing->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Finishing name is required.',
                'name.max' => 'Finishing name must not exceed 100 characters.',
                'name.unique' => 'This finishing name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $finishing->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    Finishing::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    Finishing::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $finishing->update(array_filter($validated));

            Cache::forget('finishings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Finishing updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'finishings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editFinishing')
                ->withInput()
                ->with('openModal', 'editFinishing')
                ->with('editFinishingId', $finishing->id)
                ->with('scrollToSection', 'finishings');
        }
    }

    public function destroy(Finishing $finishing)
    {
        $deletedSortOrder = $finishing->sort_order;
        $finishing->delete();

        Finishing::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('finishings');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Finishing deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'finishings');
    }
}
