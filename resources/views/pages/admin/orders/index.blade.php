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
        activeFilter: '{{ request('filter', 'wip') }}',
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
                const isVisible = this.matchesSearch(row) && this.checkFilterMatch(row);
                if (isVisible) return true;
            }
            return false;
        },
        checkFilterMatch(row) {
            const activeFilter = this.activeFilter;
            if (activeFilter === 'finished') {
                return row.hasAttribute('data-finished-row');
            } else {
                return row.hasAttribute('data-default-row');
            }
        },
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
            if (this.dateRange === 'default') return 'Default';
            if (this.dateRange === 'last_month') return 'Last Month';
            if (this.dateRange === 'this_month') return 'This Month';
            if (this.dateRange === 'custom' && this.startDate && this.endDate) return 'Custom Date';
            return 'Date';
        },
        applyDatePreset(preset) {
            const today = new Date();
            if (preset === 'default') {
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 45);
                this.startDate = thirtyDaysAgo.toISOString().split('T')[0];
                this.endDate = today.toISOString().split('T')[0];
                this.dateRange = 'default';
                this.applyFilter();
            } else if (preset === 'this-month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                this.startDate = firstDay.toISOString().split('T')[0];
                this.endDate = lastDay.toISOString().split('T')[0];
                this.dateRange = 'this_month';
                this.applyFilter();
            } else if (preset === 'last-month') {
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                this.startDate = lastMonth.toISOString().split('T')[0];
                this.endDate = lastMonthEnd.toISOString().split('T')[0];
                this.dateRange = 'last_month';
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
            
            // Get per_page from nested component
            const perPageValue = this.getPerPageValue();
            if (perPageValue) params.set('per_page', perPageValue);
            
            const url = '{{ route('admin.orders.index') }}?' + params.toString();
            
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
                const newSection = doc.getElementById('orders-section');
                
                if (newSection) {
                    document.getElementById('orders-section').innerHTML = newSection.innerHTML;
                    setupPagination('orders-pagination-container', 'orders-section');
                    
                    // Scroll to filter section (section 2)
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
            // Get per_page value from URL or default
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('per_page') || '25';
        }
    }" class="space-y-6">

        {{-- Wrap dengan section untuk AJAX reload --}}
        <section id="orders-section">
            {{-- ================= SECTION 1: STATISTICS CARDS ================= --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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

                {{-- Cancelled --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Cancelled</p>
                            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats['cancelled']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <div class="grid grid-cols-3 md:flex md:flex-wrap gap-2" id="filter-section">
                        {{-- All - Green (Primary) --}}
                        <button type="button" @click="activeFilter = 'default'; applyFilter();"
                            :class="activeFilter === 'default' ? 'bg-primary text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            All
                        </button>
                        {{-- Pending - Yellow --}}
                        <button type="button" @click="activeFilter = 'pending'; applyFilter();"
                            :class="activeFilter === 'pending' ? 'bg-yellow-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-yellow-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Pending
                        </button>
                        {{-- WIP - Blue --}}
                        <button type="button" @click="activeFilter = 'wip'; applyFilter();"
                            :class="activeFilter === 'wip' ? 'bg-blue-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-blue-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            WIP
                        </button>
                        {{-- Finished - Green --}}
                        <button type="button" @click="activeFilter = 'finished'; applyFilter();"
                            :class="activeFilter === 'finished' ? 'bg-green-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-green-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Finished
                        </button>
                        {{-- Cancelled - Red --}}
                        <button type="button" @click="activeFilter = 'cancelled'; applyFilter();"
                            :class="activeFilter === 'cancelled' ? 'bg-red-500 text-white' :
                                'bg-gray-100 text-gray-700 hover:bg-red-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Cancelled
                        </button>
                    </div>

                    {{-- Right: Search, Date Filter, Create Order --}}
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">
                        
                        {{-- Search, Show Per Page & Date Filter - Same row on mobile --}}
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
                                    
                                    const url = '{{ route('admin.orders.index') }}?' + params.toString();
                                    
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
                                        const newSection = doc.getElementById('orders-section');
                                        
                                        if (newSection) {
                                            document.getElementById('orders-section').innerHTML = newSection.innerHTML;
                                            setupPagination('orders-pagination-container', 'orders-section');
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
                                <div x-show="open" @click.away="open = false" x-cloak 
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
                                    <form x-show="showDateCustomRange" class="p-4"
                                        @submit.prevent="dateRange = 'custom'; applyFilter();">
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
                                <th class="py-3 px-4 text-left font-bold rounded-l-lg">No Invoice</th>
                                <th class="py-3 px-4 text-left font-bold">Customer</th>
                                <th class="py-3 px-4 text-left font-bold">Product</th>
                                <th class="py-3 px-4 text-left font-bold">QTY</th>
                                <th class="py-3 px-4 text-left font-bold">Total Bill</th>
                                <th class="py-3 px-4 text-left font-bold">Remaining</th>
                                <th class="py-3 px-4 text-left font-bold">Status</th>
                                <th class="py-3 px-4 text-left font-bold">Finished Date</th>
                                <th class="py-3 px-4 text-left font-bold">Shipping Status</th>
                                <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                            </tr>
                            {{-- Other Filters: Default Columns --}}
                            <tr x-show="activeFilter !== 'finished'">
                                <th class="py-3 px-4 text-left font-bold rounded-l-lg">No Invoice</th>
                                <th class="py-3 px-4 text-left font-bold">Customer</th>
                                <th class="py-3 px-4 text-left font-bold">Product</th>
                                <th class="py-3 px-4 text-left font-bold">QTY</th>
                                <th class="py-3 px-4 text-left font-bold">Total Bill</th>
                                <th class="py-3 px-4 text-left font-bold">Remaining</th>
                                <th class="py-3 px-4 text-left font-bold">Order Date</th>
                                <th class="py-3 px-4 text-left font-bold">Deadline</th>
                                <th class="py-3 px-4 text-left font-bold">Status</th>
                                <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="" x-data="{
                            get hasResults() {
                                if (!searchQuery || searchQuery.trim() === '') return true;
                                const search = searchQuery.toLowerCase();
                                return {{ Js::from($allOrders->map(fn($o) => strtolower(($o->invoice->invoice_no ?? '') . ' ' . ($o->customer->customer_name ?? '') . ' ' . ($o->productCategory->product_name ?? '') . ' ' . $o->designVariants->pluck('design_name')->filter()->implode(' ')))) }}
                                    .some(text => text.includes(search));
                            }
                        }">
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
                                <tr x-show="activeFilter === 'finished' && searchQuery.trim() === ''" 
                                    class="hover:bg-gray-50 border-b border-gray-200"
                                    data-finished-row
                                    data-invoice="{{ $order->invoice->invoice_no ?? '' }}"
                                    data-customer="{{ $order->customer->customer_name ?? '' }}"
                                    data-product="{{ $order->productCategory->product_name ?? '' }}"
                                    data-designs="{{ $order->designVariants->pluck('design_name')->filter()->implode(' ') }}"
                                    x-data="{ productDropdownOpen: false }">
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
                                            <div x-show="open" @click.away="open = false" x-cloak
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
                                                    <a href="{{ route('admin.orders.show', $order->id) }}" target="_blank"
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
                                <tr x-show="activeFilter !== 'finished' && searchQuery.trim() === ''" 
                                    class="hover:bg-gray-50 border-b border-gray-200"
                                    data-default-row
                                    data-invoice="{{ $order->invoice->invoice_no ?? '' }}"
                                    data-customer="{{ $order->customer->customer_name ?? '' }}"
                                    data-product="{{ $order->productCategory->product_name ?? '' }}"
                                    data-designs="{{ $order->designVariants->pluck('design_name')->filter()->implode(' ') }}">
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
                                            <div x-show="open" @click.away="open = false" x-cloak
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
                                                    <a href="{{ route('admin.orders.show', $order->id) }}" target="_blank"
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
                                <tr x-show="activeFilter === 'finished' && searchQuery.trim() === ''">
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
                                <tr x-show="activeFilter !== 'finished' && searchQuery.trim() === ''">
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

                            @foreach ($allOrders as $order)
                                @php
                                    // Calculate pending transactions for this order
                                    $pendingPayments = $order->invoice
                                        ? $order->invoice->payments()->where('status', 'pending')->get()
                                        : collect();
                                    $pendingCount = $pendingPayments->count();
                                    $pendingAmount = $pendingPayments->sum('amount');
                                    
                                    $designNames = $order->designVariants->pluck('design_name')->filter()->implode(' ');
                                    $searchText = strtolower(($order->invoice->invoice_no ?? '') . ' ' . ($order->customer->customer_name ?? '') . ' ' . ($order->productCategory->product_name ?? '') . ' ' . $designNames);
                                @endphp

                                {{-- Finished Filter Row for All Orders --}}
                                <tr x-show="activeFilter === 'finished' && searchQuery.trim() !== '' && '{{ $searchText }}'.includes(searchQuery.toLowerCase())"
                                    class="hover:bg-gray-50 border-b border-gray-200">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-medium text-gray-900">{{ $order->invoice->invoice_no ?? '-' }}</span>
                                            @if ($order->shipping_type)
                                                @if (strtolower($order->shipping_type) === 'pickup')
                                                    <span class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">PICKUP</span>
                                                @elseif (strtolower($order->shipping_type) === 'delivery')
                                                    <span class="px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 bg-blue-100 rounded">DELIVERY</span>
                                                @endif
                                            @endif
                                            @if (isset($order->priority) && strtolower($order->priority) === 'high')
                                                <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <p class="text-gray-700">{{ $order->customer->customer_name ?? '-' }}</p>
                                            <p class="text-xs text-gray-500">{{ $order->customer->phone ?? '-' }}</p>
                                        </div>
                                    </td>
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
                                            <div x-show="open" @click.away="open = false" x-cloak
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
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->orderItems->sum('qty') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">Rp {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-red-600">Rp {{ number_format($order->invoice->amount_due ?? 0, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $statusClasses = ['pending' => 'bg-yellow-100 text-yellow-800', 'wip' => 'bg-blue-100 text-blue-800', 'finished' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-red-100 text-red-800'];
                                            $statusClass = $statusClasses[$order->production_status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ strtoupper($order->production_status) }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->finished_date ? \Carbon\Carbon::parse($order->finished_date)->format('d M Y H:i') : '-' }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $shippingStatusClasses = ['pending' => 'bg-orange-100 text-orange-800', 'shipped' => 'bg-blue-100 text-blue-800'];
                                            $shippingStatusClass = $shippingStatusClasses[$order->shipping_status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $shippingStatusClass }}">{{ strtoupper($order->shipping_status) }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="relative inline-block text-left" x-data="{ open: false, dropdownStyle: {}, checkPosition() { const button = this.$refs.button; const rect = button.getBoundingClientRect(); const spaceBelow = window.innerHeight - rect.bottom; const spaceAbove = rect.top; const dropUp = spaceBelow < 200 && spaceAbove > spaceBelow; if (dropUp) { this.dropdownStyle = { position: 'fixed', top: (rect.top - 200) + 'px', left: (rect.right - 160) + 'px', width: '160px' }; } else { this.dropdownStyle = { position: 'fixed', top: (rect.bottom + 8) + 'px', left: (rect.right - 160) + 'px', width: '160px' }; } } }">
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" /></svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle" class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                <a href="{{ route('admin.orders.show', $order->id) }}" target="_blank" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    View Detail
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Default Filter Row for All Orders --}}
                                <tr x-show="activeFilter !== 'finished' && searchQuery.trim() !== '' && '{{ $searchText }}'.includes(searchQuery.toLowerCase())"
                                    class="hover:bg-gray-50 border-b border-gray-200">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-medium text-gray-900">{{ $order->invoice->invoice_no ?? '-' }}</span>
                                            @if ($order->shipping_type)
                                                @if (strtolower($order->shipping_type) === 'pickup')
                                                    <span class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">PICKUP</span>
                                                @elseif (strtolower($order->shipping_type) === 'delivery')
                                                    <span class="px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 bg-blue-100 rounded">DELIVERY</span>
                                                @endif
                                            @endif
                                            @if (isset($order->priority) && strtolower($order->priority) === 'high')
                                                <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <p class="text-gray-700">{{ $order->customer->customer_name ?? '-' }}</p>
                                            <p class="text-xs text-gray-500">{{ $order->customer->phone ?? '-' }}</p>
                                        </div>
                                    </td>
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
                                            <div x-show="open" @click.away="open = false" x-cloak
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
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->orderItems->sum('qty') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">Rp {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-red-600">Rp {{ number_format($order->invoice->amount_due ?? 0, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '-' }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-gray-700">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d M Y') : '-' }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $statusClasses = ['pending' => 'bg-yellow-100 text-yellow-800', 'wip' => 'bg-blue-100 text-blue-800', 'finished' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-red-100 text-red-800'];
                                            $statusClass = $statusClasses[$order->production_status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ strtoupper($order->production_status) }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="relative inline-block text-left" x-data="{ open: false, dropdownStyle: {}, checkPosition() { const button = this.$refs.button; const rect = button.getBoundingClientRect(); const spaceBelow = window.innerHeight - rect.bottom; const spaceAbove = rect.top; const dropUp = spaceBelow < 200 && spaceAbove > spaceBelow; if (dropUp) { this.dropdownStyle = { position: 'fixed', top: (rect.top - 200) + 'px', left: (rect.right - 160) + 'px', width: '160px' }; } else { this.dropdownStyle = { position: 'fixed', top: (rect.bottom + 8) + 'px', left: (rect.right - 160) + 'px', width: '160px' }; } } }">
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" /></svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle" class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                <a href="{{ route('admin.orders.show', $order->id) }}" target="_blank" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    View Detail
                                                </a>
                                                @if ($order->production_status !== 'finished' && $order->production_status !== 'cancelled')
                                                    <a href="{{ route('admin.orders.edit', $order->id) }}" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                        Edit
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            
                            {{-- No Search Results Message --}}
                            <tr x-show="searchQuery.trim() !== '' && !hasResults" x-cloak>
                                <td colspan="10" class="py-8 text-center text-gray-400">
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

                {{-- Pagination Component (already includes "Showing X to Y" and page numbers) --}}
                <div id="orders-pagination-container" class="mt-4" x-show="searchQuery.trim() === ''">
                    <x-custom-pagination :paginator="$orders" />
                </div>
        </section>

        {{-- ================= CANCEL CONFIRMATION MODAL ================= --}}
        <div x-show="showCancelConfirm !== null" x-cloak
            class="fixed inset-0 z-50">
            
            {{-- Background Overlay --}}
            <div x-show="showCancelConfirm !== null" @click="showCancelConfirm = null"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showCancelConfirm = null"
                    class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 z-10">
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
        </div>

        {{-- ================= MOVE TO SHIPPING CONFIRMATION MODAL ================= --}}
        <div x-show="showMoveToShippingConfirm !== null" x-cloak
            class="fixed inset-0 z-50">
            
            {{-- Background Overlay --}}
            <div x-show="showMoveToShippingConfirm !== null" @click="showMoveToShippingConfirm = null"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showMoveToShippingConfirm = null"
                    class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 z-10">
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
                                Yes, Move
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= ADD PAYMENT MODAL ================= --}}
        <div x-show="showAddPaymentModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs px-4 py-6">
            <div @click.away="showAddPaymentModal = false; resetPaymentForm()"
                class="bg-white rounded-xl shadow-lg w-full max-w-3xl"
                style="height: min(calc(100vh - 6rem), 700px); min-height: 0; display: flex; flex-direction: column;">
                {{-- Fixed Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">Add Payment</h3>
                    <button @click="showAddPaymentModal = false; resetPaymentForm()"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        ✕
                    </button>
                </div>

                {{-- Scrollable Content --}}
                <div class="overflow-y-auto flex-1 px-6 py-4">
                    {{-- Form --}}
                    <form id="addPaymentForm"
                        @submit.prevent="
                            // Frontend validation - prevent submit if fields are empty
                            paymentErrors = {};
                            let hasValidationError = false;
                            const formData = new FormData($event.target);
                            
                            // Validate payment_method
                            if (!formData.get('payment_method')) {
                                paymentErrors.payment_method = ['Payment method is required'];
                                hasValidationError = true;
                            }
                            
                            // Validate payment_type
                            if (!formData.get('payment_type')) {
                                paymentErrors.payment_type = ['Payment type is required'];
                                hasValidationError = true;
                            }
                            
                            // Validate amount
                            const amount = formData.get('amount');
                            if (!amount || amount === '0' || amount === '') {
                                paymentErrors.amount = ['Amount is required'];
                                hasValidationError = true;
                            }
                            
                            // Validate image
                            const imageFile = formData.get('image');
                            if (!imageFile || imageFile.size === 0) {
                                paymentErrors.image = ['Payment proof image is required'];
                                hasValidationError = true;
                            }
                            
                            // If validation fails, stop here and show errors
                            if (hasValidationError) {
                                return; // Don't submit to server
                            }
                            
                            // Validation passed, proceed with submission
                            isSubmittingPayment = true;
                            fetch('{{ route('admin.payments.store') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData,
                                redirect: 'manual'
                            })
                            .then(async res => {
                                if (!res.ok && res.type === 'opaqueredirect') {
                                    throw new Error('Redirect detected');
                                }
                                const data = await res.json();
                                return { status: res.status, ok: res.ok, data };
                            })
                            .then(({ status, ok, data }) => {
                                if (ok && data.success) {
                                    sessionStorage.setItem('toast_message', 'Payment added successfully');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else if (status === 422) {
                                    // Validation errors - expected behavior
                                    isSubmittingPayment = false;
                                    paymentErrors = data.errors || {};
                                } else {
                                    isSubmittingPayment = false;
                                    paymentErrors = data.errors || {};
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingPayment = false;
                                // Suppress console error for expected validation errors
                                if (err.message !== 'Redirect detected') {
                                    console.error('Payment error:', err);
                                }
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: 'Failed to add payment. Please try again.', type: 'error' }
                                }));
                            });
                        ">
                        <input type="hidden" name="invoice_id" :value="selectedOrderForPayment?.invoice_id">

                        <div class="space-y-4">
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

                            {{-- Payment Method & Type (1 Row) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Payment Method --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Method <span class="text-red-600">*</span>
                                    </label>
                                <div x-data="{
                                    open: false,
                                    options: [
                                        { value: 'transfer', name: 'Transfer' },
                                        { value: 'cash', name: 'Cash' }
                                    ],
                                    selected: null,
                                    selectedValue: '',
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.value;
                                        this.open = false;
                                        if (paymentErrors.payment_method) {
                                            delete paymentErrors.payment_method;
                                        }
                                    }
                                }" class="relative w-full">
                                    {{-- Trigger --}}
                                    <button type="button" @click="open = !open"
                                        :class="paymentErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white
                                               focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : 'Select Method'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-500'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {{-- Hidden input --}}
                                    <input type="hidden" name="payment_method" x-model="selectedValue">

                                    {{-- Dropdown --}}
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.value">
                                                <li @click="select(option)"
                                                    class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                    :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.value === option.value }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    </div>
                                    <p x-show="paymentErrors.payment_method" x-cloak
                                        x-text="paymentErrors.payment_method?.[0]" class="mt-1 text-sm text-red-600"></p>
                                </div>

                                {{-- Payment Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Type <span class="text-red-600">*</span>
                                    </label>
                                <div x-data="{
                                    open: false,
                                    options: [
                                        { value: 'dp', name: 'DP (Down Payment)' },
                                        { value: 'repayment', name: 'Repayment' },
                                        { value: 'full_payment', name: 'Full Payment' }
                                    ],
                                    selected: null,
                                    selectedValue: '',
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.value;
                                        this.open = false;
                                        
                                        // Auto-fill amount untuk repayment atau full_payment
                                        if (option.value === 'repayment' || option.value === 'full_payment') {
                                            const remainingDue = selectedOrderForPayment?.remaining_due || 0;
                                            paymentAmount = Math.floor(remainingDue).toLocaleString('id-ID');
                                        }
                                        
                                        if (paymentErrors.payment_type) {
                                            delete paymentErrors.payment_type;
                                        }
                                    }
                                }" class="relative w-full">
                                    {{-- Trigger --}}
                                    <button type="button" @click="open = !open"
                                        :class="paymentErrors.payment_type ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white
                                               focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : 'Select Type'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-500'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {{-- Hidden input --}}
                                    <input type="hidden" name="payment_type" x-model="selectedValue">

                                    {{-- Dropdown --}}
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.value">
                                                <li @click="select(option)"
                                                    class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                    :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.value === option.value }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    </div>
                                    <p x-show="paymentErrors.payment_type" x-cloak x-text="paymentErrors.payment_type?.[0]"
                                        class="mt-1 text-sm text-red-600"></p>
                                </div>
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
                            <div x-data="{
                                imagePreview: null,
                                fileName: '',
                                isDragging: false,
                                handleFileChange(event) {
                                    const file = event.target.files[0];
                                    this.processFile(file);
                                },
                                processFile(file) {
                                    if (file && file.type.startsWith('image/')) {
                                        this.fileName = file.name;
                                        const reader = new FileReader();
                                        reader.onload = (e) => {
                                            this.imagePreview = e.target.result;
                                        };
                                        reader.readAsDataURL(file);
                                    } else {
                                        this.imagePreview = null;
                                        this.fileName = '';
                                    }
                                },
                                handleDrop(event) {
                                    this.isDragging = false;
                                    const file = event.dataTransfer.files[0];
                                    if (file) {
                                        // Set file to input
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        this.$refs.fileInput.files = dataTransfer.files;
                                        this.processFile(file);
                                    }
                                },
                                handleDragOver(event) {
                                    event.preventDefault();
                                    this.isDragging = true;
                                },
                                handleDragLeave() {
                                    this.isDragging = false;
                                }
                            }">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Proof <span class="text-red-600">*</span>
                                </label>

                                {{-- Drag & Drop Area --}}
                                <div @drop.prevent="handleDrop($event)"
                                    @dragover.prevent="handleDragOver($event)"
                                    @dragleave="handleDragLeave()"
                                    @click="$refs.fileInput.click()"
                                    :class="{
                                        'border-primary bg-primary/5': isDragging && !imagePreview,
                                        'border-red-500 bg-red-50': paymentErrors.image && !imagePreview,
                                        'border-gray-300 bg-gray-50': !isDragging && !paymentErrors.image && !imagePreview,
                                        'border-gray-200 bg-white p-2': imagePreview,
                                        'p-6': !imagePreview
                                    }"
                                    class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary"
                                    :style="imagePreview ? 'min-height: 200px;' : ''">
                                    <input type="file" x-ref="fileInput" name="image" accept="image/jpeg,image/png,image/jpg"
                                        @change="handleFileChange($event)"
                                        class="hidden">
                                    
                                    {{-- Image Preview (Full Container) --}}
                                    <div x-show="imagePreview" x-cloak class="relative w-full h-full flex items-center justify-center">
                                        <img :src="imagePreview" alt="Preview"
                                            class="w-full h-auto max-h-80 object-contain rounded-lg">
                                        <button type="button" @click.stop="imagePreview = null; fileName = ''; $refs.fileInput.value = ''"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    {{-- Upload Icon & Text (Only when no preview) --}}
                                    <div x-show="!imagePreview">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <div class="space-y-1">
                                            <p class="text-sm font-medium text-gray-700">
                                                <span class="text-primary font-semibold">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-500">PNG, JPG, JPEG (Max 10MB)</p>
                                        </div>
                                    </div>

                                    {{-- File Name Display --}}
                                    <div x-show="fileName && !imagePreview" x-cloak class="mt-3 pt-3 border-t border-gray-200">
                                        <div class="flex items-center justify-center gap-2 text-sm text-gray-700">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="fileName" class="font-medium truncate max-w-xs"></span>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">Drag and drop your image here or click to browse</p>
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
                    </form>
                </div>

                {{-- Fixed Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                    <button type="button" @click="showAddPaymentModal = false; resetPaymentForm()"
                        :disabled="isSubmittingPayment"
                        class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                    <button type="submit" form="addPaymentForm" :disabled="isSubmittingPayment"
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

                            // Re-setup pagination after content update
                            setupPagination(containerId, sectionId);
                            
                            // Scroll to pagination area (bottom)
                            setTimeout(() => {
                                const paginationContainer = document.getElementById(containerId);
                                if (paginationContainer) {
                                    paginationContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }, 100);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        }
    </script>
@endpush
