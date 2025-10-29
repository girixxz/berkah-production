<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipping;

class ShippingController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addShipping', [
            'shipping_name' => 'required|max:255|unique:shippings,shipping_name',
        ]);

        Shipping::create($validated);

        return redirect()->to(route('owner.manage-data.products.index') . '#shippings')
            ->with('message', 'Shipping added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, Shipping $shipping)
    {
        $validated = $request->validateWithBag('editShipping', [
            'shipping_name' => 'required|max:255|unique:shippings,shipping_name,' . $shipping->id,
        ]);

        $shipping->update(array_filter($validated));

        return redirect()->to(route('owner.manage-data.products.index') . '#shippings')
            ->with('message', 'Shipping updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(Shipping $shipping)
    {
        $shipping->delete();

        return redirect()->to(route('owner.manage-data.products.index') . '#shippings')
            ->with('message', 'Shipping deleted successfully.')
            ->with('alert-type', 'success');
    }
}
