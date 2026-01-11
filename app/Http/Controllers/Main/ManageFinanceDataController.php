<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\MaterialSupplier;
use App\Models\SupportPartner;
use Illuminate\Http\Request;

class ManageFinanceDataController extends Controller
{
    /**
     * Display the finance data management page.
     */
    public function index(Request $request)
    {
        // Get per_page values for each section with validation
        $perPageSupplier = $request->input('per_page_supplier', 5);
        $perPageSupplier = in_array($perPageSupplier, [5, 10, 15, 20, 25]) ? $perPageSupplier : 5;
        
        $perPagePartner = $request->input('per_page_partner', 5);
        $perPagePartner = in_array($perPagePartner, [5, 10, 15, 20, 25]) ? $perPagePartner : 5;

        $materialSuppliers = MaterialSupplier::orderBy('supplier_name')
            ->paginate($perPageSupplier, ['*'], 'suppliers_page');
        
        $supportPartners = SupportPartner::orderBy('partner_name')
            ->paginate($perPagePartner, ['*'], 'partners_page');

        // Get all data for search functionality
        $allMaterialSuppliers = MaterialSupplier::orderBy('supplier_name')->get();
        $allSupportPartners = SupportPartner::orderBy('partner_name')->get();

        return view('pages.owner.manage-data.finance', compact(
            'materialSuppliers',
            'supportPartners',
            'allMaterialSuppliers',
            'allSupportPartners'
        ));
    }
}
