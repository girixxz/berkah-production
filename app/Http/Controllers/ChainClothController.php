<?php

namespace App\Http\Controllers;

use App\Models\ChainCloth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChainClothController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addChainCloth', [
            'name' => 'required|max:100|unique:chain_cloths,name',
        ]);

        ChainCloth::create($validated);

        Cache::forget('chain_cloths');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#chain-cloths')
            ->with('message', 'Chain Cloth added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, ChainCloth $chainCloth)
    {
        $validated = $request->validateWithBag('editChainCloth', [
            'name' => 'required|max:100|unique:chain_cloths,name,' . $chainCloth->id,
        ]);

        $chainCloth->update(array_filter($validated));

        Cache::forget('chain_cloths');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#chain-cloths')
            ->with('message', 'Chain Cloth updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(ChainCloth $chainCloth)
    {
        $chainCloth->delete();

        Cache::forget('chain_cloths');

        return redirect()->to(route('owner.manage-data.work-orders.index') . '#chain-cloths')
            ->with('message', 'Chain Cloth deleted successfully.')
            ->with('alert-type', 'success');
    }
}
