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
            $validated = $request->validateWithBag('editSticker', [
                'name' => 'required|max:100|unique:stickers,name,' . $sticker->id,
            ], [
                'name.required' => 'Sticker name is required.',
                'name.max' => 'Sticker name must not exceed 100 characters.',
                'name.unique' => 'This sticker name already exists.',
            ]);

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
        $sticker->delete();

        Cache::forget('stickers');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Sticker deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'stickers');
    }
}
