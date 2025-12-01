<?php

namespace App\Http\Controllers;

use App\Models\SideSplit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SideSplitController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSideSplit', [
                'name' => 'required|max:100|unique:side_splits,name',
            ]);

            SideSplit::create($validated);

            Cache::forget('side_splits');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Side Split added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'side-splits');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addSideSplit')
                ->withInput()
                ->with('openModal', 'addSideSplit')
                ->with('scrollToSection', 'side-splits');
        }
    }

    public function update(Request $request, SideSplit $sideSplit)
    {
        try {
            $validated = $request->validateWithBag('editSideSplit', [
                'name' => 'required|max:100|unique:side_splits,name,' . $sideSplit->id,
            ]);

            $sideSplit->update(array_filter($validated));

            Cache::forget('side_splits');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Side Split updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'side-splits');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editSideSplit')
                ->withInput()
                ->with('openModal', 'editSideSplit')
                ->with('editSideSplitId', $sideSplit->id)
                ->with('scrollToSection', 'side-splits');
        }
    }

    public function destroy(SideSplit $sideSplit)
    {
        $sideSplit->delete();

        Cache::forget('side_splits');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Side Split deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'side-splits');
    }
}
