@extends('layouts.app')

@section('title', 'Customer Detail')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Customers', 'Detail']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        searchQuery: '{{ request('search') }}',
        startDate: '{{ $startDate ? $startDate->format('Y-m-d') : '' }}',
        endDate: '{{ $endDate ? $endDate->format('Y-m-d') : '' }}',
        dateRange: '{{ $dateRange ?? 'this_month' }}',
        showDateFilter: false,
        showDateCustomRange: false,
        locationNames: {
            province: 'Loading...',
            city: 'Loading...',
            district: 'Loading...',
            village: 'Loading...'
        },
        async fetchLocationNames() {
            const provinceId = '{{ $customer->province_id }}';
            const cityId = '{{ $customer->city_id }}';
            const districtId = '{{ $customer->district_id }}';
            const villageId = '{{ $customer->village_id }}';

            if (!provinceId && !cityId && !districtId && !villageId) {
                this.locationNames = {
                    province: '-',
                    city: '-',
                    district: '-',
                    village: '-'
                };
                return;
            }

            try {
                // Fetch all locations in parallel
                const [provinces, cities, districts, villages] = await Promise.all([
                    provinceId ? fetch('{{ url('/admin/customers/api/provinces') }}').then(r => r.json()) : Promise.resolve([]),
                    cityId && provinceId ? fetch(`{{ url('/admin/customers/api/cities') }}/${provinceId}`).then(r => r.json()) : Promise.resolve([]),
                    districtId && cityId ? fetch(`{{ url('/admin/customers/api/districts') }}/${cityId}`).then(r => r.json()) : Promise.resolve([]),
                    villageId && districtId ? fetch(`{{ url('/admin/customers/api/villages') }}/${districtId}`).then(r => r.json()) : Promise.resolve([])
                ]);

                // Find matching names
                const province = provinces.find(p => p.id == provinceId);
                const city = cities.find(c => c.id == cityId);
                const district = districts.find(d => d.id == districtId);
                const village = villages.find(v => v.id == villageId);

                this.locationNames = {
                    province: province ? province.province_name : '-',
                    city: city ? city.city_name : '-',
                    district: district ? district.district_name : '-',
                    village: village ? village.village_name : '-'
                };
            } catch (error) {
                console.error('Error fetching location names:', error);
                this.locationNames = {
                    province: '-',
                    city: '-',
                    district: '-',
                    village: '-'
                };
            }
        },
        matchesSearch(row) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const query = this.searchQuery.toLowerCase();
            const invoiceNo = (row.getAttribute('data-invoice') || '').toLowerCase();
            const product = (row.getAttribute('data-product') || '').toLowerCase();
            return invoiceNo.includes(query) || product.includes(query);
        },
        get hasVisibleRows() {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const desktopRows = document.querySelectorAll('tbody tr[data-invoice]');
            const mobileCards = document.querySelectorAll('.xl\\\\:hidden > div[data-invoice]');
            
            for (let row of desktopRows) {
                if (this.matchesSearch(row)) return true;
            }
            for (let card of mobileCards) {
                if (this.matchesSearch(card)) return true;
            }
            return false;
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
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.dateRange) params.set('date_range', this.dateRange);
            if (this.startDate) params.set('start_date', this.startDate);
            if (this.endDate) params.set('end_date', this.endDate);
            
            // Include per_page parameter
            const perPageValue = this.getPerPageValue();
            if (perPageValue) params.set('per_page', perPageValue);
            
            const url = '{{ route('admin.customers.show', $customer) }}?' + params.toString();
            
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
                const newSection = doc.getElementById('customer-orders-section');
                
                if (newSection) {
                    document.getElementById('customer-orders-section').innerHTML = newSection.innerHTML;
                    setupPagination('customer-orders-pagination-container', 'customer-orders-section');
                }
                
                NProgress.done();
            })
            .catch(error => {
                console.error('Error:', error);
                NProgress.done();
            });
        },
        getPerPageValue() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('per_page') || '15';
        }
    }" x-init="fetchLocationNames()">

        {{-- GRID LAYOUT: Table (col-span-8) + Card (col-span-4) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            
            {{-- ================= LEFT: DATA ORDERS TABLE (col-span-8 = 2/3 width) ================= --}}
            <section id="customer-orders-section" class="lg:col-span-9 order-2 lg:order-1">
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    {{-- Header --}}
                    <div class="flex flex-col gap-3 md:flex-row md:items-center mb-5">
                        <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">Data Orders</h2>

                        {{-- Search & Date Filter --}}
                        <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                            {{-- Search --}}
                            <div class="relative flex-1 md:w-72">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" x-model="searchQuery" x-ref="searchInput"
                                    placeholder="Search invoice, product..."
                                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>

                            {{-- Show Per Page Dropdown --}}
                            <div x-data="{
                                open: false,
                                perPage: {{ request('per_page', 15) }},
                                options: [
                                    { value: 5, label: '5' },
                                    { value: 10, label: '10' },
                                    { value: 15, label: '15' },
                                    { value: 20, label: '20' },
                                    { value: 25, label: '25' }
                                ],
                                get selected() {
                                    return this.options.find(o => o.value === this.perPage) || this.options[2];
                                },
                                selectOption(option) {
                                    this.perPage = option.value;
                                    this.open = false;
                                    this.applyPerPageFilter();
                                },
                                applyPerPageFilter() {
                                    // Build URL with all existing params + per_page
                                    const params = new URLSearchParams(window.location.search);
                                    params.set('per_page', this.perPage);
                                    params.delete('page'); // Reset to page 1
                                    
                                    const url = '{{ route('admin.customers.show', $customer) }}?' + params.toString();
                                    
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
                                        const newSection = doc.getElementById('customer-orders-section');
                                        
                                        if (newSection) {
                                            document.getElementById('customer-orders-section').innerHTML = newSection.innerHTML;
                                            setupPagination('customer-orders-pagination-container', 'customer-orders-section');
                                        }
                                        
                                        NProgress.done();
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        NProgress.done();
                                    });
                                }
                            }" class="relative flex-shrink-0">
                                {{-- Trigger Button --}}
                                <button type="button" @click="open = !open"
                                    class="w-14 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                        focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="selected.label"></span>
                                    <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                {{-- Dropdown --}}
                                <div x-show="open" @click.away="open = false" x-cloak 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" 
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" 
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-14 bg-white border border-gray-200 rounded-md shadow-lg">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="option in options" :key="option.value">
                                            <li @click="selectOption(option)"
                                                class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                                :class="{ 'bg-primary/10 font-medium text-primary': perPage === option.value }">
                                                <span x-text="option.label"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            {{-- Date Filter --}}
                            <div class="relative flex-shrink-0">
                                <button type="button" @click="showDateFilter = !showDateFilter"
                                    :class="dateRange ? 'border-primary bg-primary/5 text-primary' :
                                        'border-gray-300 text-gray-700 bg-white'"
                                    class="px-3 lg:px-4 py-2 border rounded-md text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span x-text="getDateLabel()" class="hidden lg:inline whitespace-nowrap"></span>
                                </button>

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
                    </div>

                    {{-- Desktop Table View --}}
                    <div class="hidden xl:block overflow-x-auto">
                        <table class="min-w-full text-sm table-fixed">
                            <thead class="bg-primary-light text-gray-600">
                                <tr>
                                    <th class="py-2 pl-[60px] text-left font-medium rounded-l-lg w-[180px]">Mockup</th>
                                    <th class="py-2 pl-[60px] text-left font-medium w-[170px]">Order Detail</th>
                                    <th class="py-2 px-2 text-center font-medium w-[100px]">Total Design</th>
                                    <th class="py-2 px-2 text-left font-medium w-[150px]">Total Bill</th>
                                    <th class="py-2 px-2 text-left font-medium w-[150px]">Remaining Due</th>
                                    <th class="py-2 px-2 text-left font-medium w-[130px]">Finished Date</th>
                                    <th class="py-2 px-2 text-center font-medium rounded-r-lg w-[100px]">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    @php
                                        $totalDesigns = $order->designVariants->count();
                                    @endphp
                                    <tr class="border-t border-gray-200 hover:bg-gray-50 cursor-pointer" x-data="{ showImageModal: false }"
                                        x-show="matchesSearch($el)"
                                        data-invoice="{{ $order->invoice->invoice_number ?? '' }}"
                                        data-product="{{ $order->productCategory->product_name ?? '' }}"
                                        onclick="window.open('{{ route('admin.orders.show', $order->id) }}', '_blank')">
                                        {{-- Mockup Column --}}
                                        <td class="py-2 px-2 text-left">
                                            <div @click="showImageModal = true; $event.stopPropagation()" 
                                                class="inline-block w-40 h-25 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity">
                                                @if ($order->img_url)
                                                    <img src="{{ route('admin.orders.image', $order) }}" alt="Order Image" 
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

                                        {{-- Order Detail Column --}}
                                        <td class="py-2 px-2">
                                            <div class="space-y-0.5">
                                                <div>
                                                    <span class="inline-block w-16 text-xs text-gray-500">INVOICE</span>
                                                    <span class="text-xs text-gray-500">:</span>
                                                    <span class="ml-1 text-sm font-bold text-gray-900">
                                                        {{ $order->invoice->invoice_no ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="inline-block w-16 text-xs text-gray-500">PRODUCT</span>
                                                    <span class="text-xs text-gray-500">:</span>
                                                    <span class="ml-1 text-sm font-semibold text-gray-900">
                                                        {{ $order->productCategory->product_name ?? '-' }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="inline-block w-16 text-xs text-gray-500">MATERIAL</span>
                                                    <span class="text-xs text-gray-500">:</span>
                                                    <span class="ml-1 text-sm text-gray-700">
                                                        {{ $order->materialCategory->material_name ?? '-' }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="inline-block w-16 text-xs text-gray-500">QTY</span>
                                                    <span class="text-xs text-gray-500">:</span>
                                                    <span class="ml-1 text-sm font-semibold text-gray-900">
                                                        {{ number_format($order->total_qty) }} pcs
                                                    </span>
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
                                                        <img src="{{ route('admin.orders.image', $order) }}" 
                                                            alt="Order Image Full" 
                                                            class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
                                                    </div>
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Total Design Column --}}
                                        <td class="py-2 px-2 text-center">
                                            <div class="inline-flex items-center justify-center w-9 h-9 bg-gray-100 rounded-full">
                                                <span class="text-sm font-bold text-gray-900">{{ $totalDesigns }}</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5">Design</p>
                                        </td>

                                        {{-- Total Bill Column --}}
                                        <td class="py-2 px-2">
                                            <span class="text-sm font-semibold text-gray-900">Rp {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</span>
                                        </td>

                                        {{-- Remaining Due Column --}}
                                        <td class="py-2 px-2">
                                            <span class="text-sm font-semibold text-red-600">Rp {{ number_format($order->invoice->amount_due ?? 0, 0, ',', '.') }}</span>
                                        </td>

                                        {{-- Finished Date Column --}}
                                        <td class="py-2 px-2">
                                            @if ($order->production_status === 'finished' && $order->finished_date)
                                                <span class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($order->finished_date)->format('d M Y') }}</span>
                                            @else
                                                <span class="text-sm text-gray-400">-</span>
                                            @endif
                                        </td>

                                        {{-- Status Column --}}
                                        <td class="py-2 px-2 text-center">
                                            @if ($order->production_status === 'wip')
                                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                                    WIP
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    FINISHED
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr x-show="!searchQuery">
                                        <td colspan="7" class="py-8 text-center text-gray-500">
                                            No orders found
                                        </td>
                                    </tr>
                                @endforelse
                                
                                {{-- No Search Results Message --}}
                                <tr x-show="searchQuery && !hasVisibleRows" x-cloak>
                                    <td colspan="7" class="py-8 text-center text-gray-400">
                                        <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-700">No results found for "<span x-text="searchQuery"></span>"</p>
                                        <p class="text-xs text-gray-500 mt-1">Try different keywords or filters</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile/Tablet Card View --}}
                    <div class="xl:hidden space-y-4">
                        @forelse ($orders as $order)
                            @php
                                $totalDesigns = $order->designVariants->count();
                            @endphp
                            
                            <div x-data="{ showImageModal: false }" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:bg-gray-50 cursor-pointer"
                                x-show="matchesSearch($el)"
                                data-invoice="{{ $order->invoice->invoice_number ?? '' }}"
                                data-product="{{ $order->productCategory->product_name ?? '' }}"
                                onclick="window.open('{{ route('admin.orders.show', $order->id) }}', '_blank')">
                                {{-- Header: Invoice & Status --}}
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-base text-gray-900">
                                            {{ $order->invoice->invoice_no ?? 'N/A' }}
                                        </h3>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        @if ($order->production_status === 'wip')
                                            <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                                WIP
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                FINISHED
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Content: Image & Info --}}
                                <div class="flex gap-3 mb-3">
                                    {{-- Mockup Image --}}
                                    <div @click="showImageModal = true; $event.stopPropagation()" 
                                        class="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity">
                                        @if ($order->img_url)
                                            <img src="{{ route('admin.orders.image', $order) }}" alt="Order Image" 
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

                                {{-- Footer: Design Count, Bill, Remaining, Finished Date --}}
                                <div class="pt-2 border-t border-gray-100 space-y-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1">
                                            <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center">
                                                <span class="text-xs font-bold text-gray-900">{{ $totalDesigns }}</span>
                                            </div>
                                            <span class="text-gray-500">Design</span>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-gray-500">Total Bill</div>
                                            <div class="font-semibold text-gray-900">Rp {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="text-gray-500">Remaining Due</div>
                                        <div class="font-semibold text-red-600">Rp {{ number_format($order->invoice->amount_due ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="text-gray-500">Finished Date</div>
                                        <div class="font-medium text-gray-900">
                                            @if ($order->production_status === 'finished' && $order->finished_date)
                                                {{ \Carbon\Carbon::parse($order->finished_date)->format('d M Y') }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </div>
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
                                            <img src="{{ route('admin.orders.image', $order) }}" 
                                                alt="Order Image Full" 
                                                class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div x-show="!searchQuery" class="text-center py-8 text-gray-500">
                                No orders found
                            </div>
                        @endforelse
                        
                        {{-- No Search Results Message for Mobile --}}
                        <div x-show="searchQuery && !hasVisibleRows" x-cloak class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-400 opacity-50" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-700">No results found for "<span x-text="searchQuery"></span>"</p>
                            <p class="text-xs text-gray-500 mt-1">Try different keywords or filters</p>
                        </div>
                    </div>

                {{-- Pagination --}}
                <div id="customer-orders-pagination-container" class="mt-5">
                    <x-custom-pagination :paginator="$orders" />
                </div>
            </div>
        </section>        {{-- ================= RIGHT: CUSTOMER DETAIL CARD (col-span-4 = 1/3 width, sticky) ================= --}}
            <aside class="lg:col-span-3 order-1 lg:order-2">
                <div class="lg:sticky lg:top-6">
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="space-y-3">
                            {{-- Customer Name --}}
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Customer Name</p>
                                <p class="text-lg font-bold text-gray-900">{{ $customer->customer_name }}</p>
                            </div>

                            {{-- Phone --}}
                            <div>
                                <p class="text-xs text-gray-500 font-medium mb-1">Phone</p>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm text-gray-700">{{ $customer->phone ?? '-' }}</p>
                                    @if ($customer->phone)
                                        <button type="button" 
                                                data-text="{{ $customer->phone }}"
                                                onclick="copyText(this)"
                                                class="relative text-gray-400 hover:text-primary transition-colors cursor-pointer"
                                                title="Copy phone">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Location --}}
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Location</p>
                                <p class="text-sm text-gray-700">
                                    @if ($customer->province_id || $customer->city_id || $customer->district_id || $customer->village_id)
                                        <span x-text="locationNames.province"></span> - 
                                        <span x-text="locationNames.city"></span> - 
                                        <span x-text="locationNames.district"></span> - 
                                        <span x-text="locationNames.village"></span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </p>
                            </div>

                            {{-- Address --}}
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Address</p>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm text-gray-700">{{ $customer->address ?? '-' }}</p>
                                    @if ($customer->address)
                                        <button type="button" 
                                                data-text="{{ $customer->address }}"
                                                onclick="copyText(this)"
                                                class="relative text-gray-400 hover:text-primary transition-colors cursor-pointer"
                                                title="Copy address">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

        </div>
    </div>
@endsection

@push('scripts')
    {{-- Copy to Clipboard Function --}}
    <script>
        function copyText(button) {
            const text = button.getAttribute('data-text');
            
            if (!text) {
                console.error('No text to copy');
                return;
            }
            
            // Fallback copy method (works in all browsers)
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    console.log('Copied:', text);
                    
                    // Create tooltip
                    const tooltip = document.createElement('div');
                    tooltip.textContent = 'Copied!';
                    tooltip.className = 'absolute bg-gray-900 text-white text-xs px-2 py-1 rounded shadow-lg -top-8 left-1/2 transform -translate-x-1/2 z-50';
                    tooltip.style.whiteSpace = 'nowrap';
                    
                    // Position relative to button
                    button.style.position = 'relative';
                    button.appendChild(tooltip);
                    
                    // Remove tooltip after 1.5 seconds
                    setTimeout(function() {
                        tooltip.remove();
                    }, 1500);
                } else {
                    alert('Failed to copy');
                }
            } catch (err) {
                console.error('Failed to copy:', err);
                alert('Failed to copy. Please try again.');
            }
            
            document.body.removeChild(textarea);
        }
    </script>

    {{-- Pagination AJAX Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('customer-orders-pagination-container', 'customer-orders-section');
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
