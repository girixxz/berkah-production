<?php

namespace App\Http\Controllers;

use App\Models\ProductionStage;
use App\Models\OrderStage;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Display the production calendar
     */
    public function index(Request $request)
    {
        // Get all production stages
        $productionStages = ProductionStage::orderBy('id')->get();

        // Get selected stage (default to first stage)
        $selectedStageId = $request->input('stage_id', $productionStages->first()?->id);
        $selectedStage = ProductionStage::find($selectedStageId);

        // Get month and year from request (default to current month/year)
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Create Carbon instance for the selected month
        $currentDate = Carbon::create($year, $month, 1);
        
        // Get first day of month and last day of month
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        // Get the first day to display (previous month if needed to fill calendar grid)
        $startOfCalendar = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        
        // Get the last day to display (next month if needed to fill calendar grid)
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        // Get all order stages for the selected stage in the date range
        $orderStages = OrderStage::with(['order.invoice', 'order.productCategory', 'order.customer'])
            ->where('stage_id', $selectedStageId)
            // Include all statuses (pending, in_progress, done)
            ->whereHas('order', function ($query) {
                $query->where('production_status', '!=', 'cancelled')
                      ->where('production_status', '!=', 'finished');
            })
            ->where(function ($query) use ($startOfCalendar, $endOfCalendar) {
                // Get tasks where deadline is in range OR task is ongoing during the range
                $query->whereBetween('deadline', [$startOfCalendar, $endOfCalendar])
                      ->orWhere(function ($q) use ($startOfCalendar, $endOfCalendar) {
                          $q->where('start_date', '<=', $endOfCalendar)
                            ->where('deadline', '>=', $startOfCalendar);
                      });
            })
            ->orderBy('deadline', 'asc')
            ->get();

        // Group order stages by date
        $tasksByDate = [];
        foreach ($orderStages as $orderStage) {
            // If task has start_date and deadline, add it to all dates in between
            if ($orderStage->start_date && $orderStage->deadline) {
                $taskStart = Carbon::parse($orderStage->start_date)->startOfDay();
                $taskEnd = Carbon::parse($orderStage->deadline)->endOfDay();
                
                // Loop through each date in the calendar range
                $currentDay = $startOfCalendar->copy();
                while ($currentDay <= $endOfCalendar) {
                    // If current day is within task range, add it
                    if ($currentDay >= $taskStart && $currentDay <= $taskEnd) {
                        $dateKey = $currentDay->format('Y-m-d');
                        if (!isset($tasksByDate[$dateKey])) {
                            $tasksByDate[$dateKey] = [];
                        }
                        $tasksByDate[$dateKey][] = $orderStage;
                    }
                    $currentDay->addDay();
                }
            } else if ($orderStage->deadline) {
                // If only deadline exists, add to that date
                $dateKey = Carbon::parse($orderStage->deadline)->format('Y-m-d');
                if (!isset($tasksByDate[$dateKey])) {
                    $tasksByDate[$dateKey] = [];
                }
                $tasksByDate[$dateKey][] = $orderStage;
            }
        }

        // Build calendar grid (array of weeks, each week has 7 days)
        $calendar = [];
        $currentDay = $startOfCalendar->copy();
        
        while ($currentDay <= $endOfCalendar) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateKey = $currentDay->format('Y-m-d');
                $week[] = [
                    'date' => $currentDay->copy(),
                    'isCurrentMonth' => $currentDay->month == $month,
                    'isToday' => $currentDay->isToday(),
                    'tasks' => $tasksByDate[$dateKey] ?? [],
                ];
                $currentDay->addDay();
            }
            $calendar[] = $week;
        }

        // Get previous and next month/year
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        return view('pages.calendar', compact(
            'productionStages',
            'selectedStage',
            'selectedStageId',
            'currentDate',
            'calendar',
            'prevMonth',
            'nextMonth'
        ));
    }
}
