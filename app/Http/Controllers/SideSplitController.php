<?php

namespace App\Http\Controllers;

use App\Models\SideSplit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SideSplitController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addSideSplit', [
            'name' => 'required|max:100|unique:side_splits,name',
        ]);

        SideSplit::create($validated);

        Cache::forget('side_splits');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#side-splits')
            ->with('message', 'Side Split added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, SideSplit $sideSplit)
    {
        $validated = $request->validateWithBag('editSideSplit', [
            'name' => 'required|max:100|unique:side_splits,name,' . $sideSplit->id,
        ]);

        $sideSplit->update(array_filter($validated));

        Cache::forget('side_splits');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#side-splits')
            ->with('message', 'Side Split updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(SideSplit $sideSplit)
    {
        $sideSplit->delete();

        Cache::forget('side_splits');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#side-splits')
            ->with('message', 'Side Split deleted successfully.')
            ->with('alert-type', 'success');
    }
}
