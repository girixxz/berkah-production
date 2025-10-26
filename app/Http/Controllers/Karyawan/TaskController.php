<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\ProductionStage;
use App\Models\OrderStage;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        // Get all production stages in order by ID (or created_at)
        $productionStages = ProductionStage::orderBy('id')->get();

        // Get today's date range
        $today = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        // For each stage, get order stages that have deadline today or are in progress
        $stagesWithOrders = $productionStages->map(function ($stage) use ($today, $todayEnd) {
            // Get order stages for this production stage where:
            // 1. Deadline is today, OR
            // 2. Status is 'in_progress' or 'pending', OR
            // 3. Start date is today or before today and deadline is today or after today
            $orderStages = OrderStage::with(['order.invoice', 'order.productCategory', 'order.customer'])
                ->where('stage_id', $stage->id)
                ->whereHas('order', function ($query) {
                    $query->where('production_status', '!=', 'cancelled')
                          ->where('production_status', '!=', 'finished');
                })
                ->where(function ($query) use ($today, $todayEnd) {
                    $query->whereBetween('deadline', [$today, $todayEnd])
                          ->orWhere(function ($q) use ($today) {
                              $q->where('status', '!=', 'done')
                                ->where('start_date', '<=', $today)
                                ->where('deadline', '>=', $today);
                          });
                })
                ->orderBy('deadline', 'asc')
                ->get();

            return [
                'stage' => $stage,
                'order_stages' => $orderStages,
                'total_count' => $orderStages->count(),
                'visible_count' => min(5, $orderStages->count()),
                'remaining_count' => max(0, $orderStages->count() - 5),
            ];
        });

        return view('pages.karyawan.task', compact('stagesWithOrders'));
    }
}
