<?php

namespace App\Http\Controllers;

use App\Models\Finishing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FinishingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addFinishing', [
            'name' => 'required|max:100|unique:finishings,name',
        ]);

        Finishing::create($validated);

        Cache::forget('finishings');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#finishings')
            ->with('message', 'Finishing added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, Finishing $finishing)
    {
        $validated = $request->validateWithBag('editFinishing', [
            'name' => 'required|max:100|unique:finishings,name,' . $finishing->id,
        ]);

        $finishing->update(array_filter($validated));

        Cache::forget('finishings');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#finishings')
            ->with('message', 'Finishing updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(Finishing $finishing)
    {
        $finishing->delete();

        Cache::forget('finishings');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#finishings')
            ->with('message', 'Finishing deleted successfully.')
            ->with('alert-type', 'success');
    }
}
