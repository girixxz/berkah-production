<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StickerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addSticker', [
            'name' => 'required|max:100|unique:stickers,name',
        ]);

        Sticker::create($validated);

        Cache::forget('stickers');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#stickers')
            ->with('message', 'Sticker added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, Sticker $sticker)
    {
        $validated = $request->validateWithBag('editSticker', [
            'name' => 'required|max:100|unique:stickers,name,' . $sticker->id,
        ]);

        $sticker->update(array_filter($validated));

        Cache::forget('stickers');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#stickers')
            ->with('message', 'Sticker updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(Sticker $sticker)
    {
        $sticker->delete();

        Cache::forget('stickers');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#stickers')
            ->with('message', 'Sticker deleted successfully.')
            ->with('alert-type', 'success');
    }
}
