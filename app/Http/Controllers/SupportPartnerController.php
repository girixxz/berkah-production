<?php

namespace App\Http\Controllers;

use App\Models\SupportPartner;
use Illuminate\Http\Request;

class SupportPartnerController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validateWithBag('addPartner', [
                'partner_name' => 'required|string|max:100|unique:support_partners,partner_name',
                'notes' => 'nullable|string',
            ], [
                'partner_name.required' => 'Partner name is required.',
                'partner_name.max' => 'Partner name must not exceed 100 characters.',
                'partner_name.unique' => 'This partner name already exists.',
            ]);

            SupportPartner::create($validated);

            return back()
                ->with('message', 'Support partner added successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'support-partners');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'addPartner')
                ->withInput()
                ->with('openModal', 'addPartner')
                ->with('scrollToSection', 'support-partners');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SupportPartner $supportPartner)
    {
        try {
            $validated = $request->validateWithBag('editPartner', [
                'partner_name' => 'required|string|max:100|unique:support_partners,partner_name,' . $supportPartner->id,
                'notes' => 'nullable|string',
            ], [
                'partner_name.required' => 'Partner name is required.',
                'partner_name.max' => 'Partner name must not exceed 100 characters.',
                'partner_name.unique' => 'This partner name already exists.',
            ]);

            $supportPartner->update($validated);

            return back()
                ->with('message', 'Support partner updated successfully.')
                ->with('alert-type', 'success')
                ->with('scrollToSection', 'support-partners');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors(), 'editPartner')
                ->withInput()
                ->with('openModal', 'editPartner')
                ->with('editPartnerId', $supportPartner->id)
                ->with('scrollToSection', 'support-partners');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupportPartner $supportPartner)
    {
        $supportPartner->delete();

        return back()
            ->with('message', 'Support partner deleted successfully.')
            ->with('alert-type', 'success')
            ->with('scrollToSection', 'support-partners');
    }
}
