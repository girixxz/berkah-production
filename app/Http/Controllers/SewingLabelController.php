<?php

namespace App\Http\Controllers;

use App\Models\SewingLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SewingLabelController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addSewingLabel', [
            'name' => 'required|max:100|unique:sewing_labels,name',
        ]);

        SewingLabel::create($validated);

        Cache::forget('sewing_labels');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#sewing-labels')
            ->with('message', 'Sewing Label added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, SewingLabel $sewingLabel)
    {
        $validated = $request->validateWithBag('editSewingLabel', [
            'name' => 'required|max:100|unique:sewing_labels,name,' . $sewingLabel->id,
        ]);

        $sewingLabel->update(array_filter($validated));

        Cache::forget('sewing_labels');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#sewing-labels')
            ->with('message', 'Sewing Label updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(SewingLabel $sewingLabel)
    {
        $sewingLabel->delete();

        Cache::forget('sewing_labels');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#sewing-labels')
            ->with('message', 'Sewing Label deleted successfully.')
            ->with('alert-type', 'success');
    }
}
