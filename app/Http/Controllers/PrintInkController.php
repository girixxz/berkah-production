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

            $maxSortOrder = PrintInk::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

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
            $totalCount = PrintInk::count();
            $validated = $request->validateWithBag('editPrintInk', [
                'name' => 'required|max:100|unique:print_inks,name,' . $printInk->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Print Ink name is required.',
                'name.max' => 'Print Ink name must not exceed 100 characters.',
                'name.unique' => 'This print ink name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $printInk->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    PrintInk::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    PrintInk::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

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
        $deletedSortOrder = $printInk->sort_order;
        $printInk->delete();

        PrintInk::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('print_inks');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Print Ink deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'print-inks');
    }
}
