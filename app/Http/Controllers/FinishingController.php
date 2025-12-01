<?php

namespace App\Http\Controllers;

use App\Models\Finishing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FinishingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addFinishing', [
                'name' => 'required|max:100|unique:finishings,name',
            ]);

            Finishing::create($validated);

            Cache::forget('finishings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Finishing added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'finishings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addFinishing')
                ->withInput()
                ->with('openModal', 'addFinishing')
                ->with('scrollToSection', 'finishings');
        }
    }

    public function update(Request $request, Finishing $finishing)
    {
        try {
            $validated = $request->validateWithBag('editFinishing', [
                'name' => 'required|max:100|unique:finishings,name,' . $finishing->id,
            ]);

            $finishing->update(array_filter($validated));

            Cache::forget('finishings');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Finishing updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'finishings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editFinishing')
                ->withInput()
                ->with('openModal', 'editFinishing')
                ->with('editFinishingId', $finishing->id)
                ->with('scrollToSection', 'finishings');
        }
    }

    public function destroy(Finishing $finishing)
    {
        $finishing->delete();

        Cache::forget('finishings');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Finishing deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'finishings');
    }
}
