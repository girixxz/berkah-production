<?php

namespace App\Http\Controllers;

use App\Models\NeckOverdeck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NeckOverdeckController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addNeckOverdeck', [
                'name' => 'required|max:100|unique:neck_overdecks,name',
            ], [
                'name.required' => 'Neck Overdeck name is required.',
                'name.max' => 'Neck Overdeck name must not exceed 100 characters.',
                'name.unique' => 'This neck overdeck name already exists.',
            ]);

            NeckOverdeck::create($validated);

            Cache::forget('neck_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Neck Overdeck added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'neck-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addNeckOverdeck')
                ->withInput()
                ->with('openModal', 'addNeckOverdeck')
                ->with('scrollToSection', 'neck-overdecks');
        }
    }

    public function update(Request $request, NeckOverdeck $neckOverdeck)
    {
        try {
            $validated = $request->validateWithBag('editNeckOverdeck', [
                'name' => 'required|max:100|unique:neck_overdecks,name,' . $neckOverdeck->id,
            ], [
                'name.required' => 'Neck Overdeck name is required.',
                'name.max' => 'Neck Overdeck name must not exceed 100 characters.',
                'name.unique' => 'This neck overdeck name already exists.',
            ]);

            $neckOverdeck->update(array_filter($validated));

            Cache::forget('neck_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Neck Overdeck updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'neck-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editNeckOverdeck')
                ->withInput()
                ->with('openModal', 'editNeckOverdeck')
                ->with('editNeckOverdeckId', $neckOverdeck->id)
                ->with('scrollToSection', 'neck-overdecks');
        }
    }

    public function destroy(NeckOverdeck $neckOverdeck)
    {
        $neckOverdeck->delete();

        Cache::forget('neck_overdecks');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Neck Overdeck deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'neck-overdecks');
    }
}
