<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StickerController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSticker', [
                'name' => 'required|max:100|unique:stickers,name',
            ], [
                'name.required' => 'Sticker name is required.',
                'name.max' => 'Sticker name must not exceed 100 characters.',
                'name.unique' => 'This sticker name already exists.',
            ]);

            $maxSortOrder = Sticker::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            Sticker::create($validated);

            Cache::forget('stickers');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Sticker added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'stickers');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addSticker')
                ->withInput()
                ->with('openModal', 'addSticker')
                ->with('scrollToSection', 'stickers');
        }
    }

    public function update(Request $request, Sticker $sticker)
    {
        try {
            $totalCount = Sticker::count();
            $validated = $request->validateWithBag('editSticker', [
                'name' => 'required|max:100|unique:stickers,name,' . $sticker->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Sticker name is required.',
                'name.max' => 'Sticker name must not exceed 100 characters.',
                'name.unique' => 'This sticker name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $sticker->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    Sticker::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    Sticker::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $sticker->update(array_filter($validated));

            Cache::forget('stickers');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Sticker updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'stickers');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editSticker')
                ->withInput()
                ->with('openModal', 'editSticker')
                ->with('editStickerId', $sticker->id)
                ->with('scrollToSection', 'stickers');
        }
    }

    public function destroy(Sticker $sticker)
    {
        $deletedSortOrder = $sticker->sort_order;
        $sticker->delete();

        Sticker::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('stickers');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Sticker deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'stickers');
    }
}
