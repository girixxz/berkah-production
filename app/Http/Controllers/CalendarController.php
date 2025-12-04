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

        // Get filter value (default to 'create')
        // Format: 'create', 'deadline', or 'stage_{id}'
        $filter = $request->input('filter', 'create');
        
        // Determine mode and stage based on filter
        if ($filter === 'create' || $filter === 'deadline') {
            $mode = $filter;
            $selectedStageId = $productionStages->first()?->id;
        } else {
            $mode = 'stage';
            $selectedStageId = (int) str_replace('stage_', '', $filter);
        }
        
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

        // Get data based on mode
        $orderStages = collect();
        
        if ($mode === 'deadline' || $mode === 'create') {
            // Order by Create/Deadline mode: Show orders on specific date only (not span)
            $orders = \App\Models\Order::with(['orderStages', 'invoice', 'productCategory', 'customer'])
                ->where('production_status', '!=', 'cancelled')
                ->where('production_status', '!=', 'finished')
                ->whereHas('orderStages')
                ->get();
            
            // For each order, create pseudo OrderStage
            foreach ($orders as $order) {
                $firstStage = $order->orderStages->first();
                if ($firstStage) {
                    $pseudo = new OrderStage();
                    $pseudo->id = $firstStage->id;
                    $pseudo->order_id = $order->id;
                    $pseudo->stage_id = $firstStage->stage_id;
                    $pseudo->status = $firstStage->status;
                    $pseudo->start_date = null; // Not used in create/deadline mode
                    $pseudo->deadline = null; // Not used in create/deadline mode
                    
                    if ($mode === 'create') {
                        // Create mode: show on order_date only
                        $pseudo->target_date = $order->order_date;
                    } else {
                        // Deadline mode: show on order->deadline only
                        $pseudo->target_date = $order->deadline;
                    }
                    
                    $pseudo->setRelation('order', $order);
                    
                    // Only include if target_date is within calendar range and not null
                    if ($pseudo->target_date) {
                        $targetDate = Carbon::parse($pseudo->target_date);
                        if ($targetDate >= $startOfCalendar && $targetDate <= $endOfCalendar) {
                            $orderStages->push($pseudo);
                        }
                    }
                }
            }
        } else {
            // Stage mode: Show specific stage's tasks from OrderStage (PM setup)
            $orderStages = OrderStage::with(['order.invoice', 'order.productCategory', 'order.customer'])
                ->where('stage_id', $selectedStageId)
                ->whereHas('order', function ($query) {
                    $query->where('production_status', '!=', 'cancelled')
                          ->where('production_status', '!=', 'finished');
                })
                ->where(function ($query) use ($startOfCalendar, $endOfCalendar) {
                    $query->where(function ($q) use ($startOfCalendar, $endOfCalendar) {
                        $q->where('start_date', '<=', $endOfCalendar)
                          ->where('deadline', '>=', $startOfCalendar);
                    });
                })
                ->orderBy('deadline', 'asc')
                ->get();
        }

        // Group order stages by date
        $tasksByDate = [];
        foreach ($orderStages as $orderStage) {
            if ($mode === 'create' || $mode === 'deadline') {
                // Order by Create/Deadline: Show on specific date only (target_date)
                if (isset($orderStage->target_date) && $orderStage->target_date) {
                    $dateKey = Carbon::parse($orderStage->target_date)->format('Y-m-d');
                    if (!isset($tasksByDate[$dateKey])) {
                        $tasksByDate[$dateKey] = [];
                    }
                    $tasksByDate[$dateKey][] = $orderStage;
                }
            } else {
                // Stage mode: Show bubble every day from start_date to deadline
                if ($orderStage->start_date && $orderStage->deadline) {
                    $taskStart = Carbon::parse($orderStage->start_date)->startOfDay();
                    $taskEnd = Carbon::parse($orderStage->deadline)->endOfDay();
                    
                    $currentDay = $startOfCalendar->copy();
                    while ($currentDay <= $endOfCalendar) {
                        if ($currentDay >= $taskStart && $currentDay <= $taskEnd) {
                            $dateKey = $currentDay->format('Y-m-d');
                            if (!isset($tasksByDate[$dateKey])) {
                                $tasksByDate[$dateKey] = [];
                            }
                            $tasksByDate[$dateKey][] = $orderStage;
                        }
                        $currentDay->addDay();
                    }
                } elseif ($orderStage->deadline) {
                    // Fallback: only deadline exists
                    $dateKey = Carbon::parse($orderStage->deadline)->format('Y-m-d');
                    if (!isset($tasksByDate[$dateKey])) {
                        $tasksByDate[$dateKey] = [];
                    }
                    $tasksByDate[$dateKey][] = $orderStage;
                }
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

        // If AJAX request, return only the calendar section
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('pages.calendar', compact(
                'productionStages',
                'selectedStage',
                'selectedStageId',
                'filter',
                'mode',
                'currentDate',
                'calendar',
                'prevMonth',
                'nextMonth'
            ));
        }

        return view('pages.calendar', compact(
            'productionStages',
            'selectedStage',
            'selectedStageId',
            'filter',
            'mode',
            'currentDate',
            'calendar',
            'prevMonth',
            'nextMonth'
        ));
    }
}
