<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addService', [
                'service_name' => 'required|max:255|unique:services,service_name',
            ], [
                'service_name.required' => 'Service name is required.',
                'service_name.max' => 'Service name must not exceed 255 characters.',
                'service_name.unique' => 'This service name already exists.',
            ]);

            // Auto-generate sort_order: max + 1
            $maxSortOrder = Service::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

            Service::create($validated);

            // Clear cache
            Cache::forget('services');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Service added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'services');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'addService')
                ->withInput()
                ->with('openModal', 'addService')
                ->with('scrollToSection', 'services');
        }
    }

    public function update(Request $request, Service $service)
    {
        try {
            // Get total count
            $totalServices = Service::count();
            
            $validated = $request->validateWithBag('editService', [
                'service_name' => 'required|max:255|unique:services,service_name,' . $service->id,
                'sort_order' => 'required|integer|min:1|max:' . $totalServices,
            ], [
                'service_name.required' => 'Service name is required.',
                'service_name.max' => 'Service name must not exceed 255 characters.',
                'service_name.unique' => 'This service name already exists.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total services (' . $totalServices . ').',
            ]);

            $oldSortOrder = $service->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    Service::where('id', '!=', $service->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    Service::where('id', '!=', $service->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the service
            $service->update($validated);

            // Clear cache
            Cache::forget('services');

            return redirect()->route('owner.manage-data.products.index')
                ->with('message', 'Service updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'services');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('owner.manage-data.products.index')
                ->withErrors($e->errors(), 'editService')
                ->withInput()
                ->with('openModal', 'editService')
                ->with('editServiceId', $service->id)
                ->with('scrollToSection', 'services');
        }
    }

    public function destroy(Service $service)
    {
        $deletedSortOrder = $service->sort_order;
        
        // Delete the service
        $service->delete();

        // Reorder: decrement sort_order for all items after deleted item
        Service::where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        // Clear cache
        Cache::forget('services');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Service deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'services');
    }
}
