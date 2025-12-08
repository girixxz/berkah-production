<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CuttingPattern;
use App\Models\ChainCloth;
use App\Models\RibSize;
use App\Models\PrintInk;
use App\Models\Finishing;
use App\Models\NeckOverdeck;
use App\Models\UnderarmOverdeck;
use App\Models\SideSplit;
use App\Models\SewingLabel;
use App\Models\PlasticPacking;
use App\Models\Sticker;

class ManageWorkOrderDataController extends Controller
{
    public function index(Request $request)
    {
        // Get per_page values for each section with validation
        $perPageCuttingPattern = $request->input('per_page_cutting_pattern', 5);
        $perPageCuttingPattern = in_array($perPageCuttingPattern, [5, 10, 15, 20, 25]) ? $perPageCuttingPattern : 5;
        
        $perPageChainCloth = $request->input('per_page_chain_cloth', 5);
        $perPageChainCloth = in_array($perPageChainCloth, [5, 10, 15, 20, 25]) ? $perPageChainCloth : 5;
        
        $perPageRibSize = $request->input('per_page_rib_size', 5);
        $perPageRibSize = in_array($perPageRibSize, [5, 10, 15, 20, 25]) ? $perPageRibSize : 5;
        
        $perPagePrintInk = $request->input('per_page_print_ink', 5);
        $perPagePrintInk = in_array($perPagePrintInk, [5, 10, 15, 20, 25]) ? $perPagePrintInk : 5;
        
        $perPageFinishing = $request->input('per_page_finishing', 5);
        $perPageFinishing = in_array($perPageFinishing, [5, 10, 15, 20, 25]) ? $perPageFinishing : 5;
        
        $perPageNeckOverdeck = $request->input('per_page_neck_overdeck', 5);
        $perPageNeckOverdeck = in_array($perPageNeckOverdeck, [5, 10, 15, 20, 25]) ? $perPageNeckOverdeck : 5;
        
        $perPageUnderarmOverdeck = $request->input('per_page_underarm_overdeck', 5);
        $perPageUnderarmOverdeck = in_array($perPageUnderarmOverdeck, [5, 10, 15, 20, 25]) ? $perPageUnderarmOverdeck : 5;
        
        $perPageSideSplit = $request->input('per_page_side_split', 5);
        $perPageSideSplit = in_array($perPageSideSplit, [5, 10, 15, 20, 25]) ? $perPageSideSplit : 5;
        
        $perPageSewingLabel = $request->input('per_page_sewing_label', 5);
        $perPageSewingLabel = in_array($perPageSewingLabel, [5, 10, 15, 20, 25]) ? $perPageSewingLabel : 5;
        
        $perPagePlasticPacking = $request->input('per_page_plastic_packing', 5);
        $perPagePlasticPacking = in_array($perPagePlasticPacking, [5, 10, 15, 20, 25]) ? $perPagePlasticPacking : 5;
        
        $perPageSticker = $request->input('per_page_sticker', 5);
        $perPageSticker = in_array($perPageSticker, [5, 10, 15, 20, 25]) ? $perPageSticker : 5;
        
        // Cutting Data
        $cuttingPatterns = CuttingPattern::orderBy('sort_order', 'asc')->paginate($perPageCuttingPattern, ['*'], 'cutting_pattern_page');
        $chainCloths = ChainCloth::orderBy('sort_order', 'asc')->paginate($perPageChainCloth, ['*'], 'chain_cloth_page');
        $ribSizes = RibSize::orderBy('sort_order', 'asc')->paginate($perPageRibSize, ['*'], 'rib_size_page');

        // Printing Data
        $printInks = PrintInk::orderBy('sort_order', 'asc')->paginate($perPagePrintInk, ['*'], 'print_ink_page');
        $finishings = Finishing::orderBy('sort_order', 'asc')->paginate($perPageFinishing, ['*'], 'finishing_page');

        // Sewing Data
        $neckOverdecks = NeckOverdeck::orderBy('sort_order', 'asc')->paginate($perPageNeckOverdeck, ['*'], 'neck_overdeck_page');
        $underarmOverdecks = UnderarmOverdeck::orderBy('sort_order', 'asc')->paginate($perPageUnderarmOverdeck, ['*'], 'underarm_overdeck_page');
        $sideSplits = SideSplit::orderBy('sort_order', 'asc')->paginate($perPageSideSplit, ['*'], 'side_split_page');
        $sewingLabels = SewingLabel::orderBy('sort_order', 'asc')->paginate($perPageSewingLabel, ['*'], 'sewing_label_page');

        // Packing Data
        $plasticPackings = PlasticPacking::orderBy('sort_order', 'asc')->paginate($perPagePlasticPacking, ['*'], 'plastic_packing_page');
        $stickers = Sticker::orderBy('sort_order', 'asc')->paginate($perPageSticker, ['*'], 'sticker_page');

        return view('pages.owner.manage-data.work-orders', compact(
            'cuttingPatterns',
            'chainCloths',
            'ribSizes',
            'printInks',
            'finishings',
            'neckOverdecks',
            'underarmOverdecks',
            'sideSplits',
            'sewingLabels',
            'plasticPackings',
            'stickers'
        ));
    }
}
