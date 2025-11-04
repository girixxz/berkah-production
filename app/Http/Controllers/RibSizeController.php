<?php

namespace App\Http\Controllers;

use App\Models\RibSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RibSizeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addRibSize', [
            'name' => 'required|max:100|unique:rib_sizes,name',
        ]);

        RibSize::create($validated);

        Cache::forget('rib_sizes');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#rib-sizes')
            ->with('message', 'Rib Size added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, RibSize $ribSize)
    {
        $validated = $request->validateWithBag('editRibSize', [
            'name' => 'required|max:100|unique:rib_sizes,name,' . $ribSize->id,
        ]);

        $ribSize->update(array_filter($validated));

        Cache::forget('rib_sizes');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#rib-sizes')
            ->with('message', 'Rib Size updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(RibSize $ribSize)
    {
        $ribSize->delete();

        Cache::forget('rib_sizes');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#rib-sizes')
            ->with('message', 'Rib Size deleted successfully.')
            ->with('alert-type', 'success');
    }
}
