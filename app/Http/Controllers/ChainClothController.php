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
            $validated = $request->validateWithBag('editChainCloth', [
                'name' => 'required|max:100|unique:chain_cloths,name,' . $chainCloth->id,
            ], [
                'name.required' => 'Chain Cloth name is required.',
                'name.max' => 'Chain Cloth name must not exceed 100 characters.',
                'name.unique' => 'This chain cloth name already exists.',
            ]);

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
        $chainCloth->delete();

        Cache::forget('chain_cloths');

        return redirect()->route('owner.manage-data.work-orders.index')
            ->with('message', 'Chain Cloth deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'chain-cloths');
    }
}
