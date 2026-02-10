<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\MaterialSupplier;
use App\Models\SupportPartner;
use App\Models\OperationalList;
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

        $perPageFixCost1 = $request->input('per_page_fix_cost_1', 10);
        $perPageFixCost1 = in_array($perPageFixCost1, [5, 10, 15, 20, 25]) ? $perPageFixCost1 : 10;

        $perPageFixCost2 = $request->input('per_page_fix_cost_2', 10);
        $perPageFixCost2 = in_array($perPageFixCost2, [5, 10, 15, 20, 25]) ? $perPageFixCost2 : 10;

        $perPagePrintingSupply = $request->input('per_page_printing_supply', 10);
        $perPagePrintingSupply = in_array($perPagePrintingSupply, [5, 10, 15, 20, 25]) ? $perPagePrintingSupply : 10;

        $materialSuppliers = MaterialSupplier::orderBy('sort_order')
            ->paginate($perPageSupplier, ['*'], 'suppliers_page');
        
        $supportPartners = SupportPartner::orderBy('sort_order')
            ->paginate($perPagePartner, ['*'], 'partners_page');

        // Operational Lists - separated by category
        $fixCostLists1 = OperationalList::where('category', 'fix_cost_1')
            ->orderBy('sort_order')
            ->paginate($perPageFixCost1, ['*'], 'fix_cost_1_page');

        $fixCostLists2 = OperationalList::where('category', 'fix_cost_2')
            ->orderBy('sort_order')
            ->paginate($perPageFixCost2, ['*'], 'fix_cost_2_page');

        $printingSupplyLists = OperationalList::where('category', 'printing_supply')
            ->orderBy('sort_order')
            ->paginate($perPagePrintingSupply, ['*'], 'printing_supply_page');

        // Get all data for search functionality
        $allMaterialSuppliers = MaterialSupplier::orderBy('sort_order')->get();
        $allSupportPartners = SupportPartner::orderBy('sort_order')->get();
        $allFixCostLists1 = OperationalList::where('category', 'fix_cost_1')->orderBy('sort_order')->get();
        $allFixCostLists2 = OperationalList::where('category', 'fix_cost_2')->orderBy('sort_order')->get();
        $allPrintingSupplyLists = OperationalList::where('category', 'printing_supply')->orderBy('sort_order')->get();

        return view('pages.owner.manage-data.finance', compact(
            'materialSuppliers',
            'supportPartners',
            'fixCostLists1',
            'fixCostLists2',
            'printingSupplyLists',
            'allMaterialSuppliers',
            'allSupportPartners',
            'allFixCostLists1',
            'allFixCostLists2',
            'allPrintingSupplyLists'
        ));
    }
}
