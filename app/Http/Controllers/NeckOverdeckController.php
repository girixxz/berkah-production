<?php

namespace App\Http\Controllers;

use App\Models\NeckOverdeck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NeckOverdeckController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addNeckOverdeck', [
            'name' => 'required|max:100|unique:neck_overdecks,name',
        ]);

        NeckOverdeck::create($validated);

        Cache::forget('neck_overdecks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#neck-overdecks')
            ->with('message', 'Neck Overdeck added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, NeckOverdeck $neckOverdeck)
    {
        $validated = $request->validateWithBag('editNeckOverdeck', [
            'name' => 'required|max:100|unique:neck_overdecks,name,' . $neckOverdeck->id,
        ]);

        $neckOverdeck->update(array_filter($validated));

        Cache::forget('neck_overdecks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#neck-overdecks')
            ->with('message', 'Neck Overdeck updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(NeckOverdeck $neckOverdeck)
    {
        $neckOverdeck->delete();

        Cache::forget('neck_overdecks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#neck-overdecks')
            ->with('message', 'Neck Overdeck deleted successfully.')
            ->with('alert-type', 'success');
    }
}
