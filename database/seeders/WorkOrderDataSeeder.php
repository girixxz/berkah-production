<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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

class WorkOrderDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cutting Patterns
        $cuttingPatterns = [
            ['name' => 'Custom', 'sort_order' => 1],
            ['name' => 'Regular Cut', 'sort_order' => 2],
            ['name' => 'Oversize Cut', 'sort_order' => 3],
            ['name' => 'Slim Fit Cut', 'sort_order' => 4],
            ['name' => 'Boxy Cut', 'sort_order' => 5],
            ['name' => 'Longline Cut', 'sort_order' => 6],
        ];
        foreach ($cuttingPatterns as $pattern) {
            CuttingPattern::updateOrCreate(['name' => $pattern['name']], $pattern);
        }

        // Chain Cloths
        $chainCloths = [
            ['name' => 'Menyesuaikan', 'sort_order' => 1],
            ['name' => 'Cotton Chain', 'sort_order' => 2],
            ['name' => 'Polyester Chain', 'sort_order' => 3],
            ['name' => 'Rayon Chain', 'sort_order' => 4],
            ['name' => 'Spandex Chain', 'sort_order' => 5],
            ['name' => 'Bamboo Chain', 'sort_order' => 6],
        ];
        foreach ($chainCloths as $cloth) {
            ChainCloth::updateOrCreate(['name' => $cloth['name']], $cloth);
        }

        // Rib Sizes
        $ribSizes = [
            ['name' => '2 cm', 'sort_order' => 1],
            ['name' => '1x1 Rib (Small)', 'sort_order' => 2],
            ['name' => '2x2 Rib (Medium)', 'sort_order' => 3],
            ['name' => '3x3 Rib (Large)', 'sort_order' => 4],
            ['name' => '1x2 Rib (Mixed)', 'sort_order' => 5],
            ['name' => 'No Rib', 'sort_order' => 6],
        ];
        foreach ($ribSizes as $size) {
            RibSize::updateOrCreate(['name' => $size['name']], $size);
        }

        // Print Inks
        $printInks = [
            ['name' => 'Plastisol', 'sort_order' => 1],
            ['name' => 'Water-Based Ink', 'sort_order' => 2],
            ['name' => 'Discharge Ink', 'sort_order' => 3],
            ['name' => 'Puff Ink', 'sort_order' => 4],
            ['name' => 'Metallic Ink', 'sort_order' => 5],
            ['name' => 'Glow in the Dark', 'sort_order' => 6],
            ['name' => 'Reflective Ink', 'sort_order' => 7],
        ];
        foreach ($printInks as $ink) {
            PrintInk::updateOrCreate(['name' => $ink['name']], $ink);
        }

        // Finishings
        $finishings = [
            ['name' => 'Conveyor', 'sort_order' => 1],
            ['name' => 'Standard Finishing', 'sort_order' => 2],
            ['name' => 'Enzyme Wash', 'sort_order' => 3],
            ['name' => 'Stone Wash', 'sort_order' => 4],
            ['name' => 'Garment Dye', 'sort_order' => 5],
            ['name' => 'Silicone Softener', 'sort_order' => 6],
        ];
        foreach ($finishings as $finish) {
            Finishing::updateOrCreate(['name' => $finish['name']], $finish);
        }

        // Neck Overdecks
        $neckOverdecks = [
            ['name' => 'Benang 2', 'sort_order' => 1],
            ['name' => 'Standard Neck Overdeck', 'sort_order' => 2],
            ['name' => 'Double Stitch Neck', 'sort_order' => 3],
            ['name' => 'Cover Stitch Neck', 'sort_order' => 4],
            ['name' => 'Flatlock Neck', 'sort_order' => 5],
            ['name' => 'V-Neck Overdeck', 'sort_order' => 6],
        ];
        foreach ($neckOverdecks as $neck) {
            NeckOverdeck::updateOrCreate(['name' => $neck['name']], $neck);
        }

        // Underarm Overdecks
        $underarmOverdecks = [
            ['name' => 'Benang 2', 'sort_order' => 1],
            ['name' => 'Standard Underarm', 'sort_order' => 2],
            ['name' => 'Reinforced Underarm', 'sort_order' => 3],
            ['name' => 'Flatlock Underarm', 'sort_order' => 4],
            ['name' => 'Cover Stitch Underarm', 'sort_order' => 5],
            ['name' => 'No Overdeck', 'sort_order' => 6],
        ];
        foreach ($underarmOverdecks as $underarm) {
            UnderarmOverdeck::updateOrCreate(['name' => $underarm['name']], $underarm);
        }

        // Side Splits
        $sideSplits = [
            ['name' => 'Tidak', 'sort_order' => 1],
            ['name' => 'Small Side Split (5cm)', 'sort_order' => 2],
            ['name' => 'Medium Side Split (10cm)', 'sort_order' => 3],
            ['name' => 'Large Side Split (15cm)', 'sort_order' => 4],
            ['name' => 'Extra Large Side Split (20cm)', 'sort_order' => 5],
            ['name' => 'Full Length Side Split', 'sort_order' => 6],
        ];
        foreach ($sideSplits as $split) {
            SideSplit::updateOrCreate(['name' => $split['name']], $split);
        }

        // Sewing Labels
        $sewingLabels = [
            ['name' => 'Tidak ada', 'sort_order' => 1],
            ['name' => 'Woven Label - Standard', 'sort_order' => 2],
            ['name' => 'Woven Label - Premium', 'sort_order' => 3],
            ['name' => 'Printed Label - Cotton', 'sort_order' => 4],
            ['name' => 'Heat Transfer Label', 'sort_order' => 5],
            ['name' => 'Leather Patch Label', 'sort_order' => 6],
        ];
        foreach ($sewingLabels as $label) {
            SewingLabel::updateOrCreate(['name' => $label['name']], $label);
        }

        // Plastic Packings
        $plasticPackings = [
            ['name' => 'OPP 25', 'sort_order' => 1],
            ['name' => 'Standard Polybag', 'sort_order' => 2],
            ['name' => 'Premium Polybag with Header', 'sort_order' => 3],
            ['name' => 'Ziplock Polybag', 'sort_order' => 4],
            ['name' => 'Transparent PP Bag', 'sort_order' => 5],
            ['name' => 'Biodegradable Polybag', 'sort_order' => 6],
        ];
        foreach ($plasticPackings as $packing) {
            PlasticPacking::updateOrCreate(['name' => $packing['name']], $packing);
        }

        // Stickers
        $stickers = [
            ['name' => 'Tidak ada', 'sort_order' => 1],
            ['name' => 'Size Sticker - Standard', 'sort_order' => 2],
            ['name' => 'Size Sticker - Premium', 'sort_order' => 3],
            ['name' => 'Barcode Sticker', 'sort_order' => 4],
            ['name' => 'Price Tag Sticker', 'sort_order' => 5],
            ['name' => 'Brand Logo Sticker', 'sort_order' => 6],
        ];
        foreach ($stickers as $sticker) {
            Sticker::updateOrCreate(['name' => $sticker['name']], $sticker);
        }

        $this->command->info('Work Order Data seeded successfully! Each category now has at least 6 items.');
    }
}
