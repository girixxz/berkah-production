<?php

namespace App\Http\Controllers;

use App\Models\RibSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RibSizeController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addRibSize', [
                'name' => 'required|max:100|unique:rib_sizes,name',
            ], [
                'name.required' => 'Rib Size name is required.',
                'name.max' => 'Rib Size name must not exceed 100 characters.',
                'name.unique' => 'This rib size name already exists.',
            ]);

            RibSize::create($validated);

            Cache::forget('rib_sizes');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Rib Size added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'rib-sizes');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addRibSize')
                ->withInput()
                ->with('openModal', 'addRibSize')
                ->with('scrollToSection', 'rib-sizes');
        }
    }

    public function update(Request $request, RibSize $ribSize)
    {
        try {
            $validated = $request->validateWithBag('editRibSize', [
                'name' => 'required|max:100|unique:rib_sizes,name,' . $ribSize->id,
            ], [
                'name.required' => 'Rib Size name is required.',
                'name.max' => 'Rib Size name must not exceed 100 characters.',
                'name.unique' => 'This rib size name already exists.',
            ]);

            $ribSize->update(array_filter($validated));

            Cache::forget('rib_sizes');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Rib Size updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'rib-sizes');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editRibSize')
                ->withInput()
                ->with('openModal', 'editRibSize')
                ->with('editRibSizeId', $ribSize->id)
                ->with('scrollToSection', 'rib-sizes');
        }
    }

    public function destroy(RibSize $ribSize)
    {
        $ribSize->delete();

        Cache::forget('rib_sizes');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Rib Size deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'rib-sizes');
    }
}
