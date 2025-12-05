<?php

namespace App\Http\Controllers;

use App\Models\UnderarmOverdeck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UnderarmOverdeckController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addUnderarmOverdeck', [
                'name' => 'required|max:100|unique:underarm_overdecks,name',
            ], [
                'name.required' => 'Underarm Overdeck name is required.',
                'name.max' => 'Underarm Overdeck name must not exceed 100 characters.',
                'name.unique' => 'This underarm overdeck name already exists.',
            ]);

            UnderarmOverdeck::create($validated);

            Cache::forget('underarm_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Underarm Overdeck added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'underarm-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addUnderarmOverdeck')
                ->withInput()
                ->with('openModal', 'addUnderarmOverdeck')
                ->with('scrollToSection', 'underarm-overdecks');
        }
    }

    public function update(Request $request, UnderarmOverdeck $underarmOverdeck)
    {
        try {
            $validated = $request->validateWithBag('editUnderarmOverdeck', [
                'name' => 'required|max:100|unique:underarm_overdecks,name,' . $underarmOverdeck->id,
            ], [
                'name.required' => 'Underarm Overdeck name is required.',
                'name.max' => 'Underarm Overdeck name must not exceed 100 characters.',
                'name.unique' => 'This underarm overdeck name already exists.',
            ]);

            $underarmOverdeck->update(array_filter($validated));

            Cache::forget('underarm_overdecks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Underarm Overdeck updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'underarm-overdecks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editUnderarmOverdeck')
                ->withInput()
                ->with('openModal', 'editUnderarmOverdeck')
                ->with('editUnderarmOverdeckId', $underarmOverdeck->id)
                ->with('scrollToSection', 'underarm-overdecks');
        }
    }

    public function destroy(UnderarmOverdeck $underarmOverdeck)
    {
        $underarmOverdeck->delete();

        Cache::forget('underarm_overdecks');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Underarm Overdeck deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'underarm-overdecks');
    }
}
