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
        CuttingPattern::updateOrCreate(
            ['id' => 2],
            ['name' => 'Custom']
        );

        // Chain Cloths
        ChainCloth::updateOrCreate(
            ['id' => 1],
            ['name' => 'Menyesuaikan']
        );

        // Rib Sizes
        RibSize::updateOrCreate(
            ['id' => 1],
            ['name' => '2 cm']
        );

        // Print Inks
        PrintInk::updateOrCreate(
            ['id' => 1],
            ['name' => 'Plastisol']
        );

        // Finishings
        Finishing::updateOrCreate(
            ['id' => 1],
            ['name' => 'Conveyor']
        );

        // Neck Overdecks
        NeckOverdeck::updateOrCreate(
            ['id' => 1],
            ['name' => 'Benang 2']
        );

        // Underarm Overdecks
        UnderarmOverdeck::updateOrCreate(
            ['id' => 1],
            ['name' => 'Benang 2']
        );

        // Side Splits
        SideSplit::updateOrCreate(
            ['id' => 1],
            ['name' => 'Tidak']
        );

        // Sewing Labels
        SewingLabel::updateOrCreate(
            ['id' => 1],
            ['name' => 'Tidak ada']
        );

        // Plastic Packings
        PlasticPacking::updateOrCreate(
            ['id' => 1],
            ['name' => 'OPP 25']
        );

        // Stickers
        Sticker::updateOrCreate(
            ['id' => 1],
            ['name' => 'Tidak ada']
        );
    }
}
