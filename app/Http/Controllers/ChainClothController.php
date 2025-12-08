<?php

namespace App\Http\Controllers;

use App\Models\ChainCloth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChainClothController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addChainCloth', [
                'name' => 'required|max:100|unique:chain_cloths,name',
            ], [
                'name.required' => 'Chain Cloth name is required.',
                'name.max' => 'Chain Cloth name must not exceed 100 characters.',
                'name.unique' => 'This chain cloth name already exists.',
            ]);

            $maxSortOrder = ChainCloth::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            ChainCloth::create($validated);

            Cache::forget('chain_cloths');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Chain Cloth added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'chain-cloths');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'addChainCloth')
                ->withInput()
                ->with('openModal', 'addChainCloth')
                ->with('scrollToSection', 'chain-cloths');
        }
    }

    public function update(Request $request, ChainCloth $chainCloth)
    {
        try {
            $totalCount = ChainCloth::count();
            $validated = $request->validateWithBag('editChainCloth', [
                'name' => 'required|max:100|unique:chain_cloths,name,' . $chainCloth->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalCount,
            ], [
                'name.required' => 'Chain Cloth name is required.',
                'name.max' => 'Chain Cloth name must not exceed 100 characters.',
                'name.unique' => 'This chain cloth name already exists.',
                'sort_order.required' => 'Sort Order is required.',
                'sort_order.integer' => 'Sort Order must be a number.',
                'sort_order.min' => 'Sort Order must be at least 1.',
                'sort_order.max' => 'Sort Order cannot exceed ' . $totalCount . '.',
            ]);

            $oldSortOrder = $chainCloth->sort_order;
            $newSortOrder = $validated['sort_order'];

            if ($oldSortOrder != $newSortOrder) {
                if ($newSortOrder > $oldSortOrder) {
                    ChainCloth::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                        ->decrement('sort_order');
                } else {
                    ChainCloth::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                        ->increment('sort_order');
                }
            }

            $chainCloth->update(array_filter($validated));

            Cache::forget('chain_cloths');

            return redirect()->route('owner.manage-data.work-orders.index')
                ->with('message', 'Chain Cloth updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'chain-cloths');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.work-orders.index')
                ->withErrors($e->errors(), 'editChainCloth')
                ->withInput()
                ->with('openModal', 'editChainCloth')
                ->with('editChainClothId', $chainCloth->id)
                ->with('scrollToSection', 'chain-cloths');
        }
    }

    public function destroy(ChainCloth $chainCloth)
    {
        $deletedSortOrder = $chainCloth->sort_order;
        $chainCloth->delete();

        ChainCloth::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        Cache::forget('chain_cloths');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Chain Cloth deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'chain-cloths');
    }
}
