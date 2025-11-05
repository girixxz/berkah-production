@extends('layouts.app')

@section('title', 'Payment History')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Payment History']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        activeFilter: '{{ request('filter', 'default') }}',
        searchQuery: '{{ request('search') }}',
        startDate: '{{ $startDate ?? '' }}',
        endDate: '{{ $endDate ?? '' }}',
        dateRange: '{{ $dateRange ?? '' }}',
        showDateFilter: false,
        showDateCustomRange: false,
        showImageModal: false,
        selectedImage: '',
        showActionConfirm: null,
        actionType: '',
        init() {
            // Check for toast message from session
            @if (session('message')) setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { 
                            message: '{{ session('message') }}', 
                            type: '{{ session('alert-type', 'success') }}'
                        }
                    }));
                }, 300); @endif
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            {{-- Total Transactions --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_transactions']) }}
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

            {{-- Pending --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($stats['pending']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Approved --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Approved</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['approved']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Rejected --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Rejected</p>
                        <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats['rejected']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>


            {{-- Total Balance (Only Approved) --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp
                            {{ number_format($stats['total_balance'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 2: FILTER & ACTIONS ================= --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 mt-6">
            {{-- Mobile: Vertikal | Desktop (1280px+): Horizontal dengan filter kiri, actions kanan --}}
            <div class="flex flex-col xl:flex-row xl:items-center gap-4">

                {{-- Left: Filter Buttons - Grid 3 kolom di mobile, flex di desktop --}}
                <div class="grid grid-cols-3 md:flex md:flex-wrap gap-2">
                    {{-- All - Primary --}}
                    <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => 'default'] + request()->except('filter')) }}"
                        :class="activeFilter === 'default' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        All
                    </a>
                    {{-- Pending - Yellow --}}
                    <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => 'pending'] + request()->except('filter')) }}"
                        :class="activeFilter === 'pending' ? 'bg-yellow-500 text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-yellow-50'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        Pending
                    </a>
                    {{-- Approved - Green --}}
                    <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => 'approved'] + request()->except('filter')) }}"
                        :class="activeFilter === 'approved' ? 'bg-green-500 text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-green-50'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        Approved
                    </a>
                    {{-- Rejected - Red --}}
                    <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => 'rejected'] + request()->except('filter')) }}"
                        :class="activeFilter === 'rejected' ? 'bg-red-500 text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-red-50'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        Rejected
                    </a>
                    {{-- DP - Primary --}}
                    <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => 'dp'] + request()->except('filter')) }}"
                        :class="activeFilter === 'dp' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        DP
                    </a>
                    {{-- Repayment - Primary --}}
                    <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => 'repayment'] + request()->except('filter')) }}"
                        :class="activeFilter === 'repayment' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        Repayment
                    </a>
                    {{-- Full Payment - Primary --}}
                    <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => 'full_payment'] + request()->except('filter')) }}"
                        :class="activeFilter === 'full_payment' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        Full
                    </a>
                </div>

                {{-- Right: Search & Date Filter --}}
                <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">

                    {{-- Search & Date Filter - Same row on mobile --}}
                    <div class="flex gap-2 items-center xl:flex-1 xl:min-w-0">
                        {{-- Search - Flexible width yang bisa menyesuaikan --}}
                        <form method="GET"
                            action="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history') }}"
                            class="flex-1 xl:min-w-[180px]" x-ref="searchForm">
                            <input type="hidden" name="filter" value="{{ request('filter', 'default') }}">
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
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
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
                                {{-- Active indicator dot - always visible --}}
                                <span x-show="dateRange" x-cloak class="w-2 h-2 bg-primary rounded-full"></span>
                            </button>

                            {{-- Hidden Form for Date Presets --}}
                            <form x-ref="dateForm" method="GET"
                                action="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history') }}"
                                class="hidden">
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
                                        :class="dateRange === 'last_month' ? 'bg-primary/10 text-primary font-medium' :
                                            'text-gray-700 hover:bg-gray-50'"
                                        class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                        Bulan Lalu
                                    </button>
                                    <button @click="applyDatePreset('1-week-ago')" type="button"
                                        :class="dateRange === 'last_7_days' ? 'bg-primary/10 text-primary font-medium' :
                                            'text-gray-700 hover:bg-gray-50'"
                                        class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                        1 Minggu Yang Lalu
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
                                        :class="dateRange === 'custom' ? 'bg-primary/10 text-primary font-medium' :
                                            'text-primary hover:bg-primary/5'"
                                        class="w-full text-left px-4 py-2.5 text-sm font-medium rounded-md transition-colors">
                                        Custom Date
                                    </button>
                                </div>

                                {{-- Custom Range Form --}}
                                <form x-show="showDateCustomRange" method="GET"
                                    action="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history') }}"
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
                                        <a href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history', ['filter' => request('filter', 'default')]) }}"
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
                            <th class="py-3 px-4 text-left font-medium rounded-l-lg">Paid At</th>
                            <th class="py-3 px-4 text-left font-medium">Customer</th>
                            <th class="py-3 px-4 text-left font-medium">Order</th>
                            <th class="py-3 px-4 text-left font-medium">Payment Model</th>
                            <th class="py-3 px-4 text-left font-medium">Amount</th>
                            <th class="py-3 px-4 text-left font-medium">Status</th>
                            <th class="py-3 px-4 text-left font-medium">Notes</th>
                            <th class="py-3 px-4 text-center font-medium">Attachment</th>
                            @if ($role === 'owner')
                                <th class="py-3 px-4 text-center font-medium rounded-r-lg">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($payments as $payment)
                            <tr class="hover:bg-gray-50">
                                {{-- Paid At --}}
                                <td class="py-3 px-4">
                                    <span
                                        class="text-gray-700">{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y H:i') }}</span>
                                </td>

                                {{-- Customer --}}
                                <td class="py-3 px-4">
                                    <div>
                                        <p class="text-gray-700">
                                            {{ $payment->invoice->order->customer->customer_name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $payment->invoice->order->customer->phone ?? '-' }}</p>
                                    </div>
                                </td>

                                {{-- Order (Invoice + Product + Priority) --}}
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-medium text-gray-900">
                                            {{ $payment->invoice->invoice_no ?? '-' }}
                                        </span>
                                        @if ($payment->invoice->order->productCategory)
                                            <span
                                                class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">
                                                {{ strtoupper($payment->invoice->order->productCategory->product_name) }}
                                            </span>
                                        @endif
                                        @if (isset($payment->invoice->order->priority) && strtolower($payment->invoice->order->priority) === 'high')
                                            <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Payment Model (Method + Type) --}}
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @php
                                            $methodClass =
                                                $payment->payment_method === 'tranfer'
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : 'bg-purple-100 text-purple-800';
                                            $typeClasses = [
                                                'dp' => 'bg-yellow-100 text-yellow-800',
                                                'repayment' => 'bg-orange-100 text-orange-800',
                                                'full_payment' => 'bg-green-100 text-green-800',
                                            ];
                                            $typeClass =
                                                $typeClasses[$payment->payment_type] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span
                                            class="px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $methodClass }} inline-block w-fit">
                                            {{ strtoupper($payment->payment_method) }}
                                        </span>
                                        <span
                                            class="px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $typeClass }} inline-block w-fit">
                                            {{ strtoupper(str_replace('_', ' ', $payment->payment_type)) }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Amount --}}
                                <td class="py-3 px-4">
                                    <span class="text-gray-700">Rp
                                        {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                </td>

                                {{-- Status --}}
                                <td class="py-3 px-4">
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusClass = $statusClasses[$payment->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ strtoupper($payment->status) }}
                                    </span>
                                </td>

                                {{-- Notes --}}
                                <td class="py-3 px-4">
                                    <span class="text-gray-700 text-xs">{{ $payment->notes ?? '-' }}</span>
                                </td>

                                {{-- Attachment --}}
                                <td class="py-3 px-4 text-center">
                                    @if ($payment->img_url)
                                        <button @click="selectedImage = '{{ $payment->img_url }}'; showImageModal = true"
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-xs font-medium cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            View
                                        </button>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                {{-- Action (Owner Only) --}}
                                @if ($role === 'owner')
                                    <td class="py-3 px-4">
                                        <div class="flex justify-center gap-2">
                                            @if ($payment->status === 'pending')
                                                {{-- Approve Button --}}
                                                <button type="button"
                                                    @click="showActionConfirm = {{ $payment->id }}; actionType = 'approve'"
                                                    class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-md hover:bg-green-200 text-xs font-medium cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Approve
                                                </button>

                                                {{-- Reject Button --}}
                                                <button type="button"
                                                    @click="showActionConfirm = {{ $payment->id }}; actionType = 'reject'"
                                                    class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 text-xs font-medium cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Reject
                                                </button>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $role === 'owner' ? '9' : '8' }}" class="py-8 text-center text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="text-sm">No payments found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div id="pagination-section" class="mt-5">
                @if ($payments->hasPages())
                    <x-custom-pagination :paginator="$payments" />
                @endif
            </div>

            {{-- ================= IMAGE MODAL ================= --}}
            <div x-show="showImageModal" x-cloak x-transition.opacity
                class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
                <div @click.away="showImageModal = false; selectedImage = ''"
                    class="relative bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                    {{-- Header --}}
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Payment Proof</h3>
                        <button @click="showImageModal = false; selectedImage = ''"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Image --}}
                    <div class="p-4 overflow-auto max-h-[calc(90vh-80px)]">
                        <img :src="selectedImage" alt="Payment Proof" class="w-full h-auto rounded-lg">
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-end gap-3 p-4 border-t border-gray-200">
                        <a :href="selectedImage" target="_blank"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm font-medium">
                            Open in New Tab
                        </a>
                        <button @click="showImageModal = false; selectedImage = ''"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            {{-- ================= ACTION CONFIRMATION MODAL (OWNER ONLY) ================= --}}
            @if ($role === 'owner')
                <div x-show="showActionConfirm !== null" x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
                    style="background-color: rgba(0, 0, 0, 0.5);">
                    <div @click.away="showActionConfirm = null; actionType = ''"
                        class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                        {{-- Icon --}}
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full"
                            :class="actionType === 'approve' ? 'bg-green-100' : 'bg-red-100'">
                            <svg class="w-6 h-6" :class="actionType === 'approve' ? 'text-green-600' : 'text-red-600'"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="actionType === 'approve'" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                <path x-show="actionType === 'reject'" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                            <span x-text="actionType === 'approve' ? 'Approve Payment?' : 'Reject Payment?'"></span>
                        </h3>

                        {{-- Message --}}
                        <p class="text-sm text-gray-600 text-center mb-6">
                            <span x-show="actionType === 'approve'">
                                Are you sure you want to approve this payment? If this is the first approved payment, the
                                order will be moved to <span class="font-semibold text-blue-600">WIP</span> status.
                            </span>
                            <span x-show="actionType === 'reject'">
                                Are you sure you want to reject this payment? This action cannot be undone.
                            </span>
                        </p>

                        {{-- Actions --}}
                        <div class="flex gap-3">
                            <button type="button" @click="showActionConfirm = null; actionType = ''"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <form
                                :action="actionType === 'approve' ? '{{ url('owner/payments') }}/' + showActionConfirm +
                                    '/approve' : '{{ url('owner/payments') }}/' + showActionConfirm + '/reject'"
                                method="POST" class="flex-1">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="w-full px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                    :class="actionType === 'approve' ? 'bg-green-600 text-white hover:bg-green-700' :
                                        'bg-red-600 text-white hover:bg-red-700'">
                                    <span x-text="actionType === 'approve' ? 'Yes, Approve' : 'Yes, Reject'"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

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
