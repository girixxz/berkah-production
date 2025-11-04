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
    public function index()
    {
        // Cutting Data
        $cuttingPatterns = CuttingPattern::paginate(5, ['*'], 'cutting_pattern_page');
        $chainCloths = ChainCloth::paginate(5, ['*'], 'chain_cloth_page');
        $ribSizes = RibSize::paginate(5, ['*'], 'rib_size_page');

        // Printing Data
        $printInks = PrintInk::paginate(5, ['*'], 'print_ink_page');
        $finishings = Finishing::paginate(5, ['*'], 'finishing_page');

        // Sewing Data
        $neckOverdecks = NeckOverdeck::paginate(5, ['*'], 'neck_overdeck_page');
        $underarmOverdecks = UnderarmOverdeck::paginate(5, ['*'], 'underarm_overdeck_page');
        $sideSplits = SideSplit::paginate(5, ['*'], 'side_split_page');
        $sewingLabels = SewingLabel::paginate(5, ['*'], 'sewing_label_page');

        // Packing Data
        $plasticPackings = PlasticPacking::paginate(5, ['*'], 'plastic_packing_page');
        $stickers = Sticker::paginate(5, ['*'], 'sticker_page');

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
