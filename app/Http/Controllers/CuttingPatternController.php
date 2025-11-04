<?php

namespace App\Http\Controllers;

use App\Models\CuttingPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CuttingPatternController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addCuttingPattern', [
            'name' => 'required|max:100|unique:cutting_patterns,name',
        ]);

        CuttingPattern::create($validated);

        Cache::forget('cutting_patterns');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#cutting-patterns')
            ->with('message', 'Cutting Pattern added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, CuttingPattern $cuttingPattern)
    {
        $validated = $request->validateWithBag('editCuttingPattern', [
            'name' => 'required|max:100|unique:cutting_patterns,name,' . $cuttingPattern->id,
        ]);

        $cuttingPattern->update(array_filter($validated));

        Cache::forget('cutting_patterns');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#cutting-patterns')
            ->with('message', 'Cutting Pattern updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(CuttingPattern $cuttingPattern)
    {
        $cuttingPattern->delete();

        Cache::forget('cutting_patterns');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#cutting-patterns')
            ->with('message', 'Cutting Pattern deleted successfully.')
            ->with('alert-type', 'success');
    }
}
