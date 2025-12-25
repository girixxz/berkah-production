<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Production Calendar</title>
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
        // View mode state
        viewMode: '{{ request('view', 'monthly') }}',
        // Current month and year state (for monthly)
        currentMonth: {{ request('month', $currentDate->month) }},
        currentYear: {{ request('year', $currentDate->year) }},
        // Current week state (for weekly - format: YYYY-Wnn)
        currentWeek: '{{ request('week', '') }}',
        displayText: '{{ $viewMode === 'weekly' ? ($weekDisplayText ?? $currentDate->format('F Y')) : $currentDate->format('F Y') }}',
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
        switchViewMode(mode) {
            this.viewMode = mode;
            if (mode === 'monthly') {
                this.loadCalendar(this.currentMonth, this.currentYear);
            } else if (mode === 'weekly') {
                this.loadWeekly(this.currentWeek || this.getCurrentWeek());
            }
        },
        getCurrentWeek() {
            const now = new Date();
            const year = now.getFullYear();
            const onejan = new Date(year, 0, 1);
            const week = Math.ceil((((now - onejan) / 86400000) + onejan.getDay() + 1) / 7);
            return year + '-W' + String(week).padStart(2, '0');
        },
        navigateMonth(direction) {
            if (this.viewMode === 'weekly') {
                this.navigateWeek(direction);
                return;
            }
            
            // Calculate new month/year
            let newMonth = this.currentMonth;
            let newYear = this.currentYear;
            
            if (direction === 'prev') {
                newMonth--;
                if (newMonth < 1) {
                    newMonth = 12;
                    newYear--;
                }
            } else if (direction === 'next') {
                newMonth++;
                if (newMonth > 12) {
                    newMonth = 1;
                    newYear++;
                }
            } else if (direction === 'reset') {
                const now = new Date();
                newMonth = now.getMonth() + 1;
                newYear = now.getFullYear();
            }
            
            this.loadCalendar(newMonth, newYear);
        },
        navigateWeek(direction) {
            let weekStr = this.currentWeek || this.getCurrentWeek();
            const [year, week] = weekStr.split('-W').map(Number);
            
            let newYear = year;
            let newWeek = week;
            
            if (direction === 'prev') {
                newWeek--;
                if (newWeek < 1) {
                    newWeek = 52;
                    newYear--;
                }
            } else if (direction === 'next') {
                newWeek++;
                if (newWeek > 52) {
                    newWeek = 1;
                    newYear++;
                }
            } else if (direction === 'reset') {
                weekStr = this.getCurrentWeek();
                this.loadWeekly(weekStr);
                return;
            }
            
            const newWeekStr = newYear + '-W' + String(newWeek).padStart(2, '0');
            this.loadWeekly(newWeekStr);
        },
        loadCalendar(month, year) {
            this.isLoading = true;
            
            // Build URL with query params
            const params = new URLSearchParams();
            params.set('filter', this.selectedFilter);
            params.set('view', 'monthly');
            params.set('month', month);
            params.set('year', year);
            
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
                    
                    // Update state
                    this.currentMonth = month;
                    this.currentYear = year;
                    this.viewMode = 'monthly';
                    
                    // Update display text
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                                       'July', 'August', 'September', 'October', 'November', 'December'];
                    this.displayText = monthNames[month - 1] + ' ' + year;
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
        loadWeekly(weekStr) {
            this.isLoading = true;
            
            // Build URL with query params
            const params = new URLSearchParams();
            params.set('filter', this.selectedFilter);
            params.set('view', 'weekly');
            params.set('week', weekStr);
            
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
                    
                    // Update state
                    this.currentWeek = weekStr;
                    this.viewMode = 'weekly';
                    
                    // Update display text from section data attribute
                    const displayAttr = newSection.getAttribute('data-display-text');
                    if (displayAttr) {
                        this.displayText = displayAttr;
                    }
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
        applyFilter(filterValue) {
            this.selectedFilter = filterValue;
            if (this.viewMode === 'weekly') {
                this.loadWeekly(this.currentWeek || this.getCurrentWeek());
            } else {
                this.loadCalendar(this.currentMonth, this.currentYear);
            }
        },
        openModal(date, tasks) {
            this.modalDate = date;
            this.modalTasks = tasks;
            this.showModal = true;
        }
    }" class="h-full flex flex-col">

        {{-- Header - Responsive Layout --}}
        <div class="p-3 flex-shrink-0 bg-white border-b border-gray-200">
            <div class="flex flex-col lg:grid lg:grid-cols-3 gap-3 lg:gap-4 lg:items-center">
                {{-- Left: Monthly/Weekly Buttons --}}
                <div class="flex gap-2 justify-center lg:justify-start">
                    <button type="button" @click="switchViewMode('monthly')" 
                        :class="viewMode === 'monthly' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Monthly
                    </button>
                    <button type="button" @click="switchViewMode('weekly')" 
                        :class="viewMode === 'weekly' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Weekly
                    </button>
                </div>
                
                {{-- Center: Select Form --}}
                <div class="relative w-full order-2 lg:order-none">
                    <button type="button" @click="selectOpen = !selectOpen"
                        class="w-full flex justify-between items-center rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                        <span x-text="selectSelected ? selectSelected.name : '-- Select Filter --'"
                            :class="!selectSelected ? 'text-gray-400' : 'text-gray-900'"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="selectOpen && 'rotate-180'" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
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

                {{-- Right: Navigation --}}
                <div class="flex items-center gap-3 justify-center lg:justify-end order-3 lg:order-none">
                    <button type="button" @click="navigateMonth('prev')" 
                       class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div class="px-3 lg:px-4 py-2 text-center min-w-[140px] lg:min-w-[150px]">
                        <span class="text-sm lg:text-base font-semibold text-gray-900 whitespace-nowrap" x-text="displayText">
                            {{ $currentDate->format('F Y') }}
                        </span>
                    </div>
                    <button type="button" @click="navigateMonth('next')" 
                       class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button type="button" @click="navigateMonth('reset')" 
                       class="px-3 lg:px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg transition-colors cursor-pointer flex-shrink-0"
                       x-text="viewMode === 'weekly' ? 'This Week' : 'This Month'">
                        This Month
                    </button>
                </div>
            </div>
        </div>

        {{-- Calendar Section (for AJAX reload) --}}
        <section id="calendar-section" class="flex-1 overflow-hidden flex flex-col" 
            data-display-text="{{ $viewMode === 'weekly' ? $weekDisplayText : $currentDate->format('F Y') }}">
            <div id="calendar-wrapper" class="calendar-wrapper flex-1 overflow-hidden flex flex-col" :class="{ 'opacity-50 pointer-events-none': isLoading }">
            <div class="calendar-grid flex-1 flex flex-col overflow-hidden shadow-sm">
                {{-- Calendar Header (Days of Week) --}}
                <div class="grid grid-cols-7 bg-primary-light border-y border-gray-300">
                    @if ($viewMode === 'weekly' && isset($calendar[0]))
                        {{-- Weekly mode: Show day with date inline --}}
                        @foreach ($calendar[0] as $index => $day)
                            <div class="py-2 text-center text-sm font-semibold border-r border-gray-300 last:border-r-0 {{ $index === 0 ? 'bg-red-500 text-white' : '' }}">
                                {{ $day['date']->format('D') }} <span class="text-xs font-normal {{ $index === 0 ? 'text-white opacity-90' : 'text-gray-600' }}">({{ $day['date']->format('d') }})</span>
                            </div>
                        @endforeach
                    @else
                        {{-- Monthly mode: Show day only --}}
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $index => $day)
                            <div class="py-2 text-center text-sm font-semibold border-r border-gray-300 last:border-r-0 {{ $index === 0 ? 'bg-red-500 text-white' : '' }}">
                                {{ $day }}
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Calendar Body (Weeks and Days) --}}
                <div class="flex-1 flex flex-col">
                    @if ($viewMode === 'weekly')
                        {{-- Weekly mode: Show only first week (1 row with full height) --}}
                        @if (isset($calendar[0]))
                            <div class="grid grid-cols-7 flex-1">
                                @foreach ($calendar[0] as $index => $day)
                                    <div class="border-r border-gray-200 last:border-r-0 p-2 {{ $day['isToday'] ? 'bg-blue-50' : ($index === 0 ? 'bg-red-50' : 'bg-white') }} overflow-y-auto">
                                        {{-- Tasks for this day --}}
                                        @if (count($day['tasks']) > 0 && $index !== 0)
                                            <div class="space-y-1">
                                                {{-- Weekly: Show ALL tasks (no limit) --}}
                                                @foreach ($day['tasks'] as $orderStage)
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
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        {{-- Monthly mode: Show all weeks --}}
                        @foreach ($calendar as $week)
                            <div class="grid grid-cols-7 border-b border-gray-200 last:border-b-0 flex-1">
                                @foreach ($week as $index => $day)
                                <div class="calendar-cell border-r border-gray-200 last:border-r-0 p-1.5 {{ $day['isToday'] ? 'bg-blue-50' : ($index === 0 ? 'bg-red-50' : (!$day['isCurrentMonth'] ? 'bg-gray-50' : 'bg-white')) }}">
                                    {{-- Date Number --}}
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium {{ $day['isToday'] ? 'text-primary font-bold' : ($day['isCurrentMonth'] ? 'text-gray-900' : 'text-gray-400') }}">
                                            {{ $day['date']->format('d') }}
                                        </span>
                                    </div>

                                    {{-- Tasks for this day --}}
                                    @if (count($day['tasks']) > 0 && $index !== 0)
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
                    @endif
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
