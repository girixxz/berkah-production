@extends('layouts.app')

@section('title', 'Manage Task')

@section('content')
    @php
        $role = auth()->user()?->role;
        // Task Manage is PM's feature, Owner/Admin can access as super users
        $routeName = 'pm.manage-task'; // Everyone uses PM route
    @endphp

    <x-nav-locate :items="['PM', 'Task Manage']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        activeFilter: '{{ request('filter', 'wip') }}',
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
        originalStages: {},
        pendingChanges: {},
        isUpdatingStatus: false,
        matchesSearch(row) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const query = this.searchQuery.toLowerCase();
            const invoice = (row.getAttribute('data-invoice') || '').toLowerCase();
            const customer = (row.getAttribute('data-customer') || '').toLowerCase();
            return invoice.includes(query) || customer.includes(query);
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
        get hasChanges() {
            return Object.keys(this.pendingChanges).length > 0;
        },
        openEditStageModal(orderId, invoiceNo, orderStages, orderNotes) {
            this.editStageData = {
                orderId: orderId,
                invoiceNo: invoiceNo,
                orderStages: orderStages,
                orderNotes: orderNotes || ''
            };
            // Store original stages for comparison
            this.originalStages = {};
            orderStages.forEach(stage => {
                this.originalStages[stage.id] = stage.status;
            });
            this.pendingChanges = {};
            this.showEditStageModal = true;
        },
        changeStageStatus(stageId, newStatus) {
            // Update local state only
            const stageIndex = this.editStageData.orderStages.findIndex(s => s.id === stageId);
            if (stageIndex !== -1) {
                this.editStageData.orderStages[stageIndex].status = newStatus;
                
                // Track if this is different from original
                if (this.originalStages[stageId] !== newStatus) {
                    this.pendingChanges[stageId] = newStatus;
                } else {
                    delete this.pendingChanges[stageId];
                }
            }
        },
        async saveStageChanges() {
            if (!this.hasChanges) return;
            
            this.isUpdatingStatus = true;
            let successCount = 0;
            let errorCount = 0;
            
            // Process all pending changes
            for (const [stageId, newStatus] of Object.entries(this.pendingChanges)) {
                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('order_stage_id', stageId);
                    formData.append('status', newStatus);
                    
                    const response = await fetch('{{ route($routeName . '.update-stage-status') }}', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        successCount++;
                        // Update original state
                        this.originalStages[stageId] = newStatus;
                    } else {
                        errorCount++;
                    }
                } catch (err) {
                    errorCount++;
                    console.error('Error updating stage:', err);
                }
            }
            
            // Clear pending changes after processing
            this.pendingChanges = {};
            this.isUpdatingStatus = false;
            
            // Show result notification
            if (successCount > 0) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { 
                        message: `Successfully updated ${successCount} stage${successCount > 1 ? 's' : ''}`,
                        type: 'success' 
                    }
                }));
                
                // Refresh table data
                this.refreshTableData();
            }
            
            if (errorCount > 0) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { 
                        message: `Failed to update ${errorCount} stage${errorCount > 1 ? 's' : ''}`,
                        type: 'error' 
                    }
                }));
            }
        },
        refreshTableData() {
            // Refresh table content without closing modal
            const currentUrl = window.location.href;
            fetch(currentUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTableBody = doc.querySelector('tbody');
                    const currentTableBody = document.querySelector('tbody');
    
                    if (newTableBody && currentTableBody) {
                        currentTableBody.innerHTML = newTableBody.innerHTML;
                    }
                })
                .catch(err => {
                    console.error('Failed to refresh table:', err);
                });
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
            
            // Save focus state and cursor position
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
            
            const url = '{{ route($routeName) }}?' + params.toString();
            
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
                const newSection = doc.getElementById('task-manager-section');
                
                if (newSection) {
                    document.getElementById('task-manager-section').innerHTML = newSection.innerHTML;
                    setupPagination('task-manager-pagination-container', 'task-manager-section');
                    
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
            return urlParams.get('per_page') || '15';
        }
    }" class="space-y-6">

        {{-- ================= VIEW ONLY NOTICE FOR ADMIN ================= --}}
        @if ($isViewOnly)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-yellow-800">View Only Mode</h4>
                    <p class="text-sm text-yellow-700 mt-1">You are viewing this page in read-only mode. All editing features
                        are disabled.</p>
                </div>
            </div>
        @endif

        {{-- ================= SECTION 1: STATISTICS CARDS ================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Order WIP --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Order WIP</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['order_wip']) }}</p>
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

            {{-- Order Finished --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Order Finished</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['order_finished']) }}</p>
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

        {{-- Wrap dengan section untuk AJAX reload --}}
        <section id="task-manager-section">
        {{-- ================= SECTION 2: FILTER, SEARCH & TABLE ================= --}}
        <div id="filter-section" class="bg-white border border-gray-200 rounded-lg p-5 mt-6">
            {{-- Filter & Search Section --}}
            <div class="flex flex-col xl:flex-row xl:items-center gap-4">
                {{-- Left: Filter Buttons --}}
                <div class="grid grid-cols-3 md:flex md:flex-wrap gap-2">
                    <button @click="activeFilter = 'default'; applyFilter();"
                        :class="activeFilter === 'default' ? 'bg-primary text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        All
                    </button>
                    <button @click="activeFilter = 'wip'; applyFilter();"
                        :class="activeFilter === 'wip' ? 'bg-blue-500 text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-blue-50'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        WIP
                    </button>
                    <button @click="activeFilter = 'finished'; applyFilter();"
                        :class="activeFilter === 'finished' ? 'bg-green-500 text-white' :
                            'bg-gray-100 text-gray-700 hover:bg-green-50'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                        Finished
                    </button>
                </div>

                {{-- Right: Search & Date Filter --}}
                <div class="flex gap-2 items-center xl:flex-1 xl:ml-auto xl:min-w-0">
                    {{-- Search --}}
                    <div class="flex-1 xl:min-w-[180px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="searchQuery" x-ref="searchInput"
                                @input="applyFilter()" placeholder="Search invoice, customer..."
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
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
                            
                            const url = '{{ route($routeName) }}?' + params.toString();
                            
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
                                const newSection = doc.getElementById('task-manager-section');
                                
                                if (newSection) {
                                    document.getElementById('task-manager-section').innerHTML = newSection.innerHTML;
                                    setupPagination('task-manager-pagination-container', 'task-manager-section');
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
                        <div x-show="showDateFilter" @click.away="showDateFilter = false; showDateCustomRange = false"
                            x-cloak
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
                                    <a href="{{ route($routeName, ['filter' => request('filter', 'default')]) }}"
                                        class="block w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 text-center">
                                        Reset Filter
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Table Section --}}
            <div class="overflow-x-auto mt-4">
                <table class="min-w-full text-xs">
                    <thead class="bg-primary-light text-gray-600">
                        <tr>
                            <th class="py-3 px-3 text-left font-bold whitespace-nowrap rounded-l-lg">Customer</th>
                            <th class="py-3 px-3 text-left font-bold whitespace-nowrap">Order</th>
                            <th class="py-3 px-3 text-center font-bold whitespace-nowrap">Date In</th>
                            <th class="py-3 px-3 text-center font-bold whitespace-nowrap">Date Out</th>
                            @foreach ($productionStages as $stage)
                                <th class="py-3 px-3 text-center font-bold whitespace-nowrap">{{ $stage->stage_name }}
                                </th>
                            @endforeach
                            <th class="py-3 px-3 text-center font-bold whitespace-nowrap rounded-r-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" x-data="{
                        get hasResults() {
                            if (searchQuery.trim() === '') return true;
                            const rows = Array.from($el.querySelectorAll('tr[data-order]'));
                            return rows.some(row => {
                                const invoice = (row.getAttribute('data-invoice') || '').toLowerCase();
                                const customer = (row.getAttribute('data-customer') || '').toLowerCase();
                                return invoice.includes(searchQuery.toLowerCase()) || customer.includes(searchQuery.toLowerCase());
                            });
                        }
                    }">
                        @forelse ($orders as $order)
                            @php
                                // Get all order stages for this order
                                $orderStagesMap = $order->orderStages->keyBy('stage_id');
                            @endphp
                            <tr class="hover:bg-gray-50"
                                x-show="searchQuery.trim() === ''"
                                data-invoice="{{ $order->invoice->invoice_no ?? '' }}"
                                data-customer="{{ $order->customer->customer_name ?? '' }}">
                                {{-- Customer --}}
                                <td class="py-3 px-3">
                                    <div>
                                        <p class="text-gray-700 whitespace-nowrap">
                                            {{ $order->customer->customer_name ?? '-' }}</p>
                                        <p class="text-[10px] text-gray-500">
                                            @if($order->customer && $order->customer->phone)
                                                {{ substr($order->customer->phone, 0, 4) }}****{{ substr($order->customer->phone, -3) }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                </td>

                                {{-- Order (Invoice + Product + Priority) --}}
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5 flex-wrap max-w-[200px]">
                                        <span class="font-medium text-gray-900 text-[11px]">
                                            {{ $order->invoice->invoice_no ?? '-' }}
                                        </span>
                                        @if ($order->productCategory)
                                            <span
                                                class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">
                                                {{ strtoupper($order->productCategory->product_name) }}
                                            </span>
                                        @endif
                                        @if ($order->priority === 'high')
                                            <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Date In --}}
                                <td class="py-3 px-3 text-center">
                                    <div class="flex flex-col items-center leading-tight">
                                        <span class="text-gray-900 text-sm font-semibold">{{ \Carbon\Carbon::parse($order->order_date)->format('d') }}</span>
                                        <span class="text-gray-600 text-[10px]">{{ \Carbon\Carbon::parse($order->order_date)->format('M') }}</span>
                                        <span class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($order->order_date)->format('Y') }}</span>
                                    </div>
                                </td>

                                {{-- Date Out (Deadline) --}}
                                <td class="py-3 px-3 text-center">
                                    <div class="flex flex-col items-center leading-tight">
                                        <span class="text-gray-900 text-sm font-semibold">{{ \Carbon\Carbon::parse($order->deadline)->format('d') }}</span>
                                        <span class="text-gray-600 text-[10px]">{{ \Carbon\Carbon::parse($order->deadline)->format('M') }}</span>
                                        <span class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($order->deadline)->format('Y') }}</span>
                                    </div>
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
                                        @if (!$isViewOnly)
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-5 h-5 text-gray-600" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 italic px-2">View Only</span>
                                        @endif

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
                            <tr x-show="searchQuery.trim() === ''" x-cloak>
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

                        @foreach ($allOrders as $order)
                            @php
                                // Get all order stages for this order
                                $orderStagesMap = $order->orderStages->keyBy('stage_id');
                            @endphp
                            <tr class="hover:bg-gray-50"
                                data-order="true"
                                data-invoice="{{ $order->invoice->invoice_no ?? '' }}"
                                data-customer="{{ $order->customer->customer_name ?? '' }}"
                                x-show="searchQuery.trim() !== '' && (
                                    '{{ strtolower($order->invoice->invoice_no ?? '') }}'.includes(searchQuery.toLowerCase()) ||
                                    '{{ strtolower($order->customer->customer_name ?? '') }}'.includes(searchQuery.toLowerCase())
                                )">
                                {{-- Customer --}}
                                <td class="py-3 px-3">
                                    <div>
                                        <p class="text-gray-700 whitespace-nowrap">
                                            {{ $order->customer->customer_name ?? '-' }}</p>
                                        <p class="text-[10px] text-gray-500">
                                            @if($order->customer && $order->customer->phone)
                                                {{ substr($order->customer->phone, 0, 4) }}****{{ substr($order->customer->phone, -3) }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                </td>

                                {{-- Order (Invoice + Product + Priority) --}}
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5 flex-wrap max-w-[200px]">
                                        <span class="font-medium text-gray-900 text-[11px]">
                                            {{ $order->invoice->invoice_no ?? '-' }}
                                        </span>
                                        @if ($order->productCategory)
                                            <span
                                                class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">
                                                {{ strtoupper($order->productCategory->product_name) }}
                                            </span>
                                        @endif
                                        @if ($order->priority === 'high')
                                            <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Date In --}}
                                <td class="py-3 px-3 text-center">
                                    <div class="flex flex-col items-center leading-tight">
                                        <span class="text-gray-900 text-sm font-semibold">{{ \Carbon\Carbon::parse($order->order_date)->format('d') }}</span>
                                        <span class="text-gray-600 text-[10px]">{{ \Carbon\Carbon::parse($order->order_date)->format('M') }}</span>
                                        <span class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($order->order_date)->format('Y') }}</span>
                                    </div>
                                </td>

                                {{-- Date Out (Deadline) --}}
                                <td class="py-3 px-3 text-center">
                                    <div class="flex flex-col items-center leading-tight">
                                        <span class="text-gray-900 text-sm font-semibold">{{ \Carbon\Carbon::parse($order->deadline)->format('d') }}</span>
                                        <span class="text-gray-600 text-[10px]">{{ \Carbon\Carbon::parse($order->deadline)->format('M') }}</span>
                                        <span class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($order->deadline)->format('Y') }}</span>
                                    </div>
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
                                                    <div class="font-medium text-gray-700">{{ \Carbon\Carbon::parse($orderStage->start_date)->format('d M') }}</div>
                                                    <div class="text-gray-500">{{ \Carbon\Carbon::parse($orderStage->deadline)->format('d M') }}</div>
                                                </div>
                                            @else
                                                <div class="text-[9px] text-gray-400">No date</div>
                                            @endif

                                            {{-- Status Icon --}}
                                            <div class="flex items-center gap-1">
                                                @if ($status === 'done')
                                                    <svg class="w-4 h-4 {{ $statusColor }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @elseif ($status === 'in_progress')
                                                    <svg class="w-4 h-4 {{ $statusColor }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 {{ $statusColor }}" fill="currentColor" viewBox="0 0 20 20">
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
                                    
                                            const dropdownHeight = dropdown ? dropdown.offsetHeight : 150;
                                            const dropUp = spaceBelow < (dropdownHeight + 20) && spaceAbove > spaceBelow;
                                    
                                            if (dropUp) {
                                                this.dropdownStyle = {
                                                    position: 'fixed',
                                                    top: (rect.top - dropdownHeight - 8) + 'px',
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
                                        @if (!$isViewOnly)
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 italic px-2">View Only</span>
                                        @endif

                                        {{-- Dropdown Menu with Fixed Position --}}
                                        <div x-show="open" @click.away="open = false" x-cloak x-ref="dropdown"
                                            :style="dropdownStyle"
                                            class="bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-2">
                                            <button type="button"
                                                @click="openEditStageModal({{ $order->id }}, '{{ $order->invoice->invoice_no ?? '' }}', {{ Js::from($order->orderStages->map(function($os) use ($productionStages) { return ['id' => $os->id, 'stage_name' => $productionStages->firstWhere('id', $os->stage_id)?->stage_name ?? 'Unknown', 'status' => $os->status, 'start_date' => $os->start_date?->format('d M Y'), 'deadline' => $os->deadline?->format('d M Y')]; })) }}, '{{ $order->notes ?? '' }}'); open = false"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edit Stage Status
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr x-show="searchQuery.trim() !== '' && !hasResults" x-cloak>
                            <td colspan="{{ 4 + count($productionStages) + 1 }}"
                                class="py-8 text-center text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">No orders found for "<span x-text="searchQuery"></span>"</p>
                                <p class="text-xs mt-1">Try searching with a different keyword</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination - Hidden during search --}}
            <div id="task-manager-pagination-container" class="mt-5" x-show="searchQuery.trim() === ''">
                <x-custom-pagination :paginator="$orders" />
            </div>
        </div>
    </section>

        {{-- ================= STAGE DATE MODAL ================= --}}
        <div x-show="showStageModal" x-cloak class="fixed inset-0 z-50">
            {{-- Background Overlay --}}
            <div x-show="showStageModal" x-transition.opacity @click="showStageModal = false; resetStageForm()"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showStageModal = false; resetStageForm()" x-transition
                    class="relative bg-white rounded-xl shadow-2xl max-w-md w-full z-10"
                    style="height: min(calc(100vh - 6rem), 325px); min-height: 0; display: flex; flex-direction: column;">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
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

                {{-- Scrollable Content --}}
                <div class="flex-1">
                    <form id="stageDateForm" class="px-6 py-4 space-y-4"
                    @submit.prevent="
                    isSubmittingStage = true;
                    stageErrors = {};
                    
                    // Save current scroll position
                    const scrollPosition = window.scrollY || document.documentElement.scrollTop;
                    
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('order_id', selectedOrderId);
                    formData.append('stage_id', selectedStageId);
                    formData.append('start_date', stageStartDate);
                    formData.append('deadline', stageDeadline);
                    
                    fetch('{{ route($routeName . '.update-stage') }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            showStageModal = false;
                            resetStageForm();
                            
                            // Show toast
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: data.message, type: 'success' }
                            }));
                            
                            // Refresh table content without full reload
                            const currentUrl = window.location.href;
                            fetch(currentUrl, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.getElementById('task-manager-section');
                                
                                if (newSection) {
                                    document.getElementById('task-manager-section').innerHTML = newSection.innerHTML;
                                    setupPagination('task-manager-pagination-container', 'task-manager-section');
                                    
                                    // Restore scroll position after a short delay
                                    setTimeout(() => {
                                        window.scrollTo({ top: scrollPosition, behavior: 'smooth' });
                                    }, 100);
                                }
                            })
                            .catch(err => {
                                console.error('Failed to refresh table:', err);
                            });
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
                        {{-- Start Date --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <div class="relative cursor-pointer" @click="$refs.startDateInput.showPicker()">
                                <input x-ref="startDateInput" type="date" x-model="stageStartDate"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 cursor-pointer">
                            </div>
                            <p x-show="stageErrors.start_date" x-text="stageErrors.start_date"
                                class="text-xs text-red-600 mt-1"></p>
                        </div>

                        {{-- Deadline --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deadline</label>
                            <div class="relative cursor-pointer" @click="$refs.deadlineInput.showPicker()">
                                <input x-ref="deadlineInput" type="date" x-model="stageDeadline"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 cursor-pointer">
                            </div>
                            <p x-show="stageErrors.deadline" x-text="stageErrors.deadline"
                                class="text-xs text-red-600 mt-1"></p>
                        </div>

                        {{-- General Error --}}
                        <p x-show="stageErrors.general" x-text="stageErrors.general"
                            class="text-xs text-red-600 bg-red-50 p-2 rounded"></p>
                    </form>
                </div>

                {{-- Fixed Footer --}}
                <div class="flex gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                    <button type="button" @click="showStageModal = false; resetStageForm()"
                        class="flex-1 px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" form="stageDateForm" :disabled="isSubmittingStage"
                        class="flex-1 px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <span x-show="!isSubmittingStage">Apply</span>
                        <span x-show="isSubmittingStage">Saving...</span>
                    </button>
                </div>
                </div>
            </div>
        </div>

        {{-- ================= EDIT STAGE MODAL ================= --}}
        <div x-show="showEditStageModal" x-cloak class="fixed inset-0 z-50">
            {{-- Background Overlay --}}
            <div x-show="showEditStageModal" x-transition.opacity @click="showEditStageModal = false"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showEditStageModal = false" x-transition
                    class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full z-10"
                    style="height: min(calc(100vh - 6rem), 700px); min-height: 0; display: flex; flex-direction: column;">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
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

                {{-- Scrollable Content --}}
                <div class="overflow-y-auto flex-1 px-6 py-4">
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
                                                    'bg-yellow-500': stage.status === 'pending',
                                                    'bg-blue-500': stage.status === 'in_progress',
                                                    'bg-primary': stage.status === 'done'
                                                }">
                                                <svg class="w-5 h-5 text-white"
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
                                                {{-- Always show full name --}}
                                                <p class="font-medium text-gray-900 text-sm" x-text="stage.stage_name"></p>
                                                <p class="text-xs text-gray-500">
                                                    <span class="hidden sm:inline">Current: </span>
                                                    <span class="font-medium capitalize"
                                                        x-text="stage.status.replace('_', ' ')"></span>
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Status Buttons - PENDING (Yellow), IN PROGRESS (Blue), and DONE (Green) - SOLID --}}
                                        <div class="flex flex-wrap gap-1.5 sm:gap-2">
                                            <button type="button" @click="changeStageStatus(stage.id, 'pending')"
                                                :disabled="isUpdatingStatus || {{ $isViewOnly ? 'true' : 'false' }}"
                                                :class="[
                                                    stage.status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-200 hover:bg-yellow-400 text-gray-700 hover:text-white',
                                                    pendingChanges[stage.id] === 'pending' ? 'shadow-lg shadow-yellow-500/50' : ''
                                                ]"
                                                class="px-2 sm:px-3 py-1.5 rounded-md text-[10px] sm:text-xs font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span class="hidden sm:inline">Pending</span>
                                                <span class="sm:hidden">P</span>
                                            </button>
                                            <button type="button" @click="changeStageStatus(stage.id, 'in_progress')"
                                                :disabled="isUpdatingStatus || {{ $isViewOnly ? 'true' : 'false' }}"
                                                :class="[
                                                    stage.status === 'in_progress' ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-blue-400 text-gray-700 hover:text-white',
                                                    pendingChanges[stage.id] === 'in_progress' ? 'shadow-lg shadow-blue-500/50' : ''
                                                ]"
                                                class="px-2 sm:px-3 py-1.5 rounded-md text-[10px] sm:text-xs font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span class="hidden sm:inline">In Progress</span>
                                                <span class="sm:hidden">IP</span>
                                            </button>
                                            <button type="button" @click="changeStageStatus(stage.id, 'done')"
                                                :disabled="isUpdatingStatus || {{ $isViewOnly ? 'true' : 'false' }}"
                                                :class="[
                                                    stage.status === 'done' ? 'bg-primary text-white' : 'bg-gray-200 hover:bg-primary text-gray-700 hover:text-white',
                                                    pendingChanges[stage.id] === 'done' ? 'shadow-lg shadow-primary/50' : ''
                                                ]"
                                                class="px-2 sm:px-3 py-1.5 rounded-md text-[10px] sm:text-xs font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span class="hidden sm:inline">Done</span>
                                                <span class="sm:hidden">D</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Fixed Footer --}}
                <div class="flex justify-between sm:justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                    <button @click="saveStageChanges()" 
                        :disabled="!hasChanges || isUpdatingStatus || {{ $isViewOnly ? 'true' : 'false' }}"
                        :class="hasChanges && !isUpdatingStatus ? 'bg-primary hover:bg-primary-dark text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors disabled:cursor-not-allowed flex items-center gap-2">
                        <svg x-show="isUpdatingStatus" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isUpdatingStatus ? 'Updating...' : 'Update'"></span>
                    </button>
                    <button @click="showEditStageModal = false; pendingChanges = {};"
                        :disabled="isUpdatingStatus"
                        class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Close
                    </button>
                </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function setupPagination(paginationContainerId, contentSectionId) {
            const paginationContainer = document.getElementById(paginationContainerId);
            if (!paginationContainer) return;

            paginationContainer.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (!link || !link.href) return;

                // Ignore disabled links
                if (link.classList.contains('disabled') || link.getAttribute('aria-disabled') === 'true') {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                const url = link.href;

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
                        const newContent = doc.getElementById(contentSectionId);

                        if (newContent) {
                            document.getElementById(contentSectionId).innerHTML = newContent.innerHTML;
                            window.history.pushState({}, '', url);

                            // Scroll to filter section
                            const filterSection = document.getElementById('filter-section');
                            if (filterSection) {
                                filterSection.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            }

                            // Re-setup pagination after content update
                            setTimeout(() => setupPagination(paginationContainerId, contentSectionId), 100);
                        }

                        NProgress.done();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        NProgress.done();
                    });
            });
        }

        // Initialize pagination on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('task-manager-pagination-container', 'task-manager-section');
        });
    </script>
@endpush
