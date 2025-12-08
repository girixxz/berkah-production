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

            // Auto-generate sort_order: max + 1
            $maxSortOrder = ProductCategory::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

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
            // Get total count
            $totalProducts = ProductCategory::count();
            
            $validated = $request->validateWithBag('editProduct', [
                'product_name' => 'required|max:255|unique:product_categories,product_name,' . $productCategory->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalProducts,
            ], [
                'product_name.required' => 'Product name is required.',
                'product_name.max' => 'Product name must not exceed 255 characters.',
                'product_name.unique' => 'This product name already exists.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total products (' . $totalProducts . ').',
            ]);

            $oldSortOrder = $productCategory->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    ProductCategory::where('id', '!=', $productCategory->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    ProductCategory::where('id', '!=', $productCategory->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the product
            $productCategory->update($validated);

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
        $deletedSortOrder = $productCategory->sort_order;
        
        // Delete the product
        $productCategory->delete();

        // Reorder: decrement sort_order for all items after deleted item
        ProductCategory::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        // Clear cache
        Cache::forget('product_categories');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Product Category deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'product-categories');
    }
}
