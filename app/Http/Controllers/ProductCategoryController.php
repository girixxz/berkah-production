<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductCategoryController extends Controller
{

    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addProduct', [
                'product_name' => 'required|max:255|unique:product_categories,product_name',
            ], [
                'product_name.required' => 'Product name is required.',
                'product_name.max' => 'Product name must not exceed 255 characters.',
                'product_name.unique' => 'This product name already exists.',
            ]);

            ProductCategory::create($validated);

            // Clear cache
            Cache::forget('product_categories');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Product added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'product-categories');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'addProduct')
                ->withInput()
                ->with('openModal', 'addProduct')
                ->with('scrollToSection', 'product-categories');
        }
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        try {
            $validated = $request->validateWithBag('editProduct', [
                'product_name' => 'required|max:255|unique:product_categories,product_name,' . $productCategory->id,
            ], [
                'product_name.required' => 'Product name is required.',
                'product_name.max' => 'Product name must not exceed 255 characters.',
                'product_name.unique' => 'This product name already exists.',
            ]);

            $productCategory->update(array_filter($validated));

            // Clear cache
            Cache::forget('product_categories');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Product updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'product-categories');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'editProduct')
                ->withInput()
                ->with('openModal', 'editProduct')
                ->with('editProductId', $productCategory->id)
                ->with('scrollToSection', 'product-categories');
        }
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        // Clear cache
        Cache::forget('product_categories');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Product Category deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'product-categories');
    }
}
