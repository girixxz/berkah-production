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
            // 1. Status is NOT 'done' (exclude completed tasks)
            // 2. Deadline is today, OR
            // 3. Status is 'in_progress' or 'pending', OR
            // 4. Start date is today or before today and deadline is today or after today
            $orderStages = OrderStage::with(['order.invoice', 'order.productCategory', 'order.customer'])
                ->where('stage_id', $stage->id)
                ->where('status', '!=', 'done') // Exclude done tasks
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

            // Calculate total pcs (sum of all order total_qty)
            $totalPcs = $orderStages->sum(function ($orderStage) {
                return $orderStage->order->total_qty ?? 0;
            });

            return [
                'stage' => $stage,
                'order_stages' => $orderStages,
                'total_count' => $orderStages->count(),
                'total_pcs' => $totalPcs,
                'visible_count' => min(5, $orderStages->count()),
                'remaining_count' => max(0, $orderStages->count() - 5),
            ];
        });

        return view('pages.karyawan.task', compact('stagesWithOrders'));
    }

    /**
     * Mark order stage as done
     */
    public function markAsDone(Request $request)
    {
        $validated = $request->validate([
            'order_stage_id' => 'required|exists:order_stages,id',
        ]);

        try {
            $orderStage = OrderStage::findOrFail($validated['order_stage_id']);
            
            // Update status to done
            $orderStage->update(['status' => 'done']);
            
            // Auto-check if all stages are done and update order production status
            $order = $orderStage->order;
            $order->refresh(); // Refresh to get latest stage data
            $statusChanged = $order->checkAndUpdateProductionStatus();
            
            $message = 'Task marked as done successfully!';
            if ($statusChanged && $order->production_status === 'finished') {
                $message = '🎉 All tasks completed! Order has been marked as Finished.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'production_status_changed' => $statusChanged,
                'new_production_status' => $order->production_status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark task as done: ' . $e->getMessage()
            ], 500);
        }
    }
}
