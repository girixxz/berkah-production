@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <x-nav-locate :items="['Menu', 'Dashboard']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        startDate: '{{ $startDate ?? '' }}',
        endDate: '{{ $endDate ?? '' }}',
        dateRange: '{{ $dateRange ?? '' }}',
        showDateFilter: false,
        showDateCustomRange: false,
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
            if (preset === 'last-month') {
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                this.startDate = lastMonth.toISOString().split('T')[0];
                this.endDate = lastMonthEnd.toISOString().split('T')[0];
                this.dateRange = 'last_month';
                this.applyFilter();
            } else if (preset === '1-week-ago') {
                const oneWeekAgo = new Date(today);
                oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
                this.startDate = oneWeekAgo.toISOString().split('T')[0];
                this.endDate = today.toISOString().split('T')[0];
                this.dateRange = 'last_7_days';
                this.applyFilter();
            } else if (preset === 'yesterday') {
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                this.startDate = yesterday.toISOString().split('T')[0];
                this.endDate = yesterday.toISOString().split('T')[0];
                this.dateRange = 'yesterday';
                this.applyFilter();
            } else if (preset === 'today') {
                this.startDate = today.toISOString().split('T')[0];
                this.endDate = today.toISOString().split('T')[0];
                this.dateRange = 'today';
                this.applyFilter();
            } else if (preset === 'this-month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                this.startDate = firstDay.toISOString().split('T')[0];
                this.endDate = lastDay.toISOString().split('T')[0];
                this.dateRange = 'this_month';
                this.applyFilter();
            } else if (preset === 'custom') {
                this.showDateCustomRange = true;
            }
        },
        applyFilter() {
            this.showDateFilter = false;
            this.showDateCustomRange = false;
            
            // Build URL with query params
            const params = new URLSearchParams();
            if (this.dateRange) params.set('date_range', this.dateRange);
            if (this.startDate) params.set('start_date', this.startDate);
            if (this.endDate) params.set('end_date', this.endDate);
            
            const url = '{{ route('owner.dashboard') }}?' + params.toString();
            
            // Update URL without reload
            window.history.pushState({}, '', url);
            
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
                const newSection = doc.getElementById('dashboard-stats');
                
                if (newSection) {
                    document.getElementById('dashboard-stats').innerHTML = newSection.innerHTML;
                }
                
                NProgress.done();
            })
            .catch(error => {
                console.error('Error:', error);
                NProgress.done();
            });
        }
    }" class="space-y-6">

        {{-- ================= DATE FILTER - Top Right ================= --}}
        <div class="flex justify-end">
            <div class="relative">
                <button type="button" @click="showDateFilter = !showDateFilter"
                    :class="dateRange ? 'border-primary bg-primary/5 text-primary' : 'border-gray-300 text-gray-700 bg-white'"
                    class="px-4 py-2 border rounded-md text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-text="getDateLabel()" class="whitespace-nowrap"></span>
                </button>

                {{-- Date Filter Modal --}}
                <div x-show="showDateFilter"
                    @click.away="showDateFilter = false; showDateCustomRange = false" x-cloak
                    class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-20">

                    {{-- Main Preset Options --}}
                    <div x-show="!showDateCustomRange" class="p-2">
                        <button @click="applyDatePreset('last-month')" type="button"
                            :class="dateRange === 'last_month' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                            Last Month
                        </button>
                        <button @click="applyDatePreset('1-week-ago')" type="button"
                            :class="dateRange === 'last_7_days' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                            Last 7 Days
                        </button>
                        <button @click="applyDatePreset('yesterday')" type="button"
                            :class="dateRange === 'yesterday' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                            Yesterday
                        </button>
                        <button @click="applyDatePreset('today')" type="button"
                            :class="dateRange === 'today' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                            Today
                        </button>
                        <button @click="applyDatePreset('this-month')" type="button"
                            :class="dateRange === 'this_month' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                            This Month
                        </button>
                        <div class="border-t border-gray-200 my-2"></div>
                        <button @click="applyDatePreset('custom')" type="button"
                            :class="dateRange === 'custom' ? 'bg-primary/10 text-primary font-semibold' : 'text-primary hover:bg-primary/5 font-medium'"
                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                            Custom Date
                        </button>
                    </div>

                    {{-- Custom Range Form --}}
                    <div x-show="showDateCustomRange" class="p-4">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="date" x-model="startDate"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="date" x-model="endDate"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button type="button" @click="dateRange = 'custom'; applyFilter();"
                                    class="flex-1 px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-primary-dark">
                                    Apply
                                </button>
                                <button type="button" @click="showDateCustomRange = false"
                                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
                                    Back
                                </button>
                            </div>
                        </div>

                        {{-- Clear Filter Link --}}
                        <button type="button" @click="startDate = ''; endDate = ''; dateRange = ''; applyFilter();"
                            class="w-full mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 text-center">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= STATISTICS CARDS ================= --}}
        <div id="dashboard-stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total QTY --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total QTY</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_qty']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Revenue --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp
                            {{ number_format($stats['revenue'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Bill --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Bill</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp
                            {{ number_format($stats['total_bill'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Remaining Due --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Remaining Due</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp
                            {{ number_format($stats['remaining_due'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Customers --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_customers']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Sales --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Sales</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_sales']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Products --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Products</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_products']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
