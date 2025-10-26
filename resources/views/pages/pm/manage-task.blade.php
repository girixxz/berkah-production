@extends('layouts.app')

@section('title', 'Manage Task')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Manage Task']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        activeFilter: '{{ request('filter', 'default') }}',
        searchQuery: '{{ request('search') }}',
        startDate: '{{ $startDate ?? '' }}',
        endDate: '{{ $endDate ?? '' }}',
        dateRange: '{{ $dateRange ?? '' }}',
        showDateFilter: false,
        showDateCustomRange: false,
        showStageModal: false,
        showEditStageModal: false,
        selectedOrderId: null,
        selectedStageId: null,
        selectedStageName: '',
        stageStartDate: '',
        stageDeadline: '',
        isSubmittingStage: false,
        stageErrors: {},
        editStageData: {
            orderId: null,
            invoiceNo: '',
            orderStages: [],
            orderNotes: ''
        },
        isUpdatingStatus: false,
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
        resetStageForm() {
            this.stageStartDate = '';
            this.stageDeadline = '';
            this.stageErrors = {};
            this.selectedOrderId = null;
            this.selectedStageId = null;
            this.selectedStageName = '';
            this.isSubmittingStage = false;
        },
        openStageModal(orderId, stageId, stageName, startDate = '', deadline = '') {
            this.selectedOrderId = orderId;
            this.selectedStageId = stageId;
            this.selectedStageName = stageName;
            this.stageStartDate = startDate || '';
            this.stageDeadline = deadline || '';
            this.showStageModal = true;
        },
        openEditStageModal(orderId, invoiceNo, orderStages, orderNotes) {
            this.editStageData = {
                orderId: orderId,
                invoiceNo: invoiceNo,
                orderStages: orderStages,
                orderNotes: orderNotes || ''
            };
            this.showEditStageModal = true;
        },
        updateStageStatus(orderStageId, newStatus) {
            this.isUpdatingStatus = true;
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('order_stage_id', orderStageId);
            formData.append('status', newStatus);
    
            fetch('{{ route('pm.manage-task.update-stage-status') }}', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update local data without reload
                        const stageIndex = this.editStageData.orderStages.findIndex(s => s.id === orderStageId);
                        if (stageIndex !== -1) {
                            this.editStageData.orderStages[stageIndex].status = newStatus;
                        }
    
                        // Show toast
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: data.message, type: 'success' }
                        }));
    
                        this.isUpdatingStatus = false;
    
                        // Reload page after short delay to update table
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert(data.message || 'Failed to update status');
                        this.isUpdatingStatus = false;
                    }
                })
                .catch(err => {
                    alert('Network error. Please try again.');
                    this.isUpdatingStatus = false;
                    console.error(err);
                });
        },
        getDateLabel() {
            if (this.dateRange === 'last_month') return 'Bulan Lalu';
            if (this.dateRange === 'last_7_days') return '1 Minggu Yang Lalu';
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

        {{-- ================= SECTION 1: STATISTICS CARDS ================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Total Orders --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_orders']) }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Order Finished --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Order Finished</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['order_finished']) }}</p>
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
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                {{-- Left: Filter Buttons --}}
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('pm.manage-task', ['filter' => 'default'] + request()->except('filter')) }}"
                        :class="activeFilter === 'default' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Default
                    </a>
                    <a href="{{ route('pm.manage-task', ['filter' => 'wip'] + request()->except('filter')) }}"
                        :class="activeFilter === 'wip' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        WIP
                    </a>
                    <a href="{{ route('pm.manage-task', ['filter' => 'finished'] + request()->except('filter')) }}"
                        :class="activeFilter === 'finished' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Finished
                    </a>
                </div>

                {{-- Right: Search & Date Filter --}}
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    {{-- Search --}}
                    <form method="GET" action="{{ route('pm.manage-task') }}" class="flex-1 lg:w-64" x-ref="searchForm">
                        <input type="hidden" name="filter" value="{{ request('filter', 'default') }}">
                        @if (request('start_date'))
                            <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                        @endif
                        @if (request('end_date'))
                            <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                        @endif
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}"
                                @input.debounce.500ms="$refs.searchForm.submit()" placeholder="Search invoice, customer..."
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </form>

                    {{-- Date Filter --}}
                    <div class="relative">
                        <button type="button" @click="showDateFilter = !showDateFilter"
                            :class="dateRange ? 'border-primary bg-primary/5 text-primary' :
                                'border-gray-300 text-gray-700 bg-white'"
                            class="px-4 py-2 border rounded-md text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span x-text="getDateLabel()"></span>
                        </button>

                        {{-- Hidden Form for Date Presets --}}
                        <form x-ref="dateForm" method="GET" action="{{ route('pm.manage-task') }}" class="hidden">
                            <input type="hidden" name="filter" :value="activeFilter">
                            <input type="hidden" name="search" :value="searchQuery">
                            <input type="hidden" name="date_range" :value="dateRange">
                            <input type="hidden" name="start_date" :value="startDate">
                            <input type="hidden" name="end_date" :value="endDate">
                        </form>

                        {{-- Date Filter Modal --}}
                        <div x-show="showDateFilter" @click.away="showDateFilter = false; showDateCustomRange = false"
                            x-cloak
                            class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-20">

                            {{-- Main Preset Options --}}
                            <div x-show="!showDateCustomRange" class="p-2">
                                <button @click="applyDatePreset('last-month')" type="button"
                                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                                    Bulan Lalu
                                </button>
                                <button @click="applyDatePreset('1-week-ago')" type="button"
                                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                                    1 Minggu Yang Lalu
                                </button>
                                <button @click="applyDatePreset('yesterday')" type="button"
                                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                                    Yesterday
                                </button>
                                <button @click="applyDatePreset('today')" type="button"
                                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                                    Today
                                </button>
                                <button @click="applyDatePreset('this-month')" type="button"
                                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">
                                    This Month
                                </button>
                                <div class="border-t border-gray-200 my-2"></div>
                                <button @click="applyDatePreset('custom')" type="button"
                                    class="w-full text-left px-4 py-2.5 text-sm font-medium text-primary hover:bg-primary/5 rounded-md transition-colors">
                                    Custom Date
                                </button>
                            </div>

                            {{-- Custom Range Form --}}
                            <form x-show="showDateCustomRange" method="GET" action="{{ route('pm.manage-task') }}"
                                class="p-4" @submit="dateRange = 'custom'">
                                <input type="hidden" name="filter" :value="activeFilter">
                                <input type="hidden" name="search" :value="searchQuery">
                                <input type="hidden" name="date_range" value="custom">

                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                        <input type="date" name="start_date" x-model="startDate"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
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
                                    <a href="{{ route('pm.manage-task', ['filter' => request('filter', 'default')]) }}"
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
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="py-3 px-3 text-left font-medium whitespace-nowrap">Customer</th>
                            <th class="py-3 px-3 text-left font-medium whitespace-nowrap">Order</th>
                            <th class="py-3 px-3 text-center font-medium whitespace-nowrap">Date In</th>
                            <th class="py-3 px-3 text-center font-medium whitespace-nowrap">Date Out</th>
                            @foreach ($productionStages as $stage)
                                <th class="py-3 px-3 text-center font-medium whitespace-nowrap">{{ $stage->stage_name }}
                                </th>
                            @endforeach
                            <th class="py-3 px-3 text-center font-medium whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($orders as $order)
                            @php
                                // Get all order stages for this order
                                $orderStagesMap = $order->orderStages->keyBy('stage_id');
                            @endphp
                            <tr class="hover:bg-gray-50">
                                {{-- Customer --}}
                                <td class="py-3 px-3">
                                    <div>
                                        <p class="font-medium text-gray-900 whitespace-nowrap">
                                            {{ $order->customer->customer_name ?? '-' }}</p>
                                        <p class="text-[10px] text-gray-500">
                                            {{ $order->customer->phone ?? '-' }}</p>
                                    </div>
                                </td>

                                {{-- Order (Invoice + Product + Priority) --}}
                                <td class="py-3 px-3">
                                    <div class="max-w-[200px]">
                                        <p class="font-medium text-gray-900 text-[11px]">
                                            {{ $order->invoice->invoice_no ?? '-' }}
                                            {{ $order->productCategory->product_name ?? '-' }}
                                            @if ($order->priority === 'high')
                                                <span class="text-red-600 font-semibold">(HIGH)</span>
                                            @endif
                                        </p>
                                    </div>
                                </td>

                                {{-- Date In --}}
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="text-gray-700 text-[10px] whitespace-nowrap">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</span>
                                </td>

                                {{-- Date Out (Deadline) --}}
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="text-gray-700 text-[10px] whitespace-nowrap">{{ \Carbon\Carbon::parse($order->deadline)->format('d M Y') }}</span>
                                </td>

                                {{-- Production Stages --}}
                                @foreach ($productionStages as $stage)
                                    @php
                                        $orderStage = $orderStagesMap->get($stage->id);
                                        $hasDate = $orderStage && $orderStage->start_date && $orderStage->deadline;
                                        $status = $orderStage ? $orderStage->status : 'pending';
                                        $statusColors = [
                                            'pending' => 'text-gray-400',
                                            'in_progress' => 'text-yellow-500',
                                            'done' => 'text-green-500',
                                        ];
                                        $statusColor = $statusColors[$status] ?? 'text-gray-400';
                                    @endphp
                                    <td class="py-3 px-3">
                                        <div class="flex flex-col items-center gap-1">
                                            {{-- Date Button --}}
                                            <button type="button"
                                                @click="openStageModal({{ $order->id }}, {{ $stage->id }}, '{{ $stage->stage_name }}', '{{ $orderStage?->start_date?->format('Y-m-d') ?? '' }}', '{{ $orderStage?->deadline?->format('Y-m-d') ?? '' }}')"
                                                class="p-1 rounded hover:bg-gray-200 transition-colors" title="Set Date">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </button>

                                            {{-- Date Display --}}
                                            @if ($hasDate)
                                                <div class="text-[9px] text-center">
                                                    <div class="text-gray-600">
                                                        {{ $orderStage->start_date->format('d/m') }}</div>
                                                    <div class="text-gray-400">to</div>
                                                    <div class="text-gray-600">
                                                        {{ $orderStage->deadline->format('d/m') }}</div>
                                                </div>
                                            @else
                                                <div class="text-[9px] text-gray-400">No date</div>
                                            @endif

                                            {{-- Status Icon --}}
                                            <div class="flex items-center gap-1">
                                                @if ($status === 'done')
                                                    <svg class="w-4 h-4 {{ $statusColor }}" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @elseif ($status === 'in_progress')
                                                    <svg class="w-4 h-4 {{ $statusColor }}" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 {{ $statusColor }}" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @endforeach

                                {{-- Action Column --}}
                                <td class="py-3 px-3 text-center">
                                    <div class="relative inline-block text-left" x-data="{
                                        open: false,
                                        dropdownStyle: {},
                                        checkPosition() {
                                            const button = this.$refs.button;
                                            const dropdown = this.$refs.dropdown;
                                            const rect = button.getBoundingClientRect();
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            const spaceAbove = rect.top;
                                    
                                            // Get dropdown height (estimate or actual)
                                            const dropdownHeight = dropdown ? dropdown.offsetHeight : 150;
                                            const dropUp = spaceBelow < (dropdownHeight + 20) && spaceAbove > spaceBelow;
                                    
                                            // Position fixed dropdown
                                            if (dropUp) {
                                                this.dropdownStyle = {
                                                    position: 'fixed',
                                                    bottom: (window.innerHeight - rect.top + 8) + 'px',
                                                    left: (rect.right - 192) + 'px',
                                                    width: '192px',
                                                    top: 'auto'
                                                };
                                            } else {
                                                this.dropdownStyle = {
                                                    position: 'fixed',
                                                    top: (rect.bottom + 8) + 'px',
                                                    left: (rect.right - 192) + 'px',
                                                    width: '192px',
                                                    bottom: 'auto'
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
                                        <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md hover:bg-gray-100"
                                            title="Actions">
                                            <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                            </svg>
                                        </button>

                                        {{-- Dropdown Menu with Fixed Position --}}
                                        <div x-show="open" @click.away="open = false" x-cloak x-ref="dropdown"
                                            :style="dropdownStyle"
                                            class="bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-2">
                                            <button type="button"
                                                @click="open = false; 
                                                        openEditStageModal(
                                                            {{ $order->id }}, 
                                                            '{{ $order->invoice->invoice_no ?? '' }}',
                                                            {{ json_encode(
                                                                $order->orderStages->map(
                                                                    fn($os) => [
                                                                        'id' => $os->id,
                                                                        'stage_id' => $os->stage_id,
                                                                        'stage_name' => $os->productionStage->stage_name,
                                                                        'status' => $os->status,
                                                                    ],
                                                                ),
                                                            ) }},
                                                            '{{ addslashes($order->notes ?? '') }}'
                                                        )"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edit Stage
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($productionStages) + 1 }}"
                                    class="py-8 text-center text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="text-sm">No orders found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div id="pagination-section" class="mt-5 pt-5 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex-1 flex justify-start text-sm text-gray-600">
                        @if ($orders->total() > 0)
                            Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of
                            {{ $orders->total() }} results
                        @else
                            No results found
                        @endif
                    </div>
                    <div class="flex items-center">
                        @if ($orders->hasPages())
                            {{ $orders->links() }}
                        @else
                            <div class="pagination">
                                <span class="px-3 py-2 text-sm text-gray-500 bg-gray-50 rounded-md">Page 1 of 1</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= STAGE DATE MODAL ================= --}}
        <div x-show="showStageModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showStageModal = false; resetStageForm()"
                class="relative bg-white rounded-xl shadow-2xl max-w-md w-full">
                {{-- Header --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Set Stage Date - <span
                            x-text="selectedStageName"></span></h3>
                    <button @click="showStageModal = false; resetStageForm()"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form --}}
                <form
                    @submit.prevent="
                    isSubmittingStage = true;
                    stageErrors = {};
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('order_id', selectedOrderId);
                    formData.append('stage_id', selectedStageId);
                    formData.append('start_date', stageStartDate);
                    formData.append('deadline', stageDeadline);
                    
                    fetch('{{ route('pm.manage-task.update-stage') }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            sessionStorage.setItem('toast_message', data.message);
                            sessionStorage.setItem('toast_type', 'success');
                            window.location.reload();
                        } else {
                            stageErrors = data.errors || { general: data.message };
                            isSubmittingStage = false;
                        }
                    })
                    .catch(err => {
                        stageErrors = { general: 'Network error. Please try again.' };
                        isSubmittingStage = false;
                        console.error(err);
                    });
                ">
                    <div class="p-5 space-y-4">
                        {{-- Start Date --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" x-model="stageStartDate"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2">
                            <p x-show="stageErrors.start_date" x-text="stageErrors.start_date"
                                class="text-xs text-red-600 mt-1"></p>
                        </div>

                        {{-- Deadline --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deadline</label>
                            <input type="date" x-model="stageDeadline"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2">
                            <p x-show="stageErrors.deadline" x-text="stageErrors.deadline"
                                class="text-xs text-red-600 mt-1"></p>
                        </div>

                        {{-- General Error --}}
                        <p x-show="stageErrors.general" x-text="stageErrors.general"
                            class="text-xs text-red-600 bg-red-50 p-2 rounded"></p>
                    </div>

                    {{-- Footer --}}
                    <div class="flex gap-3 p-5 border-t border-gray-200">
                        <button type="button" @click="showStageModal = false; resetStageForm()"
                            class="flex-1 px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmittingStage"
                            class="flex-1 px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <span x-show="!isSubmittingStage">Apply</span>
                            <span x-show="isSubmittingStage">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= EDIT STAGE MODAL ================= --}}
        <div x-show="showEditStageModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showEditStageModal = false"
                class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col">
                {{-- Header --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Edit Stage Status</h3>
                        <p class="text-sm text-gray-500 mt-1">Order: <span class="font-medium"
                                x-text="editStageData.invoiceNo"></span></p>
                    </div>
                    <button @click="showEditStageModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="p-5 overflow-y-auto flex-1">
                    {{-- Order Notes - MOVED TO TOP --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Order Notes</h4>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap"
                                x-text="editStageData.orderNotes || 'No notes available'"></p>
                        </div>
                    </div>

                    {{-- Production Stages List --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Production Stages</h4>
                        <div class="space-y-3">
                            <template x-for="stage in editStageData.orderStages" :key="stage.id">
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        {{-- Stage Name & Current Status --}}
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                                :class="{
                                                    'bg-gray-100': stage.status === 'pending',
                                                    'bg-yellow-100': stage.status === 'in_progress',
                                                    'bg-green-100': stage.status === 'done'
                                                }">
                                                <svg class="w-5 h-5"
                                                    :class="{
                                                        'text-gray-400': stage.status === 'pending',
                                                        'text-yellow-500': stage.status === 'in_progress',
                                                        'text-green-500': stage.status === 'done'
                                                    }"
                                                    fill="currentColor" viewBox="0 0 20 20">
                                                    <template x-if="stage.status === 'done'">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd" />
                                                    </template>
                                                    <template x-if="stage.status === 'in_progress'">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                            clip-rule="evenodd" />
                                                    </template>
                                                    <template x-if="stage.status === 'pending'">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z"
                                                            clip-rule="evenodd" />
                                                    </template>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900" x-text="stage.stage_name"></p>
                                                <p class="text-xs text-gray-500">
                                                    Current: <span class="font-medium capitalize"
                                                        x-text="stage.status.replace('_', ' ')"></span>
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Status Buttons - ONLY PENDING AND DONE --}}
                                        <div class="flex gap-2">
                                            <button type="button" @click="updateStageStatus(stage.id, 'pending')"
                                                :disabled="isUpdatingStatus"
                                                :class="stage.status === 'pending' ? 'bg-gray-200 border-gray-300' :
                                                    'bg-white hover:bg-gray-100'"
                                                class="px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                Pending
                                            </button>
                                            <button type="button" @click="updateStageStatus(stage.id, 'done')"
                                                :disabled="isUpdatingStatus"
                                                :class="stage.status === 'done' ? 'bg-green-100 border-green-300' :
                                                    'bg-white hover:bg-green-50'"
                                                class="px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                Done
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 p-5 border-t border-gray-200 flex-shrink-0">
                    <button @click="showEditStageModal = false"
                        class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('styles')
    <style>
        /* Pagination styling */
        nav[aria-label="Pagination Navigation"] ul li span.pagination-active-page {
            background-color: #56ba9f !important;
            border-color: #56ba9f !important;
            color: white !important;
        }

        nav[aria-label="Pagination Navigation"] ul li span.pagination-active-page:hover {
            background-color: #489984 !important;
            border-color: #489984 !important;
            color: white !important;
        }

        nav[aria-label="Pagination Navigation"] ul li span,
        nav[aria-label="Pagination Navigation"] ul li a {
            min-width: 2rem;
            font-weight: 500;
        }

        nav[aria-label="Pagination Navigation"] ul li a:hover {
            background-color: #f3f4f6 !important;
            color: #111827 !important;
        }

        span[aria-current="page"] {
            background-color: #56ba9f !important;
            border-color: #56ba9f !important;
            color: white !important;
        }
    </style>
@endpush
