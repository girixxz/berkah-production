@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Orders']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        activeFilter: '{{ request('filter', 'default') }}',
        searchQuery: '{{ request('search') }}',
        startDate: '{{ $startDate ?? '' }}',
        endDate: '{{ $endDate ?? '' }}',
        dateRange: '{{ $dateRange ?? '' }}',
        showDateFilter: false,
        showDateCustomRange: false,
        datePreset: '',
        showCancelConfirm: null,
        showMoveToShippingConfirm: null,
        showAddPaymentModal: false,
        selectedOrderForPayment: null,
        paymentAmount: '',
        paymentErrors: {},
        isSubmittingPayment: false,
        init() {
            // Check for toast message from sessionStorage
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
        resetPaymentForm() {
            this.paymentAmount = '';
            this.paymentErrors = {};
            this.selectedOrderForPayment = null;
            this.isSubmittingPayment = false;
            setTimeout(() => {
                document.getElementById('addPaymentForm')?.reset();
            }, 100);
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
        <section id="orders-section">
            {{-- ================= SECTION 1: STATISTICS CARDS ================= --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total QTY --}}
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-purple-600 font-medium">Total QTY</p>
                            <p class="text-2xl font-bold text-purple-900 mt-1">{{ number_format($stats['total_qty']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Bill --}}
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Total Bill</p>
                            <p class="text-2xl font-bold text-green-900 mt-1">Rp
                                {{ number_format($stats['total_bill'], 0, ',', '.') }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Remaining Due --}}
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-orange-600 font-medium">Remaining Due</p>
                            <p class="text-2xl font-bold text-orange-900 mt-1">Rp
                                {{ number_format($stats['remaining_due'], 0, ',', '.') }}</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
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

                {{-- WIP --}}
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-blue-600 font-medium">WIP</p>
                            <p class="text-2xl font-bold text-blue-900 mt-1">{{ number_format($stats['wip']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Finished --}}
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Finished</p>
                            <p class="text-2xl font-bold text-green-900 mt-1">{{ number_format($stats['finished']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Cancelled --}}
                <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-red-600 font-medium">Cancelled</p>
                            <p class="text-2xl font-bold text-red-900 mt-1">{{ number_format($stats['cancelled']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-200/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                        {{-- Default - Green (Primary) --}}
                        <a href="{{ route('admin.orders.index', ['filter' => 'default'] + request()->except('filter')) }}"
                            :class="activeFilter === 'default' ? 'bg-primary text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Default
                        </a>
                        {{-- Pending - Yellow --}}
                        <a href="{{ route('admin.orders.index', ['filter' => 'pending'] + request()->except('filter')) }}"
                            :class="activeFilter === 'pending' ? 'bg-yellow-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-yellow-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Pending
                        </a>
                        {{-- WIP - Blue --}}
                        <a href="{{ route('admin.orders.index', ['filter' => 'wip'] + request()->except('filter')) }}"
                            :class="activeFilter === 'wip' ? 'bg-blue-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-blue-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            WIP
                        </a>
                        {{-- Finished - Green --}}
                        <a href="{{ route('admin.orders.index', ['filter' => 'finished'] + request()->except('filter')) }}"
                            :class="activeFilter === 'finished' ? 'bg-green-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-green-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Finished
                        </a>
                        {{-- Cancelled - Red --}}
                        <a href="{{ route('admin.orders.index', ['filter' => 'cancelled'] + request()->except('filter')) }}"
                            :class="activeFilter === 'cancelled' ? 'bg-red-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-red-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Cancelled
                        </a>
                    </div>

                    {{-- Right: Search, Date Filter, Create Order --}}
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">

                        {{-- Search & Date Filter - Same row on mobile --}}
                        <div class="flex gap-2 items-center xl:flex-1 xl:min-w-0">
                            {{-- Search - Flexible width yang bisa menyesuaikan --}}
                            <form method="GET" action="{{ route('admin.orders.index') }}"
                                class="flex-1 xl:min-w-[180px]" x-ref="searchForm">
                                <input type="hidden" name="filter" value="{{ request('filter', 'default') }}">
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
                                <form x-ref="dateForm" method="GET" action="{{ route('admin.orders.index') }}"
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
                                        action="{{ route('admin.orders.index') }}" class="p-4"
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
                                            <a href="{{ route('admin.orders.index', ['filter' => request('filter', 'default')]) }}"
                                                class="block w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 text-center">
                                                Reset Filter
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Create Order Button - Desktop (xl): Same row, Mobile: Separate row --}}
                        <a href="{{ route('admin.orders.create') }}"
                            class="w-full xl:w-auto px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm font-medium flex items-center justify-center gap-2 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Create Order
                        </a>
                    </div>
                </div>

                {{-- ================= SECTION 3: TABLE ================= --}}
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-light text-gray-600">
                            {{-- Finished Filter: Different Columns --}}
                            <tr x-show="activeFilter === 'finished'">
                                <th class="py-3 px-4 text-left font-medium rounded-l-lg">No Invoice</th>
                                <th class="py-3 px-4 text-left font-medium">Customer</th>
                                <th class="py-3 px-4 text-left font-medium">Product</th>
                                <th class="py-3 px-4 text-left font-medium">QTY</th>
                                <th class="py-3 px-4 text-left font-medium">Total Bill</th>
                                <th class="py-3 px-4 text-left font-medium">Remaining</th>
                                <th class="py-3 px-4 text-left font-medium">Status</th>
                                <th class="py-3 px-4 text-left font-medium">Finished Date</th>
                                <th class="py-3 px-4 text-left font-medium">Shipping Status</th>
                                <th class="py-3 px-4 text-center font-medium rounded-r-lg">Action</th>
                            </tr>
                            {{-- Other Filters: Default Columns --}}
                            <tr x-show="activeFilter !== 'finished'">
                                <th class="py-3 px-4 text-left font-medium rounded-l-lg">No Invoice</th>
                                <th class="py-3 px-4 text-left font-medium">Customer</th>
                                <th class="py-3 px-4 text-left font-medium">Product</th>
                                <th class="py-3 px-4 text-left font-medium">QTY</th>
                                <th class="py-3 px-4 text-left font-medium">Total Bill</th>
                                <th class="py-3 px-4 text-left font-medium">Remaining</th>
                                <th class="py-3 px-4 text-left font-medium">Order Date</th>
                                <th class="py-3 px-4 text-left font-medium">Deadline</th>
                                <th class="py-3 px-4 text-left font-medium">Status</th>
                                <th class="py-3 px-4 text-center font-medium rounded-r-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="">
                            @forelse ($orders as $order)
                                @php
                                    // Calculate pending transactions for this order
                                    $pendingPayments = $order->invoice
                                        ? $order->invoice->payments()->where('status', 'pending')->get()
                                        : collect();
                                    $pendingCount = $pendingPayments->count();
                                    $pendingAmount = $pendingPayments->sum('amount');
                                @endphp
                                {{-- Finished Filter: Different Row Structure --}}
                                <tr x-show="activeFilter === 'finished'" class="hover:bg-gray-50">
                                    {{-- Invoice No with Shipping Type and Priority --}}
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                class="font-medium text-gray-900">{{ $order->invoice->invoice_no ?? '-' }}</span>
                                            @if ($order->shipping_type)
                                                @if (strtolower($order->shipping_type) === 'pickup')
                                                    <span
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">PICKUP</span>
                                                @elseif (strtolower($order->shipping_type) === 'delivery')
                                                    <span
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 bg-blue-100 rounded">DELIVERY</span>
                                                @endif
                                            @endif
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

                                    {{-- Total Bill --}}
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">Rp
                                            {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</span>
                                    </td>

                                    {{-- Remaining --}}
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-red-600">Rp
                                            {{ number_format($order->invoice->amount_due ?? 0, 0, ',', '.') }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3 px-4">
                                        @php
                                            $statusClasses = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'wip' => 'bg-blue-100 text-blue-800',
                                                'finished' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusClass =
                                                $statusClasses[$order->production_status] ??
                                                'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                            {{ strtoupper($order->production_status) }}
                                        </span>
                                    </td>

                                    {{-- Finished Date --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->finished_date ? \Carbon\Carbon::parse($order->finished_date)->format('d M Y H:i') : '-' }}</span>
                                    </td>

                                    {{-- Shipping Status --}}
                                    <td class="py-3 px-4">
                                        @php
                                            $shippingStatusClasses = [
                                                'pending' => 'bg-orange-100 text-orange-800',
                                                'shipped' => 'bg-blue-100 text-blue-800',
                                            ];
                                            $shippingStatusClass =
                                                $shippingStatusClasses[$order->shipping_status] ??
                                                'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-medium {{ $shippingStatusClass }}">
                                            {{ strtoupper($order->shipping_status) }}
                                        </span>
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
                                                    {{-- View Detail --}}
                                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                                        class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View Detail
                                                    </a>

                                                    {{-- Edit (Hidden for finished/cancelled) --}}
                                                    @if ($order->production_status !== 'finished' && $order->production_status !== 'cancelled')
                                                        <a href="{{ route('admin.orders.edit', $order->id) }}"
                                                            class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            Edit
                                                        </a>
                                                    @endif

                                                    {{-- Add Payment (Hidden if no invoice, fully paid, or cancelled) --}}
                                                    @if ($order->invoice && $order->invoice->amount_due > 0 && $order->production_status !== 'cancelled')
                                                        <button type="button"
                                                            @click="selectedOrderForPayment = {{ json_encode(['id' => $order->id, 'invoice_no' => $order->invoice->invoice_no ?? 'N/A', 'invoice_id' => $order->invoice->id ?? null, 'remaining_due' => $order->invoice->amount_due ?? 0, 'pending_transaction' => $pendingCount, 'pending_amount' => $pendingAmount]) }}; showAddPaymentModal = true; paymentErrors = {}; open = false"
                                                            class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                            </svg>
                                                            Add Payment
                                                        </button>
                                                    @endif

                                                    {{-- Move to Shippings (Only for finished orders that haven't been shipped) --}}
                                                    @if ($order->production_status === 'finished' && $order->shipping_status === 'pending')
                                                        @php
                                                            $hasRemainingDue =
                                                                $order->invoice && $order->invoice->amount_due > 0;
                                                        @endphp
                                                        <button type="button"
                                                            @if (!$hasRemainingDue) @click="showMoveToShippingConfirm = {{ $order->id }}; open = false"
                                                            @else
                                                                disabled title="Complete payment first (Remaining: Rp {{ number_format($order->invoice->amount_due, 0, ',', '.') }})" @endif
                                                            class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 {{ $hasRemainingDue ? 'text-gray-400 cursor-not-allowed' : 'text-green-600 hover:bg-green-50' }}">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                            </svg>
                                                            Move to Shippings
                                                            @if ($hasRemainingDue)
                                                                <svg class="w-4 h-4 ml-auto" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                                </svg>
                                                            @endif
                                                        </button>
                                                    @endif

                                                    {{-- Cancel (Hidden for cancelled and finished) --}}
                                                    @if ($order->production_status !== 'cancelled' && $order->production_status !== 'finished')
                                                        <button type="button"
                                                            @click="showCancelConfirm = {{ $order->id }}; open = false"
                                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Cancel Order
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Other Filters: Default Row Structure --}}
                                <tr x-show="activeFilter !== 'finished'" class="hover:bg-gray-50">
                                    {{-- Invoice No with Shipping Type and Priority --}}
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span
                                                class="font-medium text-gray-900">{{ $order->invoice->invoice_no ?? '-' }}</span>
                                            @if ($order->shipping_type)
                                                @if (strtolower($order->shipping_type) === 'pickup')
                                                    <span
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">PICKUP</span>
                                                @elseif (strtolower($order->shipping_type) === 'delivery')
                                                    <span
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 bg-blue-100 rounded">DELIVERY</span>
                                                @endif
                                            @endif
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

                                    {{-- Total Bill --}}
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">Rp
                                            {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</span>
                                    </td>

                                    {{-- Remaining --}}
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-red-600">Rp
                                            {{ number_format($order->invoice->amount_due ?? 0, 0, ',', '.') }}</span>
                                    </td>

                                    {{-- Order Date --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</span>
                                    </td>

                                    {{-- Deadline --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d M Y') : '-' }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3 px-4">
                                        @php
                                            $statusClasses = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'wip' => 'bg-blue-100 text-blue-800',
                                                'finished' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusClass =
                                                $statusClasses[$order->production_status] ??
                                                'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                            {{ strtoupper($order->production_status) }}
                                        </span>
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
                                                    {{-- View Detail --}}
                                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                                        class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View Detail
                                                    </a>

                                                    {{-- Edit (Hidden for finished/cancelled) --}}
                                                    @if ($order->production_status !== 'finished' && $order->production_status !== 'cancelled')
                                                        <a href="{{ route('admin.orders.edit', $order->id) }}"
                                                            class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            Edit
                                                        </a>
                                                    @endif

                                                    {{-- Add Payment (Hidden if no invoice, fully paid, or cancelled) --}}
                                                    @if ($order->invoice && $order->invoice->amount_due > 0 && $order->production_status !== 'cancelled')
                                                        <button type="button"
                                                            @click="selectedOrderForPayment = {{ json_encode(['id' => $order->id, 'invoice_no' => $order->invoice->invoice_no ?? 'N/A', 'invoice_id' => $order->invoice->id ?? null, 'remaining_due' => $order->invoice->amount_due ?? 0, 'pending_transaction' => $pendingCount, 'pending_amount' => $pendingAmount]) }}; showAddPaymentModal = true; paymentErrors = {}; open = false"
                                                            class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                            </svg>
                                                            Add Payment
                                                        </button>
                                                    @endif

                                                    {{-- Move to Shippings (Only for finished orders that haven't been shipped) --}}
                                                    @if ($order->production_status === 'finished' && $order->shipping_status === 'pending')
                                                        @php
                                                            $hasRemainingDue =
                                                                $order->invoice && $order->invoice->amount_due > 0;
                                                        @endphp
                                                        <button type="button"
                                                            @if (!$hasRemainingDue) @click="showMoveToShippingConfirm = {{ $order->id }}; open = false"
                                                            @else
                                                                disabled title="Complete payment first (Remaining: Rp {{ number_format($order->invoice->amount_due, 0, ',', '.') }})" @endif
                                                            class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 {{ $hasRemainingDue ? 'text-gray-400 cursor-not-allowed' : 'text-green-600 hover:bg-green-50' }}">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                            </svg>
                                                            Move to Shippings
                                                            @if ($hasRemainingDue)
                                                                <svg class="w-4 h-4 ml-auto" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                                </svg>
                                                            @endif
                                                        </button>
                                                    @endif

                                                    {{-- Cancel (Hidden for cancelled and finished) --}}
                                                    @if ($order->production_status !== 'cancelled' && $order->production_status !== 'finished')
                                                        <button type="button"
                                                            @click="showCancelConfirm = {{ $order->id }}; open = false"
                                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Cancel Order
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                {{-- Empty State for Finished Filter --}}
                                <tr x-show="activeFilter === 'finished'">
                                    <td colspan="10" class="py-8 text-center text-gray-400">
                                        <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-sm">No orders found</p>
                                    </td>
                                </tr>
                                {{-- Empty State for Other Filters --}}
                                <tr x-show="activeFilter !== 'finished'">
                                    <td colspan="10" class="py-8 text-center text-gray-400">
                                        <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-sm">No orders found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div id="orders-pagination-container" class="mt-5">
                    @if ($orders->hasPages())
                        <x-custom-pagination :paginator="$orders" />
                    @endif
                </div>
        </section>

        {{-- ================= CANCEL CONFIRMATION MODAL ================= --}}
        <div x-show="showCancelConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showCancelConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                {{-- Icon --}}
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                    Cancel Order?
                </h3>

                {{-- Message --}}
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to cancel this order? This action cannot be undone and the order status will
                    be changed to <span class="font-semibold text-red-600">Cancelled</span>.
                </p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="showCancelConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        No, Keep Order
                    </button>
                    <form :action="'{{ route('admin.orders.index') }}/' + showCancelConfirm + '/cancel'" method="POST"
                        class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                            Yes, Cancel Order
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ================= MOVE TO SHIPPING CONFIRMATION MODAL ================= --}}
        <div x-show="showMoveToShippingConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showMoveToShippingConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                {{-- Icon --}}
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                    Move Order to Shippings?
                </h3>

                {{-- Message --}}
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to move this finished order to the shipping page? The shipping status will be
                    changed to <span class="font-semibold text-green-600">Shipped</span> and the order will be available on
                    the Shippings page.
                </p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="showMoveToShippingConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form :action="`{{ url('admin/orders') }}/${showMoveToShippingConfirm}/move-to-shipping`"
                        method="POST" class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 transition-colors">
                            Yes, Move to Shippings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ================= ADD PAYMENT MODAL ================= --}}
        <div x-show="showAddPaymentModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.away="showAddPaymentModal = false; resetPaymentForm()"
                    class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                    {{-- Header --}}
                    <div class="flex items-center justify-between p-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Add Payment</h3>
                        <button @click="showAddPaymentModal = false; resetPaymentForm()"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            ✕
                        </button>
                    </div>

                    {{-- Form --}}
                    <form id="addPaymentForm"
                        @submit.prevent="
                            isSubmittingPayment = true;
                            const formData = new FormData($event.target);
                            fetch('{{ route('admin.payments.store') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    // Success - reload with toast
                                    sessionStorage.setItem('toast_message', 'Payment added successfully');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else {
                                    isSubmittingPayment = false;
                                    paymentErrors = data.errors || {};
                                    if (data.message && !data.errors) {
                                        // Show toast for general errors
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingPayment = false;
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: 'Failed to add payment. Please try again.', type: 'error' }
                                }));
                                console.error(err);
                            });
                        ">
                        <input type="hidden" name="invoice_id" :value="selectedOrderForPayment?.invoice_id">

                        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                            {{-- Invoice Info --}}
                            <div class="bg-gray-50 border border-gray-200 rounded-md p-3 space-y-3">
                                {{-- Row 1: Invoice No & Remaining Due --}}
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Invoice No</p>
                                        <p class="text-sm font-semibold text-gray-900"
                                            x-text="selectedOrderForPayment?.invoice_no || '-'"></p>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <p class="text-xs text-gray-500">Remaining Due</p>
                                        <p class="text-sm font-semibold text-red-600"
                                            x-text="'Rp ' + Math.floor(selectedOrderForPayment?.remaining_due || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                                        </p>
                                    </div>
                                </div>

                                {{-- Row 2: Pending Transaction & Pending Amount --}}
                                <div class="flex items-start justify-between gap-4 pt-2 border-t border-gray-300">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Pending Transaction</p>
                                        <p class="text-sm font-semibold text-gray-900"
                                            x-text="(selectedOrderForPayment?.pending_transaction || 0) + ' Transaction(s)'">
                                        </p>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <p class="text-xs text-gray-500">Pending Amount</p>
                                        <p class="text-sm font-semibold text-orange-600"
                                            x-text="'Rp ' + Math.floor(selectedOrderForPayment?.pending_amount || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Method --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Method <span class="text-red-600">*</span>
                                </label>
                                <select name="payment_method"
                                    :class="paymentErrors.payment_method ?
                                        'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                        'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                    <option value="">-- Select Method --</option>
                                    <option value="tranfer">Transfer</option>
                                    <option value="cash">Cash</option>
                                </select>
                                <p x-show="paymentErrors.payment_method" x-cloak
                                    x-text="paymentErrors.payment_method?.[0]" class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Payment Type --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Type <span class="text-red-600">*</span>
                                </label>
                                <select name="payment_type"
                                    @change="
                                        const selectedType = $event.target.value;
                                        if (selectedType === 'repayment' || selectedType === 'full_payment') {
                                            const remainingDue = selectedOrderForPayment?.remaining_due || 0;
                                            paymentAmount = Math.floor(remainingDue).toLocaleString('id-ID');
                                        }
                                    "
                                    :class="paymentErrors.payment_type ?
                                        'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                        'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                    <option value="">-- Select Type --</option>
                                    <option value="dp">DP (Down Payment)</option>
                                    <option value="repayment">Repayment</option>
                                    <option value="full_payment">Full Payment</option>
                                </select>
                                <p x-show="paymentErrors.payment_type" x-cloak x-text="paymentErrors.payment_type?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Amount <span class="text-red-600">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                    <input type="text" x-model="paymentAmount"
                                        @input="
                                            let value = $event.target.value.replace(/[^\d]/g, '');
                                            paymentAmount = parseInt(value || 0).toLocaleString('id-ID');
                                            $event.target.nextElementSibling.value = value;
                                        "
                                        placeholder="0"
                                        :class="paymentErrors.amount ?
                                            'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                            'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full rounded-md pl-10 pr-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                    <input type="hidden" name="amount" :value="paymentAmount.replace(/[^\d]/g, '')">
                                </div>
                                <p x-show="paymentErrors.amount" x-cloak x-text="paymentErrors.amount?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Payment Proof Image --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Proof <span class="text-red-600">*</span>
                                </label>
                                <input type="file" name="image" accept="image/jpeg,image/png,image/jpg"
                                    :class="paymentErrors.image ?
                                        'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                        'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                <p class="mt-1 text-xs text-gray-500">Max 10MB. Format: JPG, PNG, JPEG</p>
                                <p x-show="paymentErrors.image" x-cloak x-text="paymentErrors.image?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="3"
                                    :class="paymentErrors.notes ?
                                        'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                        'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    placeholder="Optional payment notes..."
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700"></textarea>
                                <p x-show="paymentErrors.notes" x-cloak x-text="paymentErrors.notes?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex justify-end gap-3 p-5 border-t border-gray-200">
                            <button type="button" @click="showAddPaymentModal = false; resetPaymentForm()"
                                :disabled="isSubmittingPayment"
                                class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingPayment"
                                class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed flex items-center gap-2">
                                {{-- Loading Spinner --}}
                                <svg x-show="isSubmittingPayment" x-cloak class="animate-spin h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="isSubmittingPayment ? 'Processing...' : 'Add Payment'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Pagination AJAX Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('orders-pagination-container', 'orders-section');
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
