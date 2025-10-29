<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addProduct', [
            'product_name' => 'required|max:255|unique:product_categories,product_name',
        ]);

        ProductCategory::create($validated);

        return redirect()->to(
            route('owner.manage-data.products.index') . '#product-categories'
        )->with('message', 'Product added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $validated = $request->validateWithBag('editProduct', [
            'product_name' => 'required|max:255|unique:product_categories,product_name,' . $productCategory->id,
        ]);

        $productCategory->update(array_filter($validated));

        return redirect()->to(route('owner.manage-data.products.index') . '#product-categories')
            ->with('message', 'Product updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return redirect()->to(route('owner.manage-data.products.index') . '#product-categories')
            ->with('message', 'Product Category deleted successfully.')
            ->with('alert-type', 'success');
    }
}
