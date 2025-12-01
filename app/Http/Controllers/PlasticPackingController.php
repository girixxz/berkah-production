<?php

namespace App\Http\Controllers;

use App\Models\PlasticPacking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlasticPackingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addPlasticPacking', [
                'name' => 'required|max:100|unique:plastic_packings,name',
            ]);

            PlasticPacking::create($validated);

            Cache::forget('plastic_packings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Plastic Packing added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'plastic-packings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addPlasticPacking')
                ->withInput()
                ->with('openModal', 'addPlasticPacking')
                ->with('scrollToSection', 'plastic-packings');
        }
    }

    public function update(Request $request, PlasticPacking $plasticPacking)
    {
        try {
            $validated = $request->validateWithBag('editPlasticPacking', [
                'name' => 'required|max:100|unique:plastic_packings,name,' . $plasticPacking->id,
            ]);

            $plasticPacking->update(array_filter($validated));

            Cache::forget('plastic_packings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Plastic Packing updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'plastic-packings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editPlasticPacking')
                ->withInput()
                ->with('openModal', 'editPlasticPacking')
                ->with('editPlasticPackingId', $plasticPacking->id)
                ->with('scrollToSection', 'plastic-packings');
        }
    }

    public function destroy(PlasticPacking $plasticPacking)
    {
        $plasticPacking->delete();

        Cache::forget('plastic_packings');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Plastic Packing deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'plastic-packings');
    }
}
