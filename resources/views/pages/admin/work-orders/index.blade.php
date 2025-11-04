@extends('layouts.app')

@section('title', 'Work Orders')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Work Orders']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        activeFilter: '{{ request('filter', 'all') }}',
        searchQuery: '{{ request('search') }}',
        startDate: '{{ $startDate ?? '' }}',
        endDate: '{{ $endDate ?? '' }}',
        dateRange: '{{ $dateRange ?? '' }}',
        showDateFilter: false,
        showDateCustomRange: false,
        init() {
            const toastMessage = sessionStorage.getItem('toast_message');
            const toastType = sessionStorage.getItem('toast_type');
            if (toastMessage) {
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: toastMessage, type: toastType || 'success' }
                    }));
                    sessionStorage.removeItem('toast_message');
                    sessionStorage.removeItem('toast_type');
                }, 300);
            }
        },
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
        <section id="work-orders-section">
            {{-- ================= SECTION 1: STATISTICS CARDS ================= --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Total Orders --}}
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-blue-600 font-medium">Total Orders</p>
                            <p class="text-2xl font-bold text-blue-900 mt-1">{{ number_format($stats['total_orders']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Pending --}}
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-yellow-600 font-medium">Pending</p>
                            <p class="text-2xl font-bold text-yellow-900 mt-1">{{ number_format($stats['pending']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Created --}}
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Created</p>
                            <p class="text-2xl font-bold text-green-900 mt-1">{{ number_format($stats['created']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <a href="{{ route('admin.work-orders.index', ['filter' => 'all'] + request()->except('filter')) }}"
                            :class="activeFilter === 'all' ? 'bg-primary text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            All
                        </a>
                        {{-- Pending - Yellow --}}
                        <a href="{{ route('admin.work-orders.index', ['filter' => 'pending'] + request()->except('filter')) }}"
                            :class="activeFilter === 'pending' ? 'bg-yellow-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-yellow-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Pending
                        </a>
                        {{-- Created - Green --}}
                        <a href="{{ route('admin.work-orders.index', ['filter' => 'created'] + request()->except('filter')) }}"
                            :class="activeFilter === 'created' ? 'bg-green-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-green-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Created
                        </a>
                    </div>

                    {{-- Right: Search & Date Filter --}}
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">

                        {{-- Search & Date Filter - Same row on mobile --}}
                        <div class="flex gap-2 items-center xl:flex-1 xl:min-w-0">
                            {{-- Search - Flexible width yang bisa menyesuaikan --}}
                            <form method="GET" action="{{ route('admin.work-orders.index') }}"
                                class="flex-1 xl:min-w-[180px]" x-ref="searchForm">
                                <input type="hidden" name="filter" value="{{ request('filter', 'all') }}">
                                @if (request('start_date'))
                                    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                                @endif
                                @if (request('end_date'))
                                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                                @endif
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        @input.debounce.500ms="$refs.searchForm.submit()"
                                        placeholder="Search invoice, customer..."
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
                                <form x-ref="dateForm" method="GET" action="{{ route('admin.work-orders.index') }}"
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
                                        action="{{ route('admin.work-orders.index') }}" class="p-4"
                                        @submit="dateRange = 'custom'">
                                        <input type="hidden" name="filter" :value="activeFilter">
                                        <input type="hidden" name="search" :value="searchQuery">
                                        <input type="hidden" name="date_range" value="custom">

                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Start
                                                    Date</label>
                                                <input type="date" name="start_date" x-model="startDate"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">End
                                                    Date</label>
                                                <input type="date" name="end_date" x-model="endDate"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                            </div>
                                            <div class="flex gap-2 pt-2">
                                                <button type="submit"
                                                    class="flex-1 px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-primary-dark">
                                                    Apply
                                                </button>
                                                <button type="button" @click="showDateCustomRange = false"
                                                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                                                    Back
                                                </button>
                                            </div>
                                            <a href="{{ route('admin.work-orders.index', ['filter' => request('filter', 'all')]) }}"
                                                class="block w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 text-center">
                                                Reset Filter
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= SECTION 3: TABLE ================= --}}
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-light text-gray-600">
                            <tr>
                                <th class="py-3 px-4 text-left font-medium rounded-l-lg">No Invoice</th>
                                <th class="py-3 px-4 text-left font-medium">Customer</th>
                                <th class="py-3 px-4 text-left font-medium">Product</th>
                                <th class="py-3 px-4 text-left font-medium">QTY</th>
                                <th class="py-3 px-4 text-left font-medium">Total Design</th>
                                <th class="py-3 px-4 text-left font-medium">Deadline</th>
                                <th class="py-3 px-4 text-left font-medium">Status</th>
                                <th class="py-3 px-4 text-center font-medium rounded-r-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-gray-50">
                                    {{-- Invoice No with Priority --}}
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                class="font-medium text-gray-900">{{ $order->invoice->invoice_no ?? '-' }}</span>
                                            @if (isset($order->priority) && strtolower($order->priority) === 'high')
                                                <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Customer --}}
                                    <td class="py-3 px-4">
                                        <div>
                                            <p class="text-gray-700">
                                                {{ $order->customer->customer_name ?? '-' }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $order->customer->phone ?? '-' }}</p>
                                        </div>
                                    </td>

                                    {{-- Product --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->productCategory->product_name ?? '-' }}</span>
                                    </td>

                                    {{-- QTY --}}
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->orderItems->sum('qty') }}</span>
                                    </td>

                                    {{-- Total Design --}}
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->designVariants->count() }}</span>
                                    </td>

                                    {{-- Deadline --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d M Y') : '-' }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3 px-4">
                                        @if ($order->work_order_status === 'created')
                                            <span
                                                class="inline-flex items-center text-xs font-medium text-green-700 bg-green-50 px-2 py-1 rounded-full w-fit">
                                                Created
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center text-xs font-medium text-yellow-700 bg-yellow-50 px-2 py-1 rounded-full w-fit">
                                                Pending
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Action --}}
                                    <td class="py-3 px-4">
                                        <div class="flex justify-center">
                                            <div class="relative inline-block text-left" x-data="{
                                                open: false,
                                                dropdownStyle: {},
                                                checkPosition() {
                                                    const button = this.$refs.button;
                                                    const rect = button.getBoundingClientRect();
                                                    const spaceBelow = window.innerHeight - rect.bottom;
                                                    const spaceAbove = rect.top;
                                                    const dropUp = spaceBelow < 200 && spaceAbove > spaceBelow;
                                            
                                                    if (dropUp) {
                                                        this.dropdownStyle = {
                                                            position: 'fixed',
                                                            top: (rect.top - 160) + 'px',
                                                            left: (rect.right - 180) + 'px',
                                                            width: '180px'
                                                        };
                                                    } else {
                                                        this.dropdownStyle = {
                                                            position: 'fixed',
                                                            top: (rect.bottom + 8) + 'px',
                                                            left: (rect.right - 180) + 'px',
                                                            width: '180px'
                                                        };
                                                    }
                                                }
                                            }"
                                                x-init="$watch('open', value => {
                                                    if (value) {
                                                        const scrollContainer = $el.closest('.overflow-x-auto');
                                                        const mainContent = document.querySelector('main');
                                                        const closeOnScroll = () => { open = false; };
                                                
                                                        scrollContainer?.addEventListener('scroll', closeOnScroll);
                                                        mainContent?.addEventListener('scroll', closeOnScroll);
                                                        window.addEventListener('resize', closeOnScroll);
                                                    }
                                                })">
                                                {{-- Three Dot Button HORIZONTAL --}}
                                                <button x-ref="button" @click="checkPosition(); open = !open"
                                                    type="button"
                                                    class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                    title="Actions">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    </svg>
                                                </button>

                                                {{-- Dropdown Menu with Fixed Position --}}
                                                <div x-show="open" @click.away="open = false" x-cloak x-ref="dropdown"
                                                    :style="dropdownStyle"
                                                    class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                    {{-- Manage Work Order --}}
                                                    <a href="{{ route('admin.work-orders.manage', $order->id) }}"
                                                        class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        Manage WO
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-gray-400">
                                        <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm">No WIP orders found</p>
                                        <p class="text-xs text-gray-500 mt-1">Orders in WIP status will appear here</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div id="work-orders-pagination-container" class="mt-5">
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
            setupPagination('work-orders-pagination-container', 'work-orders-section');
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
