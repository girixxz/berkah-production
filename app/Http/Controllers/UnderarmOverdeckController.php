<?php

namespace App\Http\Controllers;

use App\Models\UnderarmOverdeck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UnderarmOverdeckController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addUnderarmOverdeck', [
            'name' => 'required|max:100|unique:underarm_overdecks,name',
        ]);

        UnderarmOverdeck::create($validated);

        Cache::forget('underarm_overdecks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#underarm-overdecks')
            ->with('message', 'Underarm Overdeck added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, UnderarmOverdeck $underarmOverdeck)
    {
        $validated = $request->validateWithBag('editUnderarmOverdeck', [
            'name' => 'required|max:100|unique:underarm_overdecks,name,' . $underarmOverdeck->id,
        ]);

        $underarmOverdeck->update(array_filter($validated));

        Cache::forget('underarm_overdecks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#underarm-overdecks')
            ->with('message', 'Underarm Overdeck updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(UnderarmOverdeck $underarmOverdeck)
    {
        $underarmOverdeck->delete();

        Cache::forget('underarm_overdecks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#underarm-overdecks')
            ->with('message', 'Underarm Overdeck deleted successfully.')
            ->with('alert-type', 'success');
    }
}
