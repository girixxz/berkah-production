<?php

namespace App\Http\Controllers;

use App\Models\CuttingPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CuttingPatternController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addCuttingPattern', [
                'name' => 'required|max:100|unique:cutting_patterns,name',
            ], [
                'name.required' => 'Cutting Pattern name is required.',
                'name.max' => 'Cutting Pattern name must not exceed 100 characters.',
                'name.unique' => 'This cutting pattern name already exists.',
            ]);

            CuttingPattern::create($validated);

            Cache::forget('cutting_patterns');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Cutting Pattern added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'cutting-patterns');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addCuttingPattern')
                ->withInput()
                ->with('openModal', 'addCuttingPattern')
                ->with('scrollToSection', 'cutting-patterns');
        }
    }

    public function update(Request $request, CuttingPattern $cuttingPattern)
    {
        try {
            $validated = $request->validateWithBag('editCuttingPattern', [
                'name' => 'required|max:100|unique:cutting_patterns,name,' . $cuttingPattern->id,
            ], [
                'name.required' => 'Cutting Pattern name is required.',
                'name.max' => 'Cutting Pattern name must not exceed 100 characters.',
                'name.unique' => 'This cutting pattern name already exists.',
            ]);

            $cuttingPattern->update(array_filter($validated));

            Cache::forget('cutting_patterns');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Cutting Pattern updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'cutting-patterns');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editCuttingPattern')
                ->withInput()
                ->with('openModal', 'editCuttingPattern')
                ->with('editCuttingPatternId', $cuttingPattern->id)
                ->with('scrollToSection', 'cutting-patterns');
        }
    }

    public function destroy(CuttingPattern $cuttingPattern)
    {
        $cuttingPattern->delete();

        Cache::forget('cutting_patterns');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Cutting Pattern deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'cutting-patterns');
    }
}
