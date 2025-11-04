<?php

namespace App\Http\Controllers;

use App\Models\PrintInk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PrintInkController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addPrintInk', [
            'name' => 'required|max:100|unique:print_inks,name',
        ]);

        PrintInk::create($validated);

        Cache::forget('print_inks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#print-inks')
            ->with('message', 'Print Ink added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, PrintInk $printInk)
    {
        $validated = $request->validateWithBag('editPrintInk', [
            'name' => 'required|max:100|unique:print_inks,name,' . $printInk->id,
        ]);

        $printInk->update(array_filter($validated));

        Cache::forget('print_inks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#print-inks')
            ->with('message', 'Print Ink updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(PrintInk $printInk)
    {
        $printInk->delete();

        Cache::forget('print_inks');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#print-inks')
            ->with('message', 'Print Ink deleted successfully.')
            ->with('alert-type', 'success');
    }
}
