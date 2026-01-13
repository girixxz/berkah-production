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

            // Auto-generate sort_order: max + 1
            $maxSortOrder = SupportPartner::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;

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
            // Get total count
            $totalPartners = SupportPartner::count();
            
            $validated = $request->validateWithBag('editPartner', [
                'partner_name' => 'required|string|max:100|unique:support_partners,partner_name,' . $supportPartner->id,
                'notes' => 'nullable|string',
                'sort_order' => 'required|integer|min:1|max:' . $totalPartners,
            ], [
                'partner_name.required' => 'Partner name is required.',
                'partner_name.max' => 'Partner name must not exceed 100 characters.',
                'partner_name.unique' => 'This partner name already exists.',
                'sort_order.required' => 'Sort order is required.',
                'sort_order.integer' => 'Sort order must be an integer.',
                'sort_order.min' => 'Sort order must be at least 1.',
                'sort_order.max' => 'Sort order cannot exceed total partners (' . $totalPartners . ').',
            ]);

            $oldSortOrder = $supportPartner->sort_order;
            $newSortOrder = $validated['sort_order'];

            // Handle sort order adjustment
            if ($oldSortOrder !== $newSortOrder) {
                if ($newSortOrder < $oldSortOrder) {
                    // Moving UP
                    SupportPartner::where('id', '!=', $supportPartner->id)
                        ->where('sort_order', '>=', $newSortOrder)
                        ->where('sort_order', '<', $oldSortOrder)
                        ->increment('sort_order');
                } else {
                    // Moving DOWN
                    SupportPartner::where('id', '!=', $supportPartner->id)
                        ->where('sort_order', '>', $oldSortOrder)
                        ->where('sort_order', '<=', $newSortOrder)
                        ->decrement('sort_order');
                }
            }

            // Update the partner
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
