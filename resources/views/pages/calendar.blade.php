<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Production Calendar - {{ config('app.name') }}</title>
    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Calendar cells will stretch to fill available space */
        .calendar-cell {
            min-height: 100px;
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
<body class="bg-gray-light overflow-hidden" style="height: 100dvh; min-height: -webkit-fill-available;">

    {{-- Root Alpine State --}}
    <div x-data="{
        showModal: false,
        modalDate: '',
        modalTasks: [],
        isLoading: false,
        selectedFilter: '{{ $filter ?? 'create' }}',
        // Select dropdown state
        selectOpen: false,
        selectOptions: [
            { value: 'create', name: 'Order by Create' },
            { value: 'deadline', name: 'Order by Deadline' },
            @foreach ($productionStages as $stage)
                { value: 'stage_{{ $stage->id }}', name: '{{ $stage->stage_name }}' },
            @endforeach
        ],
        selectSelected: null,
        init() {
            // Initialize selected option
            this.selectSelected = this.selectOptions.find(o => o.value === this.selectedFilter) || null;
        },
        selectOption(option) {
            this.selectSelected = option;
            this.selectedFilter = option.value;
            this.selectOpen = false;
            // Trigger AJAX filter
            this.applyFilter(option.value);
        },
        applyFilter(filterValue) {
            this.isLoading = true;
            this.selectedFilter = filterValue;
            
            // Build URL with query params
            const params = new URLSearchParams();
            params.set('filter', filterValue);
            params.set('month', '{{ request('month', $currentDate->month) }}');
            params.set('year', '{{ request('year', $currentDate->year) }}');
            
            const url = '{{ route('calendar') }}?' + params.toString();
            
            // Fetch content via AJAX with loading bar
            NProgress.start();
            
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newSection = doc.getElementById('calendar-section');
                
                if (newSection) {
                    document.getElementById('calendar-section').innerHTML = newSection.innerHTML;
                }
                
                this.isLoading = false;
                NProgress.done();
            })
            .catch(error => {
                console.error('Error:', error);
                this.isLoading = false;
                NProgress.done();
            });
        },
        openModal(date, tasks) {
            this.modalDate = date;
            this.modalTasks = tasks;
            this.showModal = true;
        }
    }" class="h-full flex flex-col">

        {{-- Header --}}
        <div class="p-2 flex-shrink-0 bg-white border-b border-gray-200">
            <div class="flex flex-col gap-3 sm:gap-4 items-center">
                <div class="w-full sm:w-120">
                    {{-- Custom Select Component (Hard-write mirip select-form.blade.php) --}}
                    <div class="relative w-full">
                        {{-- Trigger --}}
                        <button type="button" @click="selectOpen = !selectOpen"
                            class="w-full flex justify-between items-center rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 bg-white
                                   focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                            <span x-text="selectSelected ? selectSelected.name : '-- Select Filter --'"
                                :class="!selectSelected ? 'text-gray-400' : 'text-gray-900'"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="selectOpen && 'rotate-180'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Dropdown --}}
                        <div x-show="selectOpen" @click.away="selectOpen = false" x-cloak 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" 
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75" 
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                            <ul class="max-h-60 overflow-y-auto py-1">
                                <template x-for="option in selectOptions" :key="option.value">
                                    <li @click="selectOption(option)"
                                        class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                        :class="{ 'bg-primary/10 font-medium text-primary': selectSelected && selectSelected.value === option.value }">
                                        <span x-text="option.name"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 justify-center">
                    {{-- Previous Month --}}
                    <a :href="`{{ route('calendar') }}?filter=${selectedFilter}&month={{ $prevMonth->month }}&year={{ $prevMonth->year }}`" 
                       class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>

                    {{-- Current Month/Year Display --}}
                    <div class="px-3 sm:px-4 py-2 text-center min-w-[140px] sm:min-w-[150px]">
                        <span class="text-sm sm:text-base font-semibold text-gray-900 whitespace-nowrap">
                            {{ $currentDate->format('F Y') }}
                        </span>
                    </div>

                    {{-- Next Month --}}
                    <a :href="`{{ route('calendar') }}?filter=${selectedFilter}&month={{ $nextMonth->month }}&year={{ $nextMonth->year }}`" 
                       class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    {{-- Reset Button --}}
                    <a :href="`{{ route('calendar') }}?filter=${selectedFilter}`" 
                       class="px-3 sm:px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg transition-colors cursor-pointer flex-shrink-0">
                        This Month
                    </a>
                </div>
            </div>
        </div>

        {{-- Calendar Section (for AJAX reload) --}}
        <section id="calendar-section" class="flex-1 overflow-hidden flex flex-col">
            <div id="calendar-wrapper" class="calendar-wrapper flex-1 overflow-hidden flex flex-col" :class="{ 'opacity-50 pointer-events-none': isLoading }">
            <div class="calendar-grid flex-1 flex flex-col overflow-hidden shadow-sm">
                {{-- Calendar Header (Days of Week) --}}
                <div class="grid grid-cols-7 bg-primary-light border-y border-gray-300">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                        <div class="py-2 text-center text-sm font-semibold border-r border-gray-300 last:border-r-0">
                            {{ $day }}
                        </div>
                    @endforeach
                </div>

                {{-- Calendar Body (Weeks and Days) --}}
                <div class="flex-1 flex flex-col">
                    @foreach ($calendar as $week)
                        <div class="grid grid-cols-7 border-b border-gray-200 last:border-b-0 flex-1">
                            @foreach ($week as $day)
                                <div class="calendar-cell border-r border-gray-200 last:border-r-0 p-1.5 {{ !$day['isCurrentMonth'] ? 'bg-gray-50' : 'bg-white' }}">
                                    {{-- Date Number --}}
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium {{ $day['isToday'] ? 'text-primary font-bold' : ($day['isCurrentMonth'] ? 'text-gray-900' : 'text-gray-400') }}">
                                            {{ $day['date']->format('d') }}
                                        </span>
                                    </div>

                                    {{-- Tasks for this day --}}
                                    @if (count($day['tasks']) > 0)
                                        <div class="space-y-1 flex-1">
                                            {{-- Show max 4 tasks --}}
                                            @foreach (array_slice($day['tasks'], 0, 4) as $orderStage)
                                                @php
                                                    // Check if ALL stages of this order are done (for create/deadline mode)
                                                    $allStagesDone = $orderStage->order->orderStages->every(function($stage) {
                                                        return strtolower($stage->status) === 'done';
                                                    });
                                                    
                                                    // In stage mode, use the current stage status
                                                    // In create/deadline mode, only show done if ALL stages done
                                                    $status = strtolower($orderStage->status ?? '');
                                                    $isHighPriority = strtolower($orderStage->order->priority ?? '') === 'high';
                                                    $isDone = ($filter === 'create' || $filter === 'deadline') ? $allStagesDone : ($status === 'done');
                                                    $isFinished = strtolower($orderStage->order->production_status ?? '') === 'finished';
                                                    
                                                    // Set colors based on status and priority
                                                    if ($isFinished) {
                                                        // Order finished (light gray with white text)
                                                        $bgClass = 'bg-gray-400';
                                                        $borderClass = 'border-gray-500';
                                                        $textClass = 'text-white';
                                                        $separatorClass = 'text-white opacity-70';
                                                    } elseif ($isDone) {
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
                                                <a href="{{ route('karyawan.task.work-order', ['order' => $orderStage->order->id]) }}" target="_blank"
                                                    class="block px-1.5 py-0.5 rounded border {{ $bgClass }} {{ $borderClass }} text-[9px] font-medium cursor-pointer hover:opacity-80 transition-opacity">
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
                                                            @if ($isHighPriority && !$isDone && !$isFinished)
                                                                <span class="{{ $separatorClass }}">•</span>
                                                                <span class="font-bold italic">HIGH</span>
                                                            @endif
                                                        </div>
                                                        @if ($isFinished)
                                                            <span class="text-white font-bold italic flex-shrink-0">FINISHED</span>
                                                        @elseif ($isDone)
                                                            <span class="text-gray-500 font-bold italic flex-shrink-0">DONE</span>
                                                        @endif
                                                    </div>
                                                </a>
                                            @endforeach

                                            {{-- View All button if more than 4 tasks --}}
                                            @if (count($day['tasks']) > 4)
                                                <button type="button"
                                                    @click="openModal('{{ $day['date']->format('l, d F Y') }}', {{ collect($day['tasks'])->map(function ($os) use ($filter) {
                                                        $isHigh = strtolower($os->order->priority ?? '') === 'high';
                                                        $isFinished = strtolower($os->order->production_status ?? '') === 'finished';
                                                        // Check if ALL stages done for create/deadline mode
                                                        $allStagesDone = $os->order->orderStages->every(function($stage) {
                                                            return strtolower($stage->status) === 'done';
                                                        });
                                                        $taskStatus = ($filter === 'create' || $filter === 'deadline') ? ($allStagesDone ? 'done' : 'in_progress') : $os->status;
                                                        
                                                        return [
                                                            'id' => $os->id,
                                                            'order_id' => $os->order->id,
                                                            'invoice' => $os->order->invoice->invoice_no ?? 'N/A',
                                                            'product' => $os->order->productCategory->product_name ?? 'N/A',
                                                            'customer' => $os->order->customer->customer_name ?? 'N/A',
                                                            'qty' => $os->order->total_qty ?? 0,
                                                            'priority' => $isHigh ? 'high' : 'normal',
                                                            'status' => $taskStatus,
                                                            'production_status' => $isFinished ? 'finished' : 'in_progress',
                                                            'deadline' => $os->deadline ? $os->deadline->format('d M Y') : 'N/A',
                                                        ];
                                                    })->toJson() }})"
                                                    class="w-full text-center text-[9px] font-medium text-primary hover:text-primary-dark transition-colors cursor-pointer border-t border-gray-200 pt-1 mt-0.5">
                                                    View All ({{ count($day['tasks']) - 4 }}+ More)
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
        </section>
        {{-- End Calendar Section --}}

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
                                <a :href="`{{ route('karyawan.task.work-order', ['order' => '__ORDER_ID__']) }}`.replace('__ORDER_ID__', task.order_id)"  target="_blank"
                                    class="block px-4 py-3 rounded-lg border hover:opacity-80 transition-opacity cursor-pointer"
                                    :style="task.production_status !== 'finished' && task.status !== 'done' && task.priority !== 'high' ? 'background-color: #eddfad; border-color: #d4c973;' : ''"
                                    :class="{
                                        'bg-gray-400 border-gray-500': task.production_status === 'finished',
                                        'bg-gray-200 border-gray-400': task.status === 'done' && task.production_status !== 'finished',
                                        'bg-red-500 border-red-600': task.status !== 'done' && task.priority === 'high' && task.production_status !== 'finished'
                                    }">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 text-xs font-medium flex-1 min-w-0"
                                            :class="{
                                                'text-white': task.production_status === 'finished' || (task.status !== 'done' && task.priority === 'high'),
                                                'text-gray-600': task.status === 'done' && task.production_status !== 'finished',
                                                'text-gray-900': task.status !== 'done' && task.priority !== 'high' && task.production_status !== 'finished'
                                            }">
                                            <span class="font-semibold truncate" x-text="task.customer"></span>
                                            <span :class="task.production_status === 'finished' || (task.priority === 'high' && task.status !== 'done') ? 'text-white opacity-70' : (task.status === 'done' ? 'text-gray-400' : 'text-gray-400')">•</span>
                                            <span class="truncate" x-text="task.product"></span>
                                            <span :class="task.production_status === 'finished' || (task.priority === 'high' && task.status !== 'done') ? 'text-white opacity-70' : (task.status === 'done' ? 'text-gray-400' : 'text-gray-400')">•</span>
                                            <span class="font-medium" x-text="task.qty || 0"></span>
                                            <template x-if="task.priority === 'high' && task.status !== 'done' && task.production_status !== 'finished'">
                                                <span>
                                                    <span class="text-white opacity-70">•</span>
                                                    <span class="font-bold italic">HIGH</span>
                                                </span>
                                            </template>
                                        </div>
                                        <template x-if="task.production_status === 'finished'">
                                            <span class="text-white font-bold italic text-xs flex-shrink-0">FINISHED</span>
                                        </template>
                                        <template x-if="task.status === 'done' && task.production_status !== 'finished'">
                                            <span class="text-gray-500 font-bold italic text-xs flex-shrink-0">DONE</span>
                                        </template>
                                    </div>
                                    <div class="mt-2 text-xs"
                                        :class="task.production_status === 'finished' || (task.priority === 'high' && task.status !== 'done') ? 'text-white opacity-80' : 'text-gray-500'">
                                        <span>Deadline: </span>
                                        <span x-text="task.deadline"></span>
                                    </div>
                                </a>
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
