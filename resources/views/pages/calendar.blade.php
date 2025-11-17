<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Production Calendar - {{ config('app.name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Fixed height for calendar cells */
        .calendar-cell {
            height: 180px;
            display: flex;
            flex-direction: column;
        }
        
        /* Horizontal scroll when screen is smaller than calendar */
        .calendar-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .calendar-grid {
            min-width: 1200px; /* Force horizontal scroll on smaller screens */
        }
        
        @media (max-width: 1280px) {
            .calendar-grid {
                min-width: 1200px;
            }
        }
    </style>
</head>
<body class="bg-gray-light min-h-screen">

    {{-- Root Alpine State --}}
    <div x-data="{
        showModal: false,
        modalDate: '',
        modalTasks: [],
        openModal(date, tasks) {
            this.modalDate = date;
            this.modalTasks = tasks;
            this.showModal = true;
        }
    }">

        {{-- Header --}}
        <div class="p-2">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                {{-- Left Side: Filter Stage --}}
                <div class="flex-1 max-w-md">
                    <form method="GET" action="{{ route('calendar') }}" id="filterForm">
                        <input type="hidden" name="month" value="{{ request('month', $currentDate->month) }}">
                        <input type="hidden" name="year" value="{{ request('year', $currentDate->year) }}">
                        
                        <select name="stage_id" 
                                onchange="document.getElementById('filterForm').submit()"
                                class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            @foreach ($productionStages as $stage)
                                <option value="{{ $stage->id }}" {{ $selectedStageId == $stage->id ? 'selected' : '' }}>
                                    {{ $stage->stage_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                {{-- Right Side: Month Navigation + Reset --}}
                <div class="flex items-center gap-3 flex-wrap justify-end">
                    {{-- Previous Month --}}
                    <a href="{{ route('calendar', ['stage_id' => $selectedStageId, 'month' => $prevMonth->month, 'year' => $prevMonth->year]) }}" 
                       class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>

                    {{-- Current Month/Year Display --}}
                    <div class="px-4 py-2 text-center min-w-[150px]">
                        <span class="text-base font-semibold text-gray-900">
                            {{ $currentDate->format('F Y') }}
                        </span>
                    </div>

                    {{-- Next Month --}}
                    <a href="{{ route('calendar', ['stage_id' => $selectedStageId, 'month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" 
                       class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    {{-- Reset Button --}}
                    <a href="{{ route('calendar', ['stage_id' => $selectedStageId]) }}" 
                       class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                        Reset
                    </a>
                </div>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="calendar-wrapper">
            <div class="calendar-grid overflow-hidden shadow-sm">
                {{-- Calendar Header (Days of Week) --}}
                <div class="grid grid-cols-7 bg-primary-light border-y border-gray-300">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                        <div class="py-3 text-center text-md font-semibold border-r border-gray-300 last:border-r-0">
                            {{ $day }}
                        </div>
                    @endforeach
                </div>

                {{-- Calendar Body (Weeks and Days) --}}
                <div>
                    @foreach ($calendar as $week)
                        <div class="grid grid-cols-7 border-b border-gray-200 last:border-b-0">
                            @foreach ($week as $day)
                                <div class="calendar-cell border-r border-gray-200 last:border-r-0 p-2 {{ !$day['isCurrentMonth'] ? 'bg-gray-50' : 'bg-white' }}">
                                    {{-- Date Number --}}
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium {{ $day['isToday'] ? 'text-primary font-bold' : ($day['isCurrentMonth'] ? 'text-gray-900' : 'text-gray-400') }}">
                                            {{ $day['date']->format('d') }}
                                        </span>
                                    </div>

                                    {{-- Tasks for this day --}}
                                    @if (count($day['tasks']) > 0)
                                        <div class="space-y-1.5 flex-1">
                                            {{-- Show max 3 tasks --}}
                                            @foreach (array_slice($day['tasks'], 0, 3) as $orderStage)
                                                @php
                                                    $status = strtolower($orderStage->status ?? '');
                                                    $isHighPriority = strtolower($orderStage->order->priority ?? '') === 'high';
                                                    $isDone = $status === 'done';
                                                    
                                                    // Set colors based on status and priority
                                                    if ($isDone) {
                                                        // Status: done (gray)
                                                        $bgClass = 'bg-gray-200';
                                                        $borderClass = 'border-gray-400';
                                                        $textClass = 'text-gray-600';
                                                        $separatorClass = 'text-gray-400';
                                                    } elseif ($isHighPriority) {
                                                        // Status: in_progress + Priority: high (red)
                                                        $bgClass = 'bg-red-500';
                                                        $borderClass = 'border-red-600';
                                                        $textClass = 'text-white';
                                                        $separatorClass = 'text-white opacity-70';
                                                    } else {
                                                        // Status: in_progress + Priority: normal (custom yellow #eddfad)
                                                        $bgClass = 'bg-[#eddfad]';
                                                        $borderClass = 'border-[#d4c973]';
                                                        $textClass = 'text-gray-900';
                                                        $separatorClass = 'text-gray-600';
                                                    }
                                                @endphp
                                                <div class="px-2 py-1.5 rounded-lg border {{ $bgClass }} {{ $borderClass }} text-[10px] font-medium">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <div class="flex items-center gap-1 {{ $textClass }} flex-1 min-w-0">
                                                            <span class="font-semibold truncate">
                                                                {{ $orderStage->order->customer->customer_name ?? 'N/A' }}
                                                            </span>
                                                            <span class="{{ $separatorClass }}">•</span>
                                                            <span class="truncate">
                                                                {{ $orderStage->order->productCategory->product_name ?? 'N/A' }}
                                                            </span>
                                                            <span class="{{ $separatorClass }}">•</span>
                                                            <span class="font-medium">
                                                                {{ $orderStage->order->total_qty ?? 0 }}
                                                            </span>
                                                            @if ($isHighPriority && !$isDone)
                                                                <span class="{{ $separatorClass }}">•</span>
                                                                <span class="font-bold italic">HIGH</span>
                                                            @endif
                                                        </div>
                                                        @if ($isDone)
                                                            <span class="text-gray-500 font-bold italic flex-shrink-0">DONE</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- View All button if more than 3 tasks --}}
                                            @if (count($day['tasks']) > 3)
                                                <button type="button"
                                                    @click="openModal('{{ $day['date']->format('l, d F Y') }}', {{ collect($day['tasks'])->map(function ($os) {
                                                        $isHigh = strtolower($os->order->priority ?? '') === 'high';
                                                        return [
                                                            'id' => $os->id,
                                                            'invoice' => $os->order->invoice->invoice_no ?? 'N/A',
                                                            'product' => $os->order->productCategory->product_name ?? 'N/A',
                                                            'customer' => $os->order->customer->customer_name ?? 'N/A',
                                                            'qty' => $os->order->total_qty ?? 0,
                                                            'priority' => $isHigh ? 'high' : 'normal',
                                                            'status' => $os->status,
                                                            'deadline' => $os->deadline ? $os->deadline->format('d M Y') : 'N/A',
                                                        ];
                                                    })->toJson() }})"
                                                    class="w-full text-center text-[10px] font-medium text-primary hover:text-primary-dark transition-colors cursor-pointer border-t border-gray-200 pt-1.5 mt-1">
                                                    View All ({{ count($day['tasks']) - 3 }}+ More)
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Modal - Show All Tasks for Selected Date --}}
        <div x-show="showModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.away="showModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-2xl">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between p-5 border-b border-gray-200">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Tasks for <span x-text="modalDate"></span></h3>
                            <p class="text-sm text-gray-500 mt-1" x-text="`${modalTasks.length} ${modalTasks.length === 1 ? 'task' : 'tasks'}`"></p>
                        </div>
                        <button @click="showModal = false"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-5 max-h-[60vh] overflow-y-auto">
                        <div class="space-y-3">
                            <template x-for="(task, index) in modalTasks" :key="index">
                                <div class="px-4 py-3 rounded-lg border"
                                    :style="task.status !== 'done' && task.priority !== 'high' ? 'background-color: #eddfad; border-color: #d4c973;' : ''"
                                    :class="{
                                        'bg-gray-200 border-gray-400': task.status === 'done',
                                        'bg-red-500 border-red-600': task.status !== 'done' && task.priority === 'high'
                                    }">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 text-xs font-medium flex-1 min-w-0"
                                            :class="{
                                                'text-gray-600': task.status === 'done',
                                                'text-white': task.status !== 'done' && task.priority === 'high',
                                                'text-gray-900': task.status !== 'done' && task.priority !== 'high'
                                            }">
                                            <span class="font-semibold truncate" x-text="task.customer"></span>
                                            <span :class="task.status === 'done' ? 'text-gray-400' : (task.priority === 'high' && task.status !== 'done' ? 'text-white opacity-70' : 'text-gray-400')">•</span>
                                            <span class="truncate" x-text="task.product"></span>
                                            <span :class="task.status === 'done' ? 'text-gray-400' : (task.priority === 'high' && task.status !== 'done' ? 'text-white opacity-70' : 'text-gray-400')">•</span>
                                            <span class="font-medium" x-text="task.qty || 0"></span>
                                            <template x-if="task.priority === 'high' && task.status !== 'done'">
                                                <span>
                                                    <span class="text-white opacity-70">•</span>
                                                    <span class="font-bold italic">HIGH</span>
                                                </span>
                                            </template>
                                        </div>
                                        <template x-if="task.status === 'done'">
                                            <span class="text-gray-500 font-bold italic text-xs flex-shrink-0">DONE</span>
                                        </template>
                                    </div>
                                    <div class="mt-2 text-xs"
                                        :class="task.status === 'done' ? 'text-gray-500' : (task.priority === 'high' && task.status !== 'done' ? 'text-white opacity-80' : 'text-gray-500')">
                                        <span>Deadline: </span>
                                        <span x-text="task.deadline"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex justify-end gap-3 p-5 border-t border-gray-200">
                        <button @click="showModal = false"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
    {{-- Toast Notification Component --}}
    <x-toast-notif />
    
</body>
</html>
