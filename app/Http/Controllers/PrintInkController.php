<?php

namespace App\Http\Controllers;

use App\Models\PrintInk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PrintInkController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addPrintInk', [
                'name' => 'required|max:100|unique:print_inks,name',
            ], [
                'name.required' => 'Print Ink name is required.',
                'name.max' => 'Print Ink name must not exceed 100 characters.',
                'name.unique' => 'This print ink name already exists.',
            ]);

            PrintInk::create($validated);

            Cache::forget('print_inks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Print Ink added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'print-inks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addPrintInk')
                ->withInput()
                ->with('openModal', 'addPrintInk')
                ->with('scrollToSection', 'print-inks');
        }
    }

    public function update(Request $request, PrintInk $printInk)
    {
        try {
            $validated = $request->validateWithBag('editPrintInk', [
                'name' => 'required|max:100|unique:print_inks,name,' . $printInk->id,
            ], [
                'name.required' => 'Print Ink name is required.',
                'name.max' => 'Print Ink name must not exceed 100 characters.',
                'name.unique' => 'This print ink name already exists.',
            ]);

            $printInk->update(array_filter($validated));

            Cache::forget('print_inks');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Print Ink updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'print-inks');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editPrintInk')
                ->withInput()
                ->with('openModal', 'editPrintInk')
                ->with('editPrintInkId', $printInk->id)
                ->with('scrollToSection', 'print-inks');
        }
    }

    public function destroy(PrintInk $printInk)
    {
        $printInk->delete();

        Cache::forget('print_inks');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Print Ink deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'print-inks');
    }
}
