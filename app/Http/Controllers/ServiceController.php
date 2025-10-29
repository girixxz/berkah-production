<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addService', [
            'service_name' => 'required|string|max:100|unique:services,service_name',
        ]);

        Service::create($validated);

        return redirect()
            ->to(url()->previous() . '#services')
            ->with('message', 'Service added successfully.')
            ->with('alert-type', 'success');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validateWithBag('editService', [
            'service_name' => 'required|string|max:100|unique:services,service_name,' . $service->id,
        ]);

        $service->update($validated);

        return redirect()
            ->to(url()->previous() . '#services')
            ->with('message', 'Service updated successfully.')
            ->with('alert-type', 'success');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()
            ->to(url()->previous() . '#services')
            ->with('message', 'Service deleted successfully.')
            ->with('alert-type', 'success');
    }
}
