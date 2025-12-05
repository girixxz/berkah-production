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
            $validated = $request->validateWithBag('editService', [
                'service_name' => 'required|max:255|unique:services,service_name,' . $service->id,
            ], [
                'service_name.required' => 'Service name is required.',
                'service_name.max' => 'Service name must not exceed 255 characters.',
                'service_name.unique' => 'This service name already exists.',
            ]);

            $service->update(array_filter($validated));

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
        $service->delete();

        // Clear cache
        Cache::forget('services');

        return redirect()->route('owner.manage-data.products.index')
            ->with('message', 'Service deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'services');
    }
}
