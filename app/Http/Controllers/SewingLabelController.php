<?php

namespace App\Http\Controllers;

use App\Models\SewingLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SewingLabelController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addSewingLabel', [
                'name' => 'required|max:100|unique:sewing_labels,name',
            ]);

            SewingLabel::create($validated);

            Cache::forget('sewing_labels');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Sewing Label added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'sewing-labels');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addSewingLabel')
                ->withInput()
                ->with('openModal', 'addSewingLabel')
                ->with('scrollToSection', 'sewing-labels');
        }
    }

    public function update(Request $request, SewingLabel $sewingLabel)
    {
        try {
            $validated = $request->validateWithBag('editSewingLabel', [
                'name' => 'required|max:100|unique:sewing_labels,name,' . $sewingLabel->id,
            ]);

            $sewingLabel->update(array_filter($validated));

            Cache::forget('sewing_labels');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Sewing Label updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'sewing-labels');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editSewingLabel')
                ->withInput()
                ->with('openModal', 'editSewingLabel')
                ->with('editSewingLabelId', $sewingLabel->id)
                ->with('scrollToSection', 'sewing-labels');
        }
    }

    public function destroy(SewingLabel $sewingLabel)
    {
        $sewingLabel->delete();

        Cache::forget('sewing_labels');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Sewing Label deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'sewing-labels');
    }
}
