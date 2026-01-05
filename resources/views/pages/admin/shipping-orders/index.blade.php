@extends('layouts.app')

@section('title', 'Shipping Orders')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Shipping Orders']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        activeFilter: '{{ request('filter', 'all') }}',
        searchQuery: '{{ request('search') }}',
        startDate: '{{ $startDate ?? '' }}',
        endDate: '{{ $endDate ?? '' }}',
        dateRange: '{{ $dateRange ?? '' }}',
        showDateFilter: false,
        showDateCustomRange: false,
        matchesSearch(row) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const query = this.searchQuery.toLowerCase();
            const invoiceNo = (row.getAttribute('data-invoice') || '').toLowerCase();
            const customer = (row.getAttribute('data-customer') || '').toLowerCase();
            const product = (row.getAttribute('data-product') || '').toLowerCase();
            const designs = (row.getAttribute('data-designs') || '').toLowerCase();
            return invoiceNo.includes(query) || customer.includes(query) || product.includes(query) || designs.includes(query);
        },
        get hasVisibleRows() {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const tbody = document.querySelector('tbody');
            if (!tbody) return true;
            const rows = tbody.querySelectorAll('tr[data-invoice]');
            for (let row of rows) {
                if (this.matchesSearch(row)) return true;
            }
            return false;
        },
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
            if (this.dateRange === 'default') return 'Default';
            if (this.dateRange === 'last_month') return 'Last Month';
            if (this.dateRange === 'this_month') return 'This Month';
            if (this.dateRange === 'custom' && this.startDate && this.endDate) return 'Custom Date';
            return 'Date';
        },
        applyDatePreset(preset) {
            const today = new Date();
            if (preset === 'default') {
                const fortyFiveDaysAgo = new Date(today);
                fortyFiveDaysAgo.setDate(fortyFiveDaysAgo.getDate() - 45);
                this.startDate = fortyFiveDaysAgo.toISOString().split('T')[0];
                this.endDate = today.toISOString().split('T')[0];
                this.dateRange = 'default';
                this.applyFilter();
            } else if (preset === 'last-month') {
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                this.startDate = lastMonth.toISOString().split('T')[0];
                this.endDate = lastMonthEnd.toISOString().split('T')[0];
                this.dateRange = 'last_month';
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
            
            // Save focus state
            const searchInputFocused = document.activeElement === this.$refs.searchInput;
            const cursorPosition = searchInputFocused ? this.$refs.searchInput.selectionStart : null;
            
            // Build URL with query params
            const params = new URLSearchParams();
            params.set('filter', this.activeFilter);
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.dateRange) params.set('date_range', this.dateRange);
            if (this.startDate) params.set('start_date', this.startDate);
            if (this.endDate) params.set('end_date', this.endDate);
            
            // Include per_page parameter
            const perPageValue = this.getPerPageValue();
            if (perPageValue) params.set('per_page', perPageValue);
            
            const url = '{{ route('admin.shipping-orders') }}?' + params.toString();
            
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
                const newSection = doc.getElementById('shipping-orders-section');
                
                if (newSection) {
                    document.getElementById('shipping-orders-section').innerHTML = newSection.innerHTML;
                    setupPagination('shipping-pagination-container', 'shipping-orders-section');
                    
                    // Scroll to filter section
                    setTimeout(() => {
                        const filterSection = document.getElementById('filter-section');
                        if (filterSection) {
                            filterSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 100);
                }
                
                NProgress.done();
                
                // Restore focus and cursor position
                if (searchInputFocused && this.$refs.searchInput) {
                    this.$nextTick(() => {
                        this.$refs.searchInput.focus();
                        if (cursorPosition !== null) {
                            this.$refs.searchInput.setSelectionRange(cursorPosition, cursorPosition);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                NProgress.done();
            });
        },
        getPerPageValue() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('per_page') || '25';
        }
    }" class="space-y-6">

        {{-- Wrap dengan section untuk AJAX reload --}}
        <section id="shipping-orders-section">
            {{-- ================= SECTION 1: STATISTICS CARDS ================= --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Total Shipped --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Shipped</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_shipped']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Pickup --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pickup</p>
                            <p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['pickup']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Delivery --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Delivery</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['delivery']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
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
                    <div class="grid grid-cols-3 md:flex md:flex-wrap gap-2" id="filter-section">
                        {{-- All - Green (Primary) --}}
                        <button type="button" @click="activeFilter = 'all'; applyFilter();"
                            :class="activeFilter === 'all' ? 'bg-primary text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            All
                        </button>
                        {{-- Pickup - Purple --}}
                        <button type="button" @click="activeFilter = 'pickup'; applyFilter();"
                            :class="activeFilter === 'pickup' ? 'bg-purple-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-purple-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Pickup
                        </button>
                        {{-- Delivery - Green --}}
                        <button type="button" @click="activeFilter = 'delivery'; applyFilter();"
                            :class="activeFilter === 'delivery' ? 'bg-green-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-green-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Delivery
                        </button>
                    </div>

                    {{-- Right: Search & Date Filter --}}
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">

                        {{-- Search & Date Filter - Same row on mobile --}}
                        <div class="flex gap-2 items-center xl:flex-1 xl:min-w-0">
                            {{-- Search - Flexible width yang bisa menyesuaikan --}}
                            <div class="flex-1 xl:min-w-[180px]">
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text" x-model="searchQuery" x-ref="searchInput"
                                        @input="applyFilter()"
                                        placeholder="Search invoice, customer..."
                                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>

                            {{-- Show Per Page Dropdown --}}
                            <div x-data="{
                                open: false,
                                perPage: {{ request('per_page', 25) }},
                                options: [
                                    { value: 5, label: '5' },
                                    { value: 10, label: '10' },
                                    { value: 15, label: '15' },
                                    { value: 20, label: '20' },
                                    { value: 25, label: '25' },
                                    { value: 50, label: '50' },
                                    { value: 100, label: '100' }
                                ],
                                get selected() {
                                    return this.options.find(o => o.value === this.perPage) || this.options[4];
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
                                    
                                    const url = '{{ route('admin.shipping-orders') }}?' + params.toString();
                                    
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
                                        const newSection = doc.getElementById('shipping-orders-section');
                                        
                                        if (newSection) {
                                            document.getElementById('shipping-orders-section').innerHTML = newSection.innerHTML;
                                            setupPagination('shipping-pagination-container', 'shipping-orders-section');
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
                                    class="w-15 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                        focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="selected.label"></span>
                                    <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                {{-- Dropdown --}}
                                <div x-show="open" @click.away="open = false" @scroll.window="open = false" x-cloak 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" 
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" 
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-18 bg-white border border-gray-200 rounded-md shadow-lg">
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

                                {{-- Date Filter Modal --}}
                                <div x-show="showDateFilter"
                                    @click.away="showDateFilter = false; showDateCustomRange = false" x-cloak
                                    class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-20">

                                    {{-- Main Preset Options --}}
                                    <div x-show="!showDateCustomRange" class="p-2">
                                        <button @click="applyDatePreset('default')" type="button"
                                            :class="dateRange === 'default' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            Default
                                        </button>
                                        <button @click="applyDatePreset('this-month')" type="button"
                                            :class="dateRange === 'this_month' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            This Month
                                        </button>
                                        <button @click="applyDatePreset('last-month')" type="button"
                                            :class="dateRange === 'last_month' ? 'bg-primary/10 text-primary font-medium' :
                                                'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 text-sm rounded-md transition-colors">
                                            Last Month
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
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Start
                                                    Date</label>
                                                <input type="date" x-model="startDate"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">End
                                                    Date</label>
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
                                            <button type="button" @click="startDate=''; endDate=''; dateRange=''; searchQuery=''; activeFilter='all'; applyFilter();"
                                                class="block w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 text-center">
                                                Reset Filter
                                            </button>
                                        </div>
                                    </div>
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
                                <th class="py-3 px-4 text-left font-bold rounded-l-lg">No Invoice</th>
                                <th class="py-3 px-4 text-left font-bold">Customer</th>
                                <th class="py-3 px-4 text-left font-bold">Product</th>
                                <th class="py-3 px-4 text-left font-bold">QTY</th>
                                <th class="py-3 px-4 text-left font-bold">Finished Date</th>
                                <th class="py-3 px-4 text-left font-bold">Shipping Date</th>
                                <th class="py-3 px-4 text-left font-bold">Shipping Type</th>
                                <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" x-data="{
                            get hasResults() {
                                if (!searchQuery || searchQuery.trim() === '') return true;
                                const search = searchQuery.toLowerCase();
                                return {{ Js::from($allOrders->map(fn($o) => strtolower(($o->invoice->invoice_no ?? '') . ' ' . ($o->customer->customer_name ?? '') . ' ' . ($o->customer->phone ?? '') . ' ' . ($o->productCategory->product_name ?? '') . ' ' . $o->designVariants->pluck('design_name')->filter()->implode(' ')))) }}
                                    .some(text => text.includes(search));
                            }
                        }">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-gray-50" x-show="searchQuery.trim() === ''"
                                    data-invoice="{{ $order->invoice->invoice_no ?? '' }}"
                                    data-customer="{{ $order->customer->customer_name ?? '' }} {{ $order->customer->phone ?? '' }}"
                                    data-product="{{ $order->productCategory->product_name ?? '' }}"
                                    data-designs="{{ $order->designVariants->pluck('design_name')->filter()->implode(' ') }}">
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
                                        <div x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const button = this.$refs.productBtn;
                                                const rect = button.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                const spaceAbove = rect.top;
                                                const dropUp = spaceBelow < 250 && spaceAbove > spaceBelow;
                                        
                                                if (dropUp) {
                                                    this.dropdownStyle = {
                                                        position: 'fixed',
                                                        bottom: (window.innerHeight - rect.top + 8) + 'px',
                                                        left: rect.left + 'px',
                                                        minWidth: '320px',
                                                        maxWidth: '400px'
                                                    };
                                                } else {
                                                    this.dropdownStyle = {
                                                        position: 'fixed',
                                                        top: (rect.bottom + 8) + 'px',
                                                        left: rect.left + 'px',
                                                        minWidth: '320px',
                                                        maxWidth: '400px'
                                                    };
                                                }
                                            }
                                        }" x-init="$watch('open', value => {
                                            if (value) {
                                                const closeOnScroll = () => { open = false; };
                                                window.addEventListener('scroll', closeOnScroll, { once: true });
                                                window.addEventListener('resize', closeOnScroll, { once: true });
                                            }
                                        })" class="relative inline-block">
                                            {{-- Product Badge (Clickable) --}}
                                            <button type="button" x-ref="productBtn" @click="checkPosition(); open = !open"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs bg-gray-100 hover:bg-gray-200 transition-colors cursor-pointer">
                                                <span>{{ $order->productCategory->product_name ?? '-' }}</span>
                                                <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" 
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown with Design Variants --}}
                                            <div x-show="open" @click.away="open = false" @scroll.window="open = false" x-cloak
                                                :style="dropdownStyle"
                                                class="bg-white border border-gray-200 rounded-lg shadow-xl z-50 p-4">
                                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Design Variants:</h4>
                                                @if($order->designVariants->count() > 0)
                                                    <div class="grid grid-cols-2 gap-2">
                                                        @foreach($order->designVariants->take(10) as $design)
                                                            <div class="px-3 py-2 bg-gray-50 rounded-md text-xs text-gray-700 border border-gray-200 flex items-center gap-1" 
                                                                title="{{ $design->design_name }}{{ $design->color ? ' - ' . $design->color : '' }}">
                                                                <span class="truncate">{{ $design->design_name }}</span>
                                                                @if($design->color)
                                                                    <span class="flex-shrink-0">- {{ $design->color }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if($order->designVariants->count() > 10)
                                                        <p class="text-xs text-gray-500 mt-2 italic">+ {{ $order->designVariants->count() - 10 }} more...</p>
                                                    @endif
                                                @else
                                                    <p class="text-xs text-gray-400 italic">No design variants</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- QTY --}}
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->orderItems->sum('qty') }}</span>
                                    </td>

                                    {{-- Finished Date --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->finished_date ? \Carbon\Carbon::parse($order->finished_date)->format('d M Y H:i') : '-' }}</span>
                                    </td>

                                    {{-- Shipping Date --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->shipping_date ? \Carbon\Carbon::parse($order->shipping_date)->format('d M Y H:i') : '-' }}</span>
                                    </td>

                                    {{-- Shipping Type --}}
                                    <td class="py-3 px-4">
                                        @php
                                            $shippingTypeClasses = [
                                                'pickup' => 'bg-purple-100 text-purple-800',
                                                'delivery' => 'bg-green-100 text-green-800',
                                            ];
                                            $shippingTypeClass =
                                                $shippingTypeClasses[$order->shipping_type] ??
                                                'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $shippingTypeClass }}">
                                            {{ strtoupper($order->shipping_type) }}
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
                                                <div x-show="open" @click.away="open = false" @scroll.window="open = false" x-cloak x-ref="dropdown"
                                                    :style="dropdownStyle"
                                                    class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                    {{-- View Detail --}}
                                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                                        target="_blank"
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
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr x-show="searchQuery.trim() === ''">
                                    <td colspan="8" class="py-8 text-center text-gray-400">
                                        <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-sm">No shipping orders found</p>
                                    </td>
                                </tr>
                            @endforelse

                            @foreach ($allOrders as $order)
                                @php
                                    $designNames = $order->designVariants->pluck('design_name')->filter()->implode(' ');
                                    $searchText = strtolower(($order->invoice->invoice_no ?? '') . ' ' . ($order->customer->customer_name ?? '') . ' ' . ($order->customer->phone ?? '') . ' ' . ($order->productCategory->product_name ?? '') . ' ' . $designNames);
                                @endphp

                                <tr class="hover:bg-gray-50" x-show="searchQuery.trim() !== '' && '{{ $searchText }}'.includes(searchQuery.toLowerCase())">
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
                                        <div x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const button = this.$refs.productBtn;
                                                const rect = button.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                const spaceAbove = rect.top;
                                                const dropUp = spaceBelow < 250 && spaceAbove > spaceBelow;
                                        
                                                if (dropUp) {
                                                    this.dropdownStyle = {
                                                        position: 'fixed',
                                                        bottom: (window.innerHeight - rect.top + 8) + 'px',
                                                        left: rect.left + 'px',
                                                        minWidth: '320px',
                                                        maxWidth: '400px'
                                                    };
                                                } else {
                                                    this.dropdownStyle = {
                                                        position: 'fixed',
                                                        top: (rect.bottom + 8) + 'px',
                                                        left: rect.left + 'px',
                                                        minWidth: '320px',
                                                        maxWidth: '400px'
                                                    };
                                                }
                                            }
                                        }" x-init="$watch('open', value => {
                                            if (value) {
                                                const closeOnScroll = () => { open = false; };
                                                window.addEventListener('scroll', closeOnScroll, { once: true });
                                                window.addEventListener('resize', closeOnScroll, { once: true });
                                            }
                                        })" class="relative inline-block">
                                            {{-- Product Badge (Clickable) --}}
                                            <button type="button" x-ref="productBtn" @click="checkPosition(); open = !open"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs bg-gray-100 hover:bg-gray-200 transition-colors cursor-pointer">
                                                <span>{{ $order->productCategory->product_name ?? '-' }}</span>
                                                <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" 
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown with Design Variants --}}
                                            <div x-show="open" @click.away="open = false" @scroll.window="open = false" x-cloak
                                                :style="dropdownStyle"
                                                class="bg-white border border-gray-200 rounded-lg shadow-xl z-50 p-4">
                                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Design Variants:</h4>
                                                @if($order->designVariants->count() > 0)
                                                    <div class="grid grid-cols-2 gap-2">
                                                        @foreach($order->designVariants->take(10) as $design)
                                                            <div class="px-3 py-2 bg-gray-50 rounded-md text-xs text-gray-700 border border-gray-200 flex items-center gap-1" 
                                                                title="{{ $design->design_name }}{{ $design->color ? ' - ' . $design->color : '' }}">
                                                                <span class="truncate">{{ $design->design_name }}</span>
                                                                @if($design->color)
                                                                    <span class="flex-shrink-0">- {{ $design->color }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if($order->designVariants->count() > 10)
                                                        <p class="text-xs text-gray-500 mt-2 italic">+ {{ $order->designVariants->count() - 10 }} more...</p>
                                                    @endif
                                                @else
                                                    <p class="text-xs text-gray-400 italic">No design variants</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- QTY --}}
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->orderItems->sum('qty') }}</span>
                                    </td>

                                    {{-- Finished Date --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->finished_date ? \Carbon\Carbon::parse($order->finished_date)->format('d M Y H:i') : '-' }}</span>
                                    </td>

                                    {{-- Shipping Date --}}
                                    <td class="py-3 px-4">
                                        <span
                                            class="text-gray-700">{{ $order->shipping_date ? \Carbon\Carbon::parse($order->shipping_date)->format('d M Y H:i') : '-' }}</span>
                                    </td>

                                    {{-- Shipping Type --}}
                                    <td class="py-3 px-4">
                                        @php
                                            $shippingTypeClasses = [
                                                'pickup' => 'bg-purple-100 text-purple-800',
                                                'delivery' => 'bg-green-100 text-green-800',
                                            ];
                                            $shippingTypeClass =
                                                $shippingTypeClasses[$order->shipping_type] ??
                                                'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $shippingTypeClass }}">
                                            {{ strtoupper($order->shipping_type) }}
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
                                                <div x-show="open" @click.away="open = false" @scroll.window="open = false" x-cloak x-ref="dropdown"
                                                    :style="dropdownStyle"
                                                    class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                    {{-- View Detail --}}
                                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                                        target="_blank"
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
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            {{-- No results after client-side filter --}}
                            <tr x-show="searchQuery.trim() !== '' && !hasResults" x-cloak>
                                <td colspan="8" class="py-8 text-center text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm">No results found for "<span x-text="searchQuery"></span>"</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination - Hidden during search --}}
                <div id="shipping-pagination-container" class="mt-5" x-show="searchQuery.trim() === ''">
                    <x-custom-pagination :paginator="$orders" />
                </div>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    {{-- Pagination AJAX Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('shipping-pagination-container', 'shipping-orders-section');
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
