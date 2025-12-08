<?php

namespace App\Http\Controllers;

use App\Models\SewingLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SewingLabelController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSewingLabel', [
                'name' => 'required|max:100|unique:sewing_labels,name',
            ], [
                'name.required' => 'Sewing Label name is required.',
                'name.max' => 'Sewing Label name must not exceed 100 characters.',
                'name.unique' => 'This sewing label name already exists.',
            ]);

            $maxSortOrder = SewingLabel::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            SewingLabel::create($validated);

            Cache::forget('sewing_labels');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Sewing Label added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'sewing-labels');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addSewingLabel')
                ->withInput()
                ->with('openModal', 'addSewingLabel')
                ->with('scrollToSection', 'sewing-labels');
        }
    }

    public function update(Request $request, SewingLabel $sewingLabel)
    {
        try {
            $totalCount = SewingLabel::count();
            $validated = $request->validateWithBag('editSewingLabel', [
                'name' => 'required|max:100|unique:sewing_labels,name,' . $sewingLabel->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Sewing Label name is required.',
                'name.max' => 'Sewing Label name must not exceed 100 characters.',
                'name.unique' => 'This sewing label name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $sewingLabel->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    SewingLabel::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    SewingLabel::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $sewingLabel->update(array_filter($validated));

            Cache::forget('sewing_labels');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Sewing Label updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'sewing-labels');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editSewingLabel')
                ->withInput()
                ->with('openModal', 'editSewingLabel')
                ->with('editSewingLabelId', $sewingLabel->id)
                ->with('scrollToSection', 'sewing-labels');
        }
    }

    public function destroy(SewingLabel $sewingLabel)
    {
        $deletedSortOrder = $sewingLabel->sort_order;
        $sewingLabel->delete();

        SewingLabel::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('sewing_labels');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Sewing Label deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'sewing-labels');
    }
}
