@extends('layouts.app')

@section('title', 'Highlights')

@section('content')

    <x-nav-locate :items="['Menu', 'Highlights']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        activeFilter: '{{ request('filter', 'all') }}',
        searchQuery: '{{ request('search') }}',
        startDate: '{{ $startDate ? $startDate->format('Y-m-d') : '' }}',
        endDate: '{{ $endDate ? $endDate->format('Y-m-d') : '' }}',
        dateRange: '{{ $dateRange ?? 'this_month' }}',
        showDateFilter: false,
        showDateCustomRange: false,
        datePreset: '',
        getDateLabel() {
            if (this.dateRange === 'last_month') return 'Last Month';
            if (this.dateRange === 'last_7_days') return 'Last 7 Days';
            if (this.dateRange === 'yesterday') return 'Yesterday';
            if (this.dateRange === 'today') return 'Today';
            if (this.dateRange === 'this_month') return 'This Month';
            if (this.dateRange === 'custom' && this.startDate && this.endDate) return 'Custom Date';
            return 'Date';
        },
        applyDatePreset(preset) {
            const today = new Date();
            const form = this.$refs.dateForm;
            if (preset === 'last-month') {
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                this.startDate = lastMonth.toISOString().split('T')[0];
                this.endDate = lastMonthEnd.toISOString().split('T')[0];
                this.dateRange = 'last_month';
                form.querySelector('input[name=date_range]').value = 'last_month';
                form.querySelector('input[name=start_date]').value = this.startDate;
                form.querySelector('input[name=end_date]').value = this.endDate;
                form.submit();
            } else if (preset === '1-week-ago') {
                const oneWeekAgo = new Date(today);
                oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
                this.startDate = oneWeekAgo.toISOString().split('T')[0];
                this.endDate = today.toISOString().split('T')[0];
                this.dateRange = 'last_7_days';
                form.querySelector('input[name=date_range]').value = 'last_7_days';
                form.querySelector('input[name=start_date]').value = this.startDate;
                form.querySelector('input[name=end_date]').value = this.endDate;
                form.submit();
            } else if (preset === 'yesterday') {
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                this.startDate = yesterday.toISOString().split('T')[0];
                this.endDate = yesterday.toISOString().split('T')[0];
                this.dateRange = 'yesterday';
                form.querySelector('input[name=date_range]').value = 'yesterday';
                form.querySelector('input[name=start_date]').value = this.startDate;
                form.querySelector('input[name=end_date]').value = this.endDate;
                form.submit();
            } else if (preset === 'today') {
                this.startDate = today.toISOString().split('T')[0];
                this.endDate = today.toISOString().split('T')[0];
                this.dateRange = 'today';
                form.querySelector('input[name=date_range]').value = 'today';
                form.querySelector('input[name=start_date]').value = this.startDate;
                form.querySelector('input[name=end_date]').value = this.endDate;
                form.submit();
            } else if (preset === 'this-month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                this.startDate = firstDay.toISOString().split('T')[0];
                this.endDate = lastDay.toISOString().split('T')[0];
                this.dateRange = 'this_month';
                form.querySelector('input[name=date_range]').value = 'this_month';
                form.querySelector('input[name=start_date]').value = this.startDate;
                form.querySelector('input[name=end_date]').value = this.endDate;
                form.submit();
            } else if (preset === 'custom') {
                this.showDateCustomRange = true;
            }
        }
    }" class="space-y-6">

        {{-- Wrap dengan section untuk AJAX reload --}}
        <section id="highlights-section">
            {{-- ================= SECTION 1: STATISTICS CARDS ================= --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Total Orders --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Orders</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_orders']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- WIP --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">WIP</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['wip']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Finished --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Finished</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['finished']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= SECTION 2: FILTER & ACTIONS ================= --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 mt-6">
                {{-- Mobile: Vertikal | Desktop (1280px+): Horizontal dengan filter kiri, actions kanan --}}
                <div class="flex flex-col xl:flex-row xl:items-center gap-4">

                    {{-- Left: Filter Buttons --}}
                    <div class="grid grid-cols-3 md:flex md:flex-wrap gap-2">
                        {{-- All - Green (Primary) --}}
                        <a href="{{ route('highlights', ['filter' => 'all'] + request()->except('filter')) }}"
                            :class="activeFilter === 'all' ? 'bg-primary text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            All
                        </a>
                        {{-- WIP - Blue --}}
                        <a href="{{ route('highlights', ['filter' => 'wip'] + request()->except('filter')) }}"
                            :class="activeFilter === 'wip' ? 'bg-blue-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-blue-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            WIP
                        </a>
                        {{-- Finished - Green --}}
                        <a href="{{ route('highlights', ['filter' => 'finished'] + request()->except('filter')) }}"
                            :class="activeFilter === 'finished' ? 'bg-green-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-green-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Finished
                        </a>
                    </div>

                    {{-- Right: Search & Date Filter --}}
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">

                        {{-- Search & Date Filter - Same row on mobile --}}
                        <div class="flex gap-2 items-center xl:flex-1 xl:min-w-0">
                            {{-- Search - Flexible width yang bisa menyesuaikan --}}
                            <form method="GET" action="{{ route('highlights') }}"
                                class="flex-1 xl:min-w-[180px] relative" x-ref="searchForm">
                                <input type="hidden" name="filter" value="{{ request('filter', 'all') }}">
                                @if (request('date_range'))
                                    <input type="hidden" name="date_range" value="{{ request('date_range') }}">
                                @endif
                                @if (request('start_date'))
                                    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                                @endif
                                @if (request('end_date'))
                                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                                @endif
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text" name="search" 
                                        value="{{ request('search') }}"
                                        @input.debounce.500ms="$refs.searchForm.submit()"
                                        placeholder="Search customer, product, invoice..."
                                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </form>

                            {{-- Date Filter - Icon only di mobile, with text di desktop --}}
                            <div class="relative flex-shrink-0">
                                <button type="button" @click="showDateFilter = !showDateFilter"
                                    :class="dateRange ? 'border-primary bg-primary/5 text-primary' :
                                        'border-gray-300 text-gray-700 bg-white'"
                                    class="px-3 lg:px-4 py-2 border rounded-md text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{-- Text hidden di mobile, visible di desktop --}}
                                    <span x-text="getDateLabel()" class="hidden lg:inline whitespace-nowrap"></span>
                                </button>

                                {{-- Hidden Form for Date Presets --}}
                                <form x-ref="dateForm" method="GET" action="{{ route('highlights') }}"
                                    class="hidden">
                                    <input type="hidden" name="filter" :value="activeFilter">
                                    <input type="hidden" name="search" :value="searchQuery">
                                    <input type="hidden" name="date_range" :value="dateRange">
                                    <input type="hidden" name="start_date" :value="startDate">
                                    <input type="hidden" name="end_date" :value="endDate">
                                </form>

                                {{-- Date Filter Modal --}}
                                <div x-show="showDateFilter"
                                    @click.away="showDateFilter = false; showDateCustomRange = false" x-cloak
                                    class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-20">

                                    {{-- Main Preset Options --}}
                                    <div x-show="!showDateCustomRange" class="p-2">
                                        <button @click="applyDatePreset('last-month')" type="button"
                                            :class="dateRange === 'last_month' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            Last Month
                                        </button>
                                        <button @click="applyDatePreset('1-week-ago')" type="button"
                                            :class="dateRange === 'last_7_days' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            Last 7 Days
                                        </button>
                                        <button @click="applyDatePreset('yesterday')" type="button"
                                            :class="dateRange === 'yesterday' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            Yesterday
                                        </button>
                                        <button @click="applyDatePreset('today')" type="button"
                                            :class="dateRange === 'today' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            Today
                                        </button>
                                        <button @click="applyDatePreset('this-month')" type="button"
                                            :class="dateRange === 'this_month' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            This Month
                                        </button>
                                        <div class="border-t border-gray-200 my-2"></div>
                                        <button @click="applyDatePreset('custom')" type="button"
                                            :class="dateRange === 'custom' ? 'bg-primary/10 text-primary font-semibold' :
                                                'text-primary hover:bg-primary/5 font-medium'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            Custom Date
                                        </button>
                                    </div>

                                    {{-- Custom Range Form --}}
                                    <form x-show="showDateCustomRange" method="GET"
                                        action="{{ route('highlights') }}" class="p-4"
                                        @submit="dateRange = 'custom'">
                                        <input type="hidden" name="filter" :value="activeFilter">
                                        <input type="hidden" name="search" :value="searchQuery">
                                        <input type="hidden" name="date_range" value="custom">
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                                <input type="date" name="start_date" x-model="startDate" required
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                                <input type="date" name="end_date" x-model="endDate" required
                                                    :min="startDate"
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                            </div>

                                            <div class="flex gap-2 pt-2">
                                                <button type="button" @click="showDateCustomRange = false; showDateFilter = false"
                                                    class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                    class="flex-1 px-4 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium">
                                                    Apply
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Clear Filter Link --}}
                                        <a href="{{ route('highlights', request()->except(['date_range', 'start_date', 'end_date'])) }}"
                                            class="block text-center mt-3 pt-3 border-t border-gray-200 text-sm text-red-600 hover:text-red-700 font-medium">
                                            Clear Date Filter
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= SECTION 3: TABLE ================= --}}
                {{-- Desktop Table View (Hidden on Mobile/Tablet) --}}
                <div class="hidden xl:block overflow-x-auto mt-4">
                    <table class="min-w-full text-sm table-fixed">
                        <thead class="bg-primary-light text-gray-600">
                            <tr>
                                <th class="py-2 pl-[60px] text-left font-medium rounded-l-lg w-[180px]">Mockup</th>
                                <th class="py-2 pl-[70px] text-left font-medium w-[180px]">Orders Detail</th>
                                <th class="py-2 px-2 text-center font-medium w-[120px]">Total Design</th>
                                <th class="py-2 px-2 text-left font-medium w-[280px]">Info Progress</th>
                                <th class="py-2 px-2 text-center font-medium rounded-r-lg w-[100px]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="">
                            @forelse ($orders as $order)
                                @php
                                    $startDate = $order->wip_date ?? $order->order_date;
                                    $deadline = $order->deadline;
                                    $now = now();
                                    
                                    $totalDuration = round($startDate->diffInDays($deadline));
                                    $daysRemaining = round($now->diffInDays($deadline, false));
                                    
                                    // Calculate progress based on completed stages
                                    $totalStages = $order->orderStages->count();
                                    $completedStages = $order->orderStages->where('status', 'done')->count();
                                    $percentage = $totalStages > 0 ? round(($completedStages / $totalStages) * 100) : 0;
                                    
                                    $totalDesigns = $order->designVariants->count();
                                @endphp
                                <tr class="border-t border-gray-200 hover:bg-gray-50" x-data="{ showImageModal: false }">
                                    {{-- Mockup Column --}}
                                    <td class="py-2 px-2 text-left">
                                        <div @click="showImageModal = true" 
                                             class="inline-block w-40 h-25 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity">
                                            @if ($order->img_url)
                                                <img src="{{ asset('storage/' . $order->img_url) }}" alt="Order Image" 
                                                     class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Orders Column - Info Only --}}
                                    <td class="py-2 px-2">
                                        <div class="space-y-0.5">
                                            <div>
                                                <span class="inline-block w-16 text-xs text-gray-500">CUSTOMER</span>
                                                <span class="text-xs text-gray-500">:</span>
                                                <span class="ml-1 text-sm font-bold text-gray-900">
                                                    {{ $order->customer->customer_name ?? '-' }}
                                                    @if (isset($order->priority) && strtolower($order->priority) === 'high')
                                                        <span class="text-xs font-semibold text-red-600 italic">(HIGH)</span>
                                                    @endif
                                                </span>
                                            </div>
                                            
                                            <div>
                                                <span class="inline-block w-16 text-xs text-gray-500">PRODUCT</span>
                                                <span class="text-xs text-gray-500">:</span>
                                                <span class="ml-1 text-sm font-semibold text-gray-900">{{ $order->productCategory->product_name ?? '-' }}</span>
                                            </div>
                                            
                                            <div>
                                                <span class="inline-block w-16 text-xs text-gray-500">INVOICE</span>
                                                <span class="text-xs text-gray-500">:</span>
                                                <span class="ml-1 text-sm text-gray-700">{{ $order->invoice->invoice_number ?? 'N/A' }}</span>
                                            </div>
                                            
                                            <div>
                                                <span class="inline-block w-16 text-xs text-gray-500">MATERIAL</span>
                                                <span class="text-xs text-gray-500">:</span>
                                                <span class="ml-1 text-sm text-gray-700">{{ $order->materialCategory->material_name ?? '-' }} {{ $order->materialTexture->texture_name ?? '' }}</span>
                                            </div>
                                            
                                            <div>
                                                <span class="inline-block w-16 text-xs text-gray-500">QTY</span>
                                                <span class="text-xs text-gray-500">:</span>
                                                <span class="ml-1 text-sm font-semibold text-gray-900">{{ number_format($order->total_qty) }} pcs</span>
                                            </div>
                                        </div>

                                        {{-- Image Modal --}}
                                        @if ($order->img_url)
                                            <div x-show="showImageModal" 
                                                 x-cloak
                                                 @click="showImageModal = false"
                                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                                                <div @click.stop class="relative max-w-4xl max-h-[90vh]">
                                                    <button @click="showImageModal = false" 
                                                            class="absolute -top-10 right-0 text-white hover:text-gray-300">
                                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                    <img src="{{ asset('storage/' . $order->img_url) }}" 
                                                         alt="Order Image Full" 
                                                         class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Total Design Column --}}
                                    <td class="py-2 px-2 text-center">
                                        <div class="inline-flex items-center justify-center w-9 h-9 bg-gray-100 rounded-full">
                                            <span class="text-sm font-bold text-gray-600">{{ $totalDesigns }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5">Design</p>
                                    </td>

                                    {{-- Info Progress Column --}}
                                    <td class="py-2 px-2">
                                        <div class="space-y-1">
                                            {{-- Days Left Info - Above Progress Bar in One Line --}}
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-gray-500">{{ $startDate->format('d/m') }}</span>
                                                @if ($daysRemaining > 0)
                                                    <span class="text-orange-600 font-medium">{{ $daysRemaining }} days left</span>
                                                @elseif ($daysRemaining === 0)
                                                    <span class="text-orange-600 font-medium">Due today</span>
                                                @else
                                                    <span class="text-red-600 font-medium">{{ abs($daysRemaining) }} days overdue</span>
                                                @endif
                                                <span class="text-gray-500">{{ $deadline->format('d/m') }}</span>
                                            </div>

                                            {{-- Progress Bar --}}
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                                                    <div class="bg-primary h-full rounded-full transition-all duration-300"
                                                        style="width: {{ $percentage }}%"></div>
                                                </div>
                                                <span class="text-xs font-medium text-gray-600 min-w-[30px] text-right">{{ $percentage }}%</span>
                                            </div>

                                            {{-- Total Duration --}}
                                            <div class="text-center text-xs text-gray-500">
                                                / {{ $totalDuration }} days total
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Status Column --}}
                                    <td class="py-2 px-2 text-center">
                                        @if ($order->production_status === 'wip')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                                WIP
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Finished
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500">
                                        No orders found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile/Tablet Card View (Visible on Mobile/Tablet Only) --}}
                <div class="xl:hidden mt-4 space-y-4">
                    @forelse ($orders as $order)
                        @php
                            $startDate = $order->wip_date ?? $order->order_date;
                            $deadline = $order->deadline;
                            $now = now();
                            
                            $totalDuration = round($startDate->diffInDays($deadline));
                            $daysRemaining = round($now->diffInDays($deadline, false));
                            
                            // Calculate progress based on completed stages
                            $totalStages = $order->orderStages->count();
                            $completedStages = $order->orderStages->where('status', 'done')->count();
                            $percentage = $totalStages > 0 ? round(($completedStages / $totalStages) * 100) : 0;
                            
                            $totalDesigns = $order->designVariants->count();
                        @endphp
                        
                        <div x-data="{ showImageModal: false }" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            {{-- Header: Customer Name & Status --}}
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-bold text-base text-gray-900">
                                        {{ $order->customer->customer_name ?? '-' }}
                                        @if (isset($order->priority) && strtolower($order->priority) === 'high')
                                            <span class="text-xs font-semibold text-red-600 italic">(HIGH)</span>
                                        @endif
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $order->invoice->invoice_number ?? 'N/A' }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    @if ($order->production_status === 'wip')
                                        <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                            WIP
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Finished
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Content: Image & Info --}}
                            <div class="flex gap-3 mb-3">
                                {{-- Mockup Image --}}
                                <div @click="showImageModal = true" 
                                     class="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity">
                                    @if ($order->img_url)
                                        <img src="{{ asset('storage/' . $order->img_url) }}" alt="Order Image" 
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Order Info --}}
                                <div class="flex-1 space-y-1 text-sm">
                                    <div class="flex">
                                        <span class="text-gray-500 w-20 flex-shrink-0">Product</span>
                                        <span class="text-gray-500">:</span>
                                        <span class="ml-2 font-semibold text-gray-900">{{ $order->productCategory->product_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-20 flex-shrink-0">Material</span>
                                        <span class="text-gray-500">:</span>
                                        <span class="ml-2 text-gray-700">{{ $order->materialCategory->material_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-20 flex-shrink-0">QTY</span>
                                        <span class="text-gray-500">:</span>
                                        <span class="ml-2 font-semibold text-gray-900">{{ number_format($order->total_qty) }} pcs</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="mb-2">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-gray-600">Progress</span>
                                    <span class="text-xs font-semibold text-gray-900">{{ $percentage }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="bg-primary h-full rounded-full transition-all duration-300"
                                        style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>

                            {{-- Footer: Design Count & Timeline --}}
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-xs">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1">
                                        <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-bold text-gray-600">{{ $totalDesigns }}</span>
                                        </div>
                                        <span class="text-gray-500">Design</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    @if ($daysRemaining > 0)
                                        <div class="text-orange-600 font-medium">{{ $daysRemaining }} days left</div>
                                    @elseif ($daysRemaining === 0)
                                        <div class="text-orange-600 font-medium">Due today</div>
                                    @else
                                        <div class="text-red-600 font-medium">{{ abs($daysRemaining) }} days overdue</div>
                                    @endif
                                    <div class="text-gray-500">{{ $deadline->format('d M Y') }}</div>
                                </div>
                            </div>

                            {{-- Image Modal --}}
                            @if ($order->img_url)
                                <div x-show="showImageModal" 
                                     x-cloak
                                     @click="showImageModal = false"
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                                    <div @click.stop class="relative max-w-4xl max-h-[90vh]">
                                        <button @click="showImageModal = false" 
                                                class="absolute -top-10 right-0 text-white hover:text-gray-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <img src="{{ asset('storage/' . $order->img_url) }}" 
                                             alt="Order Image Full" 
                                             class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            No orders found
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                <div id="highlights-pagination-container" class="mt-5">
                    @if ($orders->hasPages())
                        <x-custom-pagination :paginator="$orders" />
                    @endif
                </div>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    {{-- Pagination AJAX Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('highlights-pagination-container', 'highlights-section');
        });

        function setupPagination(containerId, sectionId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            container.addEventListener('click', function(e) {
                const link = e.target.closest('a[href*="page="]');
                if (!link) return;

                e.preventDefault();
                const url = link.getAttribute('href');

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newSection = doc.getElementById(sectionId);

                        if (newSection) {
                            document.getElementById(sectionId).innerHTML = newSection.innerHTML;

                            // Smooth scroll to top of section
                            document.getElementById(sectionId).scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });

                            // Re-setup pagination after content update
                            setupPagination(containerId, sectionId);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        }
    </script>
@endpush
