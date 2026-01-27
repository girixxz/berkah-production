@extends('layouts.app')

@section('title', 'Loan Capital')

@section('content')
    <x-nav-locate :items="['Finance', 'Loan Capital']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        searchQuery: '{{ request('search') }}',
        statusFilter: '{{ $statusFilter }}',
        currentMonth: {{ $currentDate->month }},
        currentYear: {{ $currentDate->year }},
        displayText: '{{ $currentDate->format('F Y') }}',
        showAddLoanModal: false,
        loanAmount: '',
        loanErrors: {},
        isSubmittingLoan: false,
        init() {
            // Check for toast message from sessionStorage
            const message = sessionStorage.getItem('toast_message');
            const type = sessionStorage.getItem('toast_type');
            if (message) {
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message, type: type || 'success' }
                    }));
                }, 300);
                sessionStorage.removeItem('toast_message');
                sessionStorage.removeItem('toast_type');
            }
        },
        matchesSearch(row) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const query = this.searchQuery.toLowerCase();
            const trxNo = (row.getAttribute('data-trx') || '').toLowerCase();
            const note = (row.getAttribute('data-note') || '').toLowerCase();
            return trxNo.includes(query) || note.includes(query);
        },
        get hasVisibleRows() {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const tbody = document.querySelector('tbody');
            if (!tbody) return true;
            const rows = tbody.querySelectorAll('tr[data-trx]');
            for (let row of rows) {
                if (this.matchesSearch(row)) return true;
            }
            return false;
        },
        navigateMonth(direction) {
            let newMonth = this.currentMonth;
            let newYear = this.currentYear;
            
            if (direction === 'prev') {
                newMonth--;
                if (newMonth < 1) {
                    newMonth = 12;
                    newYear--;
                }
            } else if (direction === 'next') {
                newMonth++;
                if (newMonth > 12) {
                    newMonth = 1;
                    newYear++;
                }
            } else if (direction === 'reset') {
                const now = new Date();
                newMonth = now.getMonth() + 1;
                newYear = now.getFullYear();
            }
            
            this.loadCalendar(newMonth, newYear);
        },
        loadCalendar(month, year) {
            this.currentMonth = month;
            this.currentYear = year;
            
            const params = new URLSearchParams(window.location.search);
            params.set('month', month);
            params.set('year', year);
            
            const url = '{{ route('finance.loan-capital') }}?' + params.toString();
            window.history.pushState({}, '', url);
            
            // Update display text
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            this.displayText = monthNames[month - 1] + ' ' + year;
            
            NProgress.start();
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update stats cards section
                const newStatsSection = doc.getElementById('stats-section');
                if (newStatsSection) {
                    document.getElementById('stats-section').innerHTML = newStatsSection.innerHTML;
                }
                
                // Update table section
                const newLoanSection = doc.getElementById('loan-section');
                if (newLoanSection) {
                    document.getElementById('loan-section').innerHTML = newLoanSection.innerHTML;
                    setupPagination('loan-pagination-container', 'loan-section');
                }
                
                NProgress.done();
            })
            .catch(error => {
                console.error('Error:', error);
                NProgress.done();
            });
        },
        applyFilter() {
            const params = new URLSearchParams();
            params.set('month', this.currentMonth);
            params.set('year', this.currentYear);
            params.set('status', this.statusFilter);
            if (this.searchQuery) params.set('search', this.searchQuery);
            
            const perPageValue = this.getPerPageValue();
            if (perPageValue) params.set('per_page', perPageValue);
            
            const url = '{{ route('finance.loan-capital') }}?' + params.toString();
            window.history.pushState({}, '', url);
            
            NProgress.start();
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newSection = doc.getElementById('loan-section');
                if (newSection) {
                    document.getElementById('loan-section').innerHTML = newSection.innerHTML;
                    setupPagination('loan-pagination-container', 'loan-section');
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
            return urlParams.get('per_page') || '10';
        }
    }" class="space-y-6">

        {{-- ================= SECTION 1: DATE NAVIGATION (RIGHT ALIGNED) ================= --}}
        <div class="flex items-center justify-end gap-3">
            <button type="button" @click="navigateMonth('prev')" 
                class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="px-4 py-2 text-center min-w-[150px]">
                <span class="text-base font-semibold text-gray-900 whitespace-nowrap" x-text="displayText">
                    {{ $currentDate->format('F Y') }}
                </span>
            </div>
            <button type="button" @click="navigateMonth('next')" 
                class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <button type="button" @click="navigateMonth('reset')" 
                class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg transition-colors cursor-pointer flex-shrink-0">
                This Month
            </button>
        </div>

        {{-- ================= SECTION 2: STATS CARDS ================= --}}
        <div id="stats-section" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Total Balance --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($totalBalance, 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Outstanding --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Outstanding <span class="text-xs font-medium text-orange-600">(All Time)</span></p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($outstanding, 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 3: TABLE ================= --}}
        <div class="bg-white border border-gray-200 rounded-lg" id="loan-section">
            {{-- Header: Filters & Actions --}}
            <div class="p-5 border-b border-gray-200">
                <div class="flex flex-col xl:flex-row xl:items-center gap-4">
                    {{-- Status Filter Buttons - Full width on mobile (3 buttons) --}}
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="statusFilter = 'all'; applyFilter();"
                            :class="statusFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            All
                        </button>
                        <button type="button" @click="statusFilter = 'outstanding'; applyFilter();"
                            :class="statusFilter === 'outstanding' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-orange-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Outstanding
                        </button>
                        <button type="button" @click="statusFilter = 'done'; applyFilter();"
                            :class="statusFilter === 'done' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-green-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Done
                        </button>
                    </div>

                    {{-- Right: Search, Show Per Page, History Icon --}}
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">
                        {{-- Search, Show Per Page & History Icon - Same row --}}
                        <div class="flex gap-2 items-center xl:flex-1 xl:min-w-0">
                            {{-- Search --}}
                            <div class="flex-1 xl:min-w-[180px]">
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text" x-model="searchQuery" @input.debounce.300ms="applyFilter()"
                                        placeholder="Search..."
                                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>

                            {{-- Show Per Page --}}
                            <div x-data="{
                                open: false,
                                perPage: {{ $perPage }},
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
                                    return this.options.find(o => o.value === this.perPage) || this.options[1];
                                },
                                selectOption(option) {
                                    this.perPage = option.value;
                                    this.open = false;
                                    this.applyPerPageFilter();
                                },
                                applyPerPageFilter() {
                                    const params = new URLSearchParams(window.location.search);
                                    params.set('per_page', this.perPage);
                                    params.delete('page');
                                    
                                    const url = '{{ route('finance.loan-capital') }}?' + params.toString();
                                    window.history.pushState({}, '', url);
                                    
                                    NProgress.start();
                                    fetch(url, {
                                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                    })
                                    .then(response => response.text())
                                    .then(html => {
                                        const parser = new DOMParser();
                                        const doc = parser.parseFromString(html, 'text/html');
                                        const newSection = doc.getElementById('loan-section');
                                        if (newSection) {
                                            document.getElementById('loan-section').innerHTML = newSection.innerHTML;
                                            setupPagination('loan-pagination-container', 'loan-section');
                                        }
                                        NProgress.done();
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        NProgress.done();
                                    });
                                }
                            }" class="relative flex-shrink-0">
                                <button type="button" @click="open = !open"
                                    class="w-15 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="selected.label"></span>
                                    <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
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

                            {{-- History Icon Button - Icon only on mobile, with text on desktop --}}
                            <a href="{{ route('finance.loan-capital.repayment-history') }}"
                                class="px-3 xl:px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-blue-600 hover:text-white transition flex items-center gap-2 flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="hidden xl:inline">History</span>
                            </a>
                        </div>

                        {{-- Add Loan Button - Separate row on mobile, same row on desktop --}}
                        <button type="button" @click="showAddLoanModal = true; loanErrors = {};"
                            class="w-full xl:w-auto px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark transition flex items-center justify-center gap-2 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Loan
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto px-5 pb-5">
                <table class="w-full text-sm">
                    <thead class="bg-primary-light text-gray-600">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold rounded-l-lg">Date</th>
                            <th class="py-3 px-4 text-left font-bold">Balance Period</th>
                            <th class="py-3 px-4 text-left font-bold">Payment Method</th>
                            <th class="py-3 px-4 text-left font-bold">Amount</th>
                            <th class="py-3 px-4 text-left font-bold">Note</th>
                            <th class="py-3 px-4 text-left font-bold">Attachment</th>
                            <th class="py-3 px-4 text-left font-bold">Status</th>
                            <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" x-data="{
                        get hasResults() {
                            if (!searchQuery || searchQuery.trim() === '') return true;
                            const search = searchQuery.toLowerCase();
                            return {{ Js::from($allLoans->map(fn($l) => strtolower(($l->balance ? $l->balance->period_start->format('F Y') : '') . ' ' . ($l->notes ?? '')))) }}
                                .some(text => text.includes(search));
                        }
                    }">
                        @forelse ($loans as $loan)
                            <tr data-trx="{{ $loan->balance ? $loan->balance->period_start->format('F Y') : '' }}" data-note="{{ $loan->notes }}"
                                x-show="searchQuery.trim() === ''">
                                <td class="py-3 px-4 whitespace-nowrap text-gray-500">
                                    {{ $loan->loan_date->format('d M Y') }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-gray-900">
                                    @if($loan->balance)
                                        {{ $loan->balance->period_start->format('F Y') }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                        {{ $loan->payment_method === 'transfer' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                        {{ ucfirst($loan->payment_method) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-900">
                                    Rp {{ number_format($loan->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-gray-500">
                                    {{ Str::limit($loan->notes ?? '-', 30) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-left">
                                    @if ($loan->proof_img)
                                        <button @click="$dispatch('open-image-modal', { url: '{{ route('finance.loan-capital.serve-image', $loan->id) }}' })"
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-xs font-medium cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            View
                                        </button>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($loan->status === 'outstanding')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                            Outstanding
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Done
                                        </span>
                                    @endif
                                </td>
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
                                            {{-- Three Dot Button --}}
                                            <button x-ref="button" @click="checkPosition(); open = !open"
                                                type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown Menu --}}
                                            <div x-show="open" @click.away="open = false" x-cloak x-ref="dropdown"
                                                :style="dropdownStyle"
                                                class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                {{-- Edit --}}
                                                <button type="button"
                                                    @click="
                                                        $dispatch('open-edit-modal', {
                                                            id: {{ $loan->id }},
                                                            balance_period: '{{ $loan->balance ? $loan->balance->period_start->format('F Y') : '-' }}',
                                                            date: '{{ $loan->loan_date->format('d M Y') }}',
                                                            method: '{{ $loan->payment_method }}',
                                                            amount: '{{ number_format($loan->amount, 0, ',', '.') }}',
                                                            notes: '{{ addslashes($loan->notes ?? '') }}',
                                                            image: '{{ $loan->proof_img ? route('finance.loan-capital.serve-image', $loan->id) : '' }}'
                                                        });
                                                        open = false;
                                                    "
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </button>

                                                {{-- Repayment (only if outstanding) --}}
                                                @if ($loan->status === 'outstanding')
                                                    <button type="button"
                                                        @click="$dispatch('open-repayment-modal', { id: {{ $loan->id }}, loan_balance_period: '{{ $loan->balance ? $loan->balance->period_start->format('F Y') : '-' }}', amount: {{ $loan->amount }}, remaining_amount: {{ $loan->remaining_amount }}, payment_method: '{{ $loan->payment_method }}' })"
                                                        class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Repayment
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr x-show="searchQuery.trim() === ''">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p>No loan capital records for this month</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- Search Results for All Loans --}}
                        @foreach ($allLoans as $loan)
                            <tr data-trx="{{ $loan->loan_code }}" data-note="{{ $loan->notes }}"
                                x-show="searchQuery.trim() !== '' && matchesSearch($el)">
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-gray-900">
                                    {{ $loan->loan_code }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-500">
                                    {{ $loan->loan_date->format('d M Y') }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                        {{ $loan->payment_method === 'transfer' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                        {{ ucfirst($loan->payment_method) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-900">
                                    Rp {{ number_format($loan->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-gray-500">
                                    {{ Str::limit($loan->notes ?? '-', 30) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-center">
                                    @if ($loan->proof_img)
                                        <button @click="$dispatch('open-image-modal', { url: '{{ route('finance.loan-capital.serve-image', $loan->id) }}' })"
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-xs font-medium cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            View
                                        </button>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($loan->status === 'outstanding')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                            Outstanding
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Done
                                        </span>
                                    @endif
                                </td>
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
                                            {{-- Three Dot Button --}}
                                            <button x-ref="button" @click="checkPosition(); open = !open"
                                                type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown Menu --}}
                                            <div x-show="open" @click.away="open = false" x-cloak x-ref="dropdown"
                                                :style="dropdownStyle"
                                                class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                {{-- Edit --}}
                                                <button type="button"
                                                    @click="
                                                        $dispatch('open-edit-modal', {
                                                            id: {{ $loan->id }},
                                                            balance_period: '{{ $loan->balance ? $loan->balance->period_start->format('F Y') : '-' }}',
                                                            date: '{{ $loan->loan_date->format('d M Y') }}',
                                                            method: '{{ $loan->payment_method }}',
                                                            amount: '{{ number_format($loan->amount, 0, ',', '.') }}',
                                                            notes: '{{ addslashes($loan->notes ?? '') }}',
                                                            image: '{{ $loan->proof_img ? route('finance.loan-capital.serve-image', $loan->id) : '' }}'
                                                        });
                                                        open = false;
                                                    "
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </button>

                                                {{-- Repayment (only if outstanding) --}}
                                                @if ($loan->status === 'outstanding')
                                                    <button type="button"
                                                        @click="$dispatch('open-repayment-modal', { id: {{ $loan->id }}, loan_balance_period: '{{ $loan->balance ? $loan->balance->period_start->format('F Y') : '-' }}', amount: {{ $loan->amount }}, remaining_amount: {{ $loan->remaining_amount }}, payment_method: '{{ $loan->payment_method }}' })"
                                                        class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Repayment
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- Client-side No Results Message --}}
                        <tr x-show="searchQuery.trim() !== '' && !hasResults" x-cloak>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p>No results found for "<span x-text="searchQuery"></span>"</p>
                                    <p class="text-sm text-gray-400 mt-1">Try searching with different keywords</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div x-show="searchQuery.trim() === ''" id="loan-pagination-container" class="px-6 py-4 border-gray-200">
                <x-custom-pagination :paginator="$loans" />
            </div>
        </div>

        {{-- ================= ADD LOAN MODAL ================= --}}
        <div x-show="showAddLoanModal" x-cloak
            x-data="{
                balanceMonth: null,
                balanceYear: null,
                balanceId: null,
                balanceTransfer: 0,
                balanceCash: 0,
                balanceMonthDropdownOpen: false,
                balanceYearDropdownOpen: false,
                balanceMonthOptions: [
                    { value: 1, name: 'January' },
                    { value: 2, name: 'February' },
                    { value: 3, name: 'March' },
                    { value: 4, name: 'April' },
                    { value: 5, name: 'May' },
                    { value: 6, name: 'June' },
                    { value: 7, name: 'July' },
                    { value: 8, name: 'August' },
                    { value: 9, name: 'September' },
                    { value: 10, name: 'October' },
                    { value: 11, name: 'November' },
                    { value: 12, name: 'December' }
                ],
                balanceYearOptions: [],
                init() {
                    // Generate year options (current year onwards)
                    const currentYear = new Date().getFullYear();
                    for (let i = 0; i < 10; i++) {
                        this.balanceYearOptions.push({ value: currentYear + i, name: (currentYear + i).toString() });
                    }
                },
                get selectedMonthName() {
                    const month = this.balanceMonthOptions.find(m => m.value === this.balanceMonth);
                    return month ? month.name : null;
                },
                get hasBalancePeriod() {
                    return this.balanceMonth !== null && this.balanceYear !== null;
                },
                async selectMonth(month) {
                    this.balanceMonth = month;
                    this.balanceMonthDropdownOpen = false;
                    if (this.balanceYear) {
                        await this.fetchBalanceData();
                    }
                },
                async selectYear(year) {
                    this.balanceYear = year;
                    this.balanceYearDropdownOpen = false;
                    if (this.balanceMonth) {
                        await this.fetchBalanceData();
                    }
                },
                async fetchBalanceData() {
                    if (!this.balanceMonth || !this.balanceYear) return;
                    
                    try {
                        const response = await fetch(`/finance/balance/find-by-period?month=${this.balanceMonth}&year=${this.balanceYear}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        
                        if (data.success && data.balance) {
                            this.balanceId = data.balance.id;
                            this.balanceTransfer = data.balance.transfer_balance;
                            this.balanceCash = data.balance.cash_balance;
                        } else {
                            // Balance not found - set to 0, no error
                            this.balanceId = null;
                            this.balanceTransfer = 0;
                            this.balanceCash = 0;
                        }
                    } catch (error) {
                        console.error('Error fetching balance:', error);
                        this.balanceId = null;
                        this.balanceTransfer = 0;
                        this.balanceCash = 0;
                    }
                }
            }"
            x-init="$watch('showAddLoanModal', value => {
                if (value) {
                    $refs.loanDate.value = new Date().toISOString().split('T')[0];
                    
                    // Reset balance period
                    balanceMonth = null;
                    balanceYear = null;
                    balanceId = null;
                    balanceTransfer = 0;
                    balanceCash = 0;
                }
            })"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">
            
            {{-- Background Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showAddLoanModal = false; loanAmount = ''; loanErrors = {};"
                    class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Loan</h3>
                    <button @click="showAddLoanModal = false; loanAmount = ''; loanErrors = {};" type="button"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                        ✕
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <form id="addLoanForm"
                        @submit.prevent="
                            loanErrors = {};
                            let hasValidationError = false;
                            const formData = new FormData($event.target);
                            
                            // Validate balance_month
                            if (!formData.get('balance_month')) {
                                loanErrors.balance_month = ['Month is required'];
                                hasValidationError = true;
                            }
                            
                            // Validate balance_year
                            if (!formData.get('balance_year')) {
                                loanErrors.balance_year = ['Year is required'];
                                hasValidationError = true;
                            }
                            
                            // Validate payment_method
                            if (!formData.get('payment_method')) {
                                loanErrors.payment_method = ['Payment method is required'];
                                hasValidationError = true;
                            }
                            
                            // Validate amount
                            const amount = formData.get('amount');
                            if (!amount || amount === '0' || amount === '') {
                                loanErrors.amount = ['Amount is required'];
                                hasValidationError = true;
                            }
                            
                            // Validate image
                            const imageFile = formData.get('image');
                            if (!imageFile || imageFile.size === 0) {
                                loanErrors.image = ['Payment proof image is required'];
                                hasValidationError = true;
                            }
                            
                            if (hasValidationError) {
                                return;
                            }
                            
                            isSubmittingLoan = true;
                            fetch('{{ route('finance.loan-capital.store') }}', {
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
                                    sessionStorage.setItem('toast_message', 'Loan capital added successfully');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else if (status === 422) {
                                    isSubmittingLoan = false;
                                    loanErrors = data.errors || {};
                                } else {
                                    isSubmittingLoan = false;
                                    loanErrors = data.errors || {};
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingLoan = false;
                                if (err.message !== 'Redirect detected') {
                                    console.error('Loan error:', err);
                                }
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: 'Failed to add loan. Please try again.', type: 'error' }
                                }));
                            });
                        ">
                        <div class="space-y-4">
                            {{-- Balance Period Selector (Always visible) --}}
                            <div class="mb-6 p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                <label class="block text-sm font-semibold text-gray-900 mb-3">
                                    Select Balance Period <span class="text-red-600">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Month Selector --}}
                                    <div class="relative">
                                        <button type="button" @click="balanceMonthDropdownOpen = !balanceMonthDropdownOpen"
                                            class="w-full flex justify-between items-center rounded-lg border-2 border-primary/40 bg-white px-4 py-2.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary transition-all hover:border-primary">
                                            <span x-text="selectedMonthName || 'Select Month'"
                                                :class="!selectedMonthName ? 'text-gray-400' : 'text-gray-900'"></span>
                                            <svg class="w-4 h-4 text-primary transition-transform" :class="balanceMonthDropdownOpen && 'rotate-180'" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="balanceMonthDropdownOpen" @click.away="balanceMonthDropdownOpen = false" x-cloak
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="fixed z-[100] mt-1 w-[200px] bg-white border-2 border-primary/30 rounded-lg shadow-2xl">
                                            <ul class="max-h-60 overflow-y-auto py-1">
                                                <template x-for="month in balanceMonthOptions" :key="month.value">
                                                    <li @click="selectMonth(month.value)"
                                                        class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/10 transition-colors"
                                                        :class="{ 'bg-primary/20 font-semibold text-primary': balanceMonth === month.value }">
                                                        <span x-text="month.name"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Year Selector --}}
                                    <div class="relative">
                                        <button type="button" @click="balanceYearDropdownOpen = !balanceYearDropdownOpen"
                                            class="w-full flex justify-between items-center rounded-lg border-2 border-primary/40 bg-white px-4 py-2.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary transition-all hover:border-primary">
                                            <span x-text="balanceYear || 'Select Year'"
                                                :class="!balanceYear ? 'text-gray-400' : 'text-gray-900'"></span>
                                            <svg class="w-4 h-4 text-primary transition-transform" :class="balanceYearDropdownOpen && 'rotate-180'" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="balanceYearDropdownOpen" @click.away="balanceYearDropdownOpen = false" x-cloak
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="fixed z-[100] mt-1 w-[200px] bg-white border-2 border-primary/30 rounded-lg shadow-2xl">
                                            <ul class="max-h-60 overflow-y-auto py-1">
                                                <template x-for="year in balanceYearOptions" :key="year.value">
                                                    <li @click="selectYear(year.value)"
                                                        class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/10 transition-colors"
                                                        :class="{ 'bg-primary/20 font-semibold text-primary': balanceYear === year.value }">
                                                        <span x-text="year.name"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-primary font-medium" x-show="hasBalancePeriod">
                                    <span class="font-semibold">Selected:</span> <span x-text="selectedMonthName + ' ' + balanceYear"></span>
                                </p>
                            </div>

                            {{-- Content shown only after Balance Period is selected --}}
                            <div x-show="hasBalancePeriod" x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100">
                                
                                {{-- 2 Cards: Transfer Balance, Cash Balance --}}
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    {{-- Transfer Balance --}}
                                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                        <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                        <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(balanceTransfer).toLocaleString('id-ID')"></p>
                                    </div>
                                    {{-- Cash Balance --}}
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                        <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                        <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(balanceCash).toLocaleString('id-ID')"></p>
                                    </div>
                                </div>

                            {{-- Date (Auto-filled, readonly) --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Loan Date
                                </label>
                                <input type="date" x-ref="loanDate" readonly
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                            </div>

                            {{-- Hidden inputs for balance period --}}
                            <input type="hidden" name="balance_month" x-model="balanceMonth">
                            <input type="hidden" name="balance_year" x-model="balanceYear">

                            {{-- Payment Method & Amount (Row) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                                        if (loanErrors.payment_method) {
                                            delete loanErrors.payment_method;
                                        }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="loanErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white
                                               focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : 'Select Method'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-500'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="payment_method" x-model="selectedValue">
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
                                <p x-show="loanErrors.payment_method" x-cloak
                                    x-text="loanErrors.payment_method?.[0]" class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Amount <span class="text-red-600">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                    <input type="text" x-model="loanAmount"
                                        @input="
                                            let value = $event.target.value.replace(/[^\d]/g, '');
                                            loanAmount = parseInt(value || 0).toLocaleString('id-ID');
                                            $event.target.nextElementSibling.value = value;
                                        "
                                        placeholder="0"
                                        :class="loanErrors.amount ?
                                            'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                            'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full rounded-md pl-10 pr-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                    <input type="hidden" name="amount" :value="loanAmount.replace(/[^\d]/g, '')">
                                </div>
                                <p x-show="loanErrors.amount" x-cloak x-text="loanErrors.amount?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>
                            </div>

                            {{-- Payment Proof Image with Webcam --}}
                            <div x-data="{
                                imagePreview: null,
                                fileName: '',
                                showWebcam: false,
                                stream: null,
                                facingMode: 'environment',
                                isMirrored: true,
                                async startWebcam() {
                                    console.log('Attempting to start webcam...');
                                    console.log('Current URL:', window.location.href);
                                    console.log('Protocol:', window.location.protocol);
                                    
                                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                        alert('Webcam tidak didukung di browser ini. Gunakan browser modern seperti Chrome atau Firefox.');
                                        this.$refs.fileInput.click();
                                        return;
                                    }
                                    
                                    const isSecure = window.location.protocol === 'https:' || 
                                                   window.location.hostname === 'localhost' || 
                                                   window.location.hostname === '127.0.0.1';
                                    
                                    if (!isSecure) {
                                        alert('WEBCAM HARUS PAKAI HTTPS! Akses dengan: https://berkah-production.test atau gunakan Upload File.');
                                        this.$refs.fileInput.click();
                                        return;
                                    }
                                    
                                    try {
                                        console.log('Requesting camera permission...');
                                        this.stream = await navigator.mediaDevices.getUserMedia({ 
                                            video: { 
                                                facingMode: this.facingMode,
                                                width: { ideal: 1280 },
                                                height: { ideal: 720 }
                                            } 
                                        });
                                        console.log('Camera access granted!');
                                        this.$refs.video.srcObject = this.stream;
                                        this.showWebcam = true;
                                    } catch (err) {
                                        console.error('Webcam error:', err);
                                        console.error('Error name:', err.name);
                                        console.error('Error message:', err.message);
                                        
                                        let errorMsg = 'Tidak dapat mengakses webcam. ';
                                        
                                        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                                            errorMsg += 'Permission ditolak! Klik icon gembok/kamera di address bar, pilih Allow untuk Camera, lalu refresh halaman.';
                                        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                                            errorMsg += 'Kamera tidak ditemukan! Pastikan laptop/HP punya kamera dan tidak digunakan aplikasi lain.';
                                        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                                            errorMsg += 'Kamera sedang digunakan aplikasi lain! Tutup aplikasi tersebut lalu coba lagi.';
                                        } else {
                                            errorMsg += 'Error: ' + err.message;
                                        }
                                        
                                        errorMsg += ' Atau gunakan Upload File sebagai alternatif.';
                                        alert(errorMsg);
                                        this.$refs.fileInput.click();
                                    }
                                },
                                async toggleCamera() {
                                    this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
                                    this.isMirrored = this.facingMode === 'user';
                                    
                                    if (this.stream) {
                                        this.stream.getTracks().forEach(track => track.stop());
                                    }
                                    
                                    try {
                                        this.stream = await navigator.mediaDevices.getUserMedia({ 
                                            video: { 
                                                facingMode: this.facingMode,
                                                width: { ideal: 1280 },
                                                height: { ideal: 720 }
                                            } 
                                        });
                                        this.$refs.video.srcObject = this.stream;
                                    } catch (err) {
                                        console.error('Toggle camera error:', err);
                                        alert('Gagal mengganti kamera. Error: ' + err.message);
                                    }
                                },
                                handleFileChange(event) {
                                    const file = event.target.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        this.fileName = file.name;
                                        const reader = new FileReader();
                                        reader.onload = (e) => {
                                            this.imagePreview = e.target.result;
                                        };
                                        reader.readAsDataURL(file);
                                        if (loanErrors.image) {
                                            delete loanErrors.image;
                                        }
                                    }
                                },
                                stopWebcam() {
                                    if (this.stream) {
                                        this.stream.getTracks().forEach(track => track.stop());
                                        this.stream = null;
                                    }
                                    this.showWebcam = false;
                                },
                                capturePhoto() {
                                    const video = this.$refs.video;
                                    const canvas = this.$refs.canvas;
                                    const context = canvas.getContext('2d');
                                    
                                    canvas.width = video.videoWidth;
                                    canvas.height = video.videoHeight;
                                    
                                    // Mirror the image if using front camera
                                    if (this.isMirrored) {
                                        context.translate(canvas.width, 0);
                                        context.scale(-1, 1);
                                    }
                                    
                                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                                    
                                    canvas.toBlob((blob) => {
                                        this.fileName = 'webcam_capture_' + Date.now() + '.jpg';
                                        this.imagePreview = canvas.toDataURL('image/jpeg');
                                        
                                        // Create file from blob
                                        const file = new File([blob], this.fileName, { type: 'image/jpeg' });
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        this.$refs.fileInput.files = dataTransfer.files;
                                        
                                        if (loanErrors.image) {
                                            delete loanErrors.image;
                                        }
                                        
                                        this.stopWebcam();
                                    }, 'image/jpeg', 0.9);
                                }
                            }">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Proof <span class="text-red-600">*</span>
                                </label>

                                {{-- Webcam View --}}
                                <div x-show="showWebcam" x-cloak class="mb-3">
                                    {{-- Video Container --}}
                                    <div class="relative rounded-xl overflow-hidden shadow-xl">
                                        <video x-ref="video" autoplay playsinline 
                                            class="w-full h-auto block bg-black"
                                            style="min-height: 320px; max-height: 480px; object-fit: cover;"
                                            :style="isMirrored ? 'transform: scaleX(-1);' : ''"></video>
                                        <canvas x-ref="canvas" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="capturePhoto()"
                                        class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Capture
                                        </button>
                                        <button type="button" @click="toggleCamera()"
                                        class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="stopWebcam()"
                                        class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Close
                                        </button>
                                    </div>
                                </div>

                                {{-- Image Preview --}}
                                <div x-show="imagePreview && !showWebcam" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="imagePreview" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="fileName"></p>
                                            <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                        </div>
                                        <button type="button" @click="imagePreview = null; fileName = ''; $refs.fileInput.value = ''; startWebcam()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="imagePreview = null; fileName = ''; $refs.fileInput.value = ''"
                                            class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera Button --}}
                                <div x-show="!imagePreview && !showWebcam">
                                    <button type="button" @click="startWebcam()"
                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera
                                    </button>
                                </div>

                                <input type="file" x-ref="fileInput" name="image" accept="image/jpeg,image/png,image/jpg" class="hidden">
                                
                                <p class="mt-1 text-xs text-gray-500">Click to open camera and capture payment proof</p>
                                <p x-show="loanErrors.image" x-cloak x-text="loanErrors.image?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="3"
                                    :class="loanErrors.notes ?
                                        'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                        'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 resize-none"
                                    placeholder="Optional notes..."></textarea>
                                <p x-show="loanErrors.notes" x-cloak x-text="loanErrors.notes?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>
                            </div> {{-- END: Content shown only after Balance Period is selected --}}
                        </div>
                    </form>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button type="button" @click="showAddLoanModal = false; loanAmount = ''; loanErrors = {};"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="addLoanForm"
                        :disabled="isSubmittingLoan || !hasBalancePeriod"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <template x-if="isSubmittingLoan">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="isSubmittingLoan ? 'Processing...' : 'Add Loan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= EDIT LOAN MODAL ================= --}}
    <div x-data="{
        showEditLoanModal: false,
        editLoanId: null,
        editBalancePeriod: '',
        editLoanDate: '',
        editPaymentMethod: '',
        editLoanAmount: '',
        editNotes: '',
        editExistingImage: '',
        editRemoveImage: false,
        editErrors: {},
        isSubmittingEdit: false,
        showWebcam: false,
        stream: null,
        facingMode: 'environment',
        isMirrored: true,
        imagePreview: null,
        fileName: '',
        editPaymentDropdownOpen: false,
        editPaymentOptions: [
            { value: 'transfer', name: 'Transfer' },
            { value: 'cash', name: 'Cash' }
        ],
        get editSelectedPaymentOption() {
            return this.editPaymentOptions.find(opt => opt.value === this.editPaymentMethod);
        },
        selectEditPayment(option) {
            this.editPaymentMethod = option.value;
            this.editPaymentDropdownOpen = false;
            if (this.editErrors.payment_method) {
                delete this.editErrors.payment_method;
            }
        },
        async startWebcam() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Webcam tidak didukung di browser ini. Gunakan browser modern seperti Chrome atau Firefox.');
                return;
            }
            
            const isSecure = window.location.protocol === 'https:' || 
                           window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1';
            
            if (!isSecure) {
                alert('WEBCAM HARUS PAKAI HTTPS! Akses dengan: https://berkah-production.test atau gunakan Upload File.');
                return;
            }
            
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                this.$refs.editVideo.srcObject = this.stream;
                this.showWebcam = true;
            } catch (err) {
                console.error('Webcam error:', err);
                let errorMsg = 'Tidak dapat mengakses webcam. ';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMsg += 'Permission ditolak!';
                } else if (err.name === 'NotFoundError') {
                    errorMsg += 'Kamera tidak ditemukan!';
                } else {
                    errorMsg += err.message;
                }
                alert(errorMsg);
            }
        },
        async toggleCamera() {
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.isMirrored = this.facingMode === 'user';
            
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: this.facingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                this.$refs.editVideo.srcObject = this.stream;
            } catch (err) {
                alert('Gagal mengganti kamera. Error: ' + err.message);
            }
        },
        stopWebcam() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.showWebcam = false;
        },
        capturePhoto() {
            const video = this.$refs.editVideo;
            const canvas = this.$refs.editCanvas;
            const context = canvas.getContext('2d');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            if (this.isMirrored) {
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
            }
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob((blob) => {
                this.fileName = 'webcam_edit_' + Date.now() + '.jpg';
                this.imagePreview = canvas.toDataURL('image/jpeg');
                
                const file = new File([blob], this.fileName, { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.editFileInput.files = dataTransfer.files;
                
                if (this.editErrors.image) {
                    delete this.editErrors.image;
                }
                
                this.stopWebcam();
            }, 'image/jpeg', 0.9);
        }
    }"
    @open-edit-modal.window="
        showEditLoanModal = true;
        editLoanId = $event.detail.id;
        editBalancePeriod = $event.detail.balance_period;
        editLoanDate = $event.detail.date;
        editPaymentMethod = $event.detail.method;
        editLoanAmount = $event.detail.amount;
        editNotes = $event.detail.notes;
        editExistingImage = $event.detail.image;
        editRemoveImage = false;
        editErrors = {};
        showWebcam = false;
        imagePreview = null;
        fileName = '';
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    "
    x-show="showEditLoanModal"
    x-cloak
    class="fixed inset-0 z-50">
        
        {{-- Background Overlay --}}
        <div x-show="showEditLoanModal" x-transition.opacity class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
        
        {{-- Modal Panel --}}
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.away="showEditLoanModal = false; editErrors = {}; editRemoveImage = false;"
                class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl"
                style="height: min(calc(100vh - 6rem), 800px); min-height: 0; display: flex; flex-direction: column;">
            {{-- Fixed Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900">Edit Loan</h3>
                <button @click="showEditLoanModal = false; editErrors = {}; editRemoveImage = false;"
                    class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                    ✕
                </button>
            </div>

            {{-- Scrollable Form Content --}}
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <form @submit.prevent="
                    editErrors = {};
                    isSubmittingEdit = true;
                    
                    const formData = new FormData($event.target);
                    formData.append('_method', 'PUT');
                    if (editRemoveImage) {
                        formData.append('remove_image', '1');
                    }
                    
                    fetch(`/finance/loan-capital/${editLoanId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            sessionStorage.setItem('toast_message', 'Loan capital updated successfully');
                            sessionStorage.setItem('toast_type', 'success');
                            window.location.reload();
                        } else if (data.errors) {
                            isSubmittingEdit = false;
                            editErrors = data.errors;
                        }
                    })
                    .catch(err => {
                        isSubmittingEdit = false;
                        console.error(err);
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Failed to update loan. Please try again.', type: 'error' }
                        }));
                    })
                    .finally(() => {
                        isSubmittingEdit = false;
                    });
                " id="editLoanForm" class="space-y-4">
                    @csrf

                    {{-- Balance Period & Date (Readonly) --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Balance Period (Readonly) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Balance Period
                            </label>
                            <input type="text" x-model="editBalancePeriod" readonly
                                class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                        </div>

                        {{-- Date (Readonly) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Loan Date
                            </label>
                            <input type="text" x-model="editLoanDate" readonly
                                class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                        </div>
                    </div>

                    {{-- Payment Method & Amount (Row) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Payment Method --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Payment Method <span class="text-red-600">*</span>
                            </label>
                            <div class="relative w-full">
                                <button type="button" @click="editPaymentDropdownOpen = !editPaymentDropdownOpen"
                                    :class="editErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white
                                           focus:outline-none focus:ring-2 transition-colors">
                                    <span x-text="editSelectedPaymentOption ? editSelectedPaymentOption.name : 'Select Method'"
                                        :class="!editSelectedPaymentOption ? 'text-gray-400' : 'text-gray-500'"></span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="editPaymentDropdownOpen && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <input type="hidden" name="payment_method" :value="editPaymentMethod">
                                <div x-show="editPaymentDropdownOpen" @click.away="editPaymentDropdownOpen = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="option in editPaymentOptions" :key="option.value">
                                            <li @click="selectEditPayment(option)"
                                                class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                :class="{ 'bg-primary/10 font-medium text-primary': editPaymentMethod === option.value }">
                                                <span x-text="option.name"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            <p x-show="editErrors.payment_method" x-cloak
                                x-text="editErrors.payment_method?.[0]" class="mt-1 text-sm text-red-600"></p>
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Amount <span class="text-red-600">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                <input type="text" x-model="editLoanAmount"
                                    @input="
                                        let value = $event.target.value.replace(/[^\d]/g, '');
                                        editLoanAmount = parseInt(value || 0).toLocaleString('id-ID');
                                        $event.target.nextElementSibling.value = value;
                                    "
                                    placeholder="0"
                                    :class="editErrors.amount ?
                                        'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                        'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    class="w-full rounded-md pl-10 pr-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                <input type="hidden" name="amount" :value="editLoanAmount.replace(/[^\d]/g, '')">
                            </div>
                            <p x-show="editErrors.amount" x-cloak x-text="editErrors.amount?.[0]"
                                class="mt-1 text-sm text-red-600"></p>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Notes (Optional)
                        </label>
                        <textarea name="notes" rows="3" x-model="editNotes"
                            placeholder="Additional notes..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
                    </div>

                    {{-- Payment Proof Image with Webcam --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Payment Proof <span class="text-red-600">*</span>
                        </label>

                        {{-- Existing Image (if exists and not removed) --}}
                        <div x-show="editExistingImage && !editRemoveImage && !imagePreview" class="mb-3 border-2 border-dashed border-blue-400 rounded-lg p-3 bg-blue-50">
                            <div class="flex items-center gap-3">
                                <img :src="editExistingImage" class="w-24 h-24 object-cover rounded-md border-2 border-blue-500">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Current payment proof</p>
                                    <p class="text-xs text-blue-600 mt-1">✓ Existing image</p>
                                </div>
                                <button type="button" @click="editExistingImage = ''; editRemoveImage = false; startWebcam()"
                                    class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                                <button type="button" @click="editRemoveImage = true; editExistingImage = ''"
                                    class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Webcam View --}}
                        <div x-show="showWebcam" x-cloak class="mb-3">
                            <div class="relative rounded-xl overflow-hidden shadow-xl">
                                <video x-ref="editVideo" autoplay playsinline 
                                    class="w-full h-auto block bg-black"
                                    style="min-height: 320px; max-height: 480px; object-fit: cover;"
                                    :style="isMirrored ? 'transform: scaleX(-1);' : ''"></video>
                                <canvas x-ref="editCanvas" class="hidden"></canvas>
                            </div>
                            <div class="flex gap-2 mt-3">
                                <button type="button" @click="capturePhoto()"
                                class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Capture
                                </button>
                                <button type="button" @click="toggleCamera()"
                                class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                    </svg>
                                </button>
                                <button type="button" @click="stopWebcam()"
                                class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Close
                                </button>
                            </div>
                        </div>

                        {{-- Image Preview (after capture) --}}
                        <div x-show="imagePreview && !showWebcam" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                            <div class="flex items-center gap-3">
                                <img :src="imagePreview" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900" x-text="fileName"></p>
                                    <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                </div>
                                <button type="button" @click="imagePreview = null; fileName = ''; $refs.editFileInput.value = ''; startWebcam()"
                                    class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                                <button type="button" @click="imagePreview = null; fileName = ''; $refs.editFileInput.value = ''"
                                    class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Open Camera Button --}}
                        <div x-show="!editExistingImage && !imagePreview && !showWebcam">
                            <button type="button" @click="startWebcam()"
                            class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Open Camera
                            </button>
                        </div>

                        <input type="file" x-ref="editFileInput" name="image" accept="image/jpeg,image/png,image/jpg" class="hidden">
                        
                        <p class="mt-1 text-xs text-gray-500">Click to open camera and capture payment proof</p>
                        <p x-show="editErrors.image" x-cloak x-text="editErrors.image?.[0]"
                            class="mt-1 text-sm text-red-600"></p>
                    </div>
                </form>
            </div>

            {{-- Fixed Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                <button type="button" @click="showEditLoanModal = false; editErrors = {}; editRemoveImage = false;"
                    :disabled="isSubmittingEdit"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Cancel
                </button>
                <button type="submit" form="editLoanForm"
                    :disabled="isSubmittingEdit"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <template x-if="isSubmittingEdit">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="isSubmittingEdit ? 'Updating...' : 'Update Loan'"></span>
                </button>
            </div>
            </div>
        </div>
    </div>

    {{-- ================= REPAYMENT MODAL ================= --}}
    <div x-data="{
        showRepaymentModal: false,
        repaymentLoanId: null,
        repaymentLoanCode: '',
        repaymentLoanAmount: '',
        repaymentRemainingAmount: '',
        repaymentPaidDate: '',
        repaymentPaymentMethod: '',
        repaymentAmount: '',
        repaymentNotes: '',
        repaymentErrors: {},
        isSubmittingRepayment: false,
        showWebcam: false,
        stream: null,
        facingMode: 'environment',
        isMirrored: true,
        imagePreview: null,
        fileName: '',
        repaymentDropdownOpen: false,
        repaymentPaymentOptions: [
            { value: 'transfer', name: 'Transfer' },
            { value: 'cash', name: 'Cash' }
        ],
        get repaymentSelectedPaymentOption() {
            return this.repaymentPaymentOptions.find(opt => opt.value === this.repaymentPaymentMethod);
        },
        selectRepaymentPayment(option) {
            this.repaymentPaymentMethod = option.value;
            this.repaymentDropdownOpen = false;
            if (this.repaymentErrors.payment_method) {
                delete this.repaymentErrors.payment_method;
            }
        },
        
        // Balance Period Selection
        repaymentSelectedBalanceId: null,
        repaymentSelectedMonth: null,
        repaymentSelectedYear: null,
        repaymentBalanceTransfer: 0,
        repaymentBalanceCash: 0,
        repaymentMonthDropdownOpen: false,
        repaymentYearDropdownOpen: false,
        repaymentMonths: [
            { value: 1, name: 'January' },
            { value: 2, name: 'February' },
            { value: 3, name: 'March' },
            { value: 4, name: 'April' },
            { value: 5, name: 'May' },
            { value: 6, name: 'June' },
            { value: 7, name: 'July' },
            { value: 8, name: 'August' },
            { value: 9, name: 'September' },
            { value: 10, name: 'October' },
            { value: 11, name: 'November' },
            { value: 12, name: 'December' }
        ],
        repaymentYears: Array.from({ length: 10 }, (_, i) => new Date().getFullYear() + i),
        get repaymentSelectedMonthName() {
            const month = this.repaymentMonths.find(m => m.value === this.repaymentSelectedMonth);
            return month ? month.name : null;
        },
        get repaymentHasBalancePeriod() {
            return this.repaymentSelectedMonth !== null && this.repaymentSelectedYear !== null;
        },
        async selectRepaymentMonth(month) {
            this.repaymentSelectedMonth = month;
            this.repaymentMonthDropdownOpen = false;
            if (this.repaymentSelectedYear) {
                await this.fetchRepaymentBalanceId();
            }
        },
        async selectRepaymentYear(year) {
            this.repaymentSelectedYear = year;
            this.repaymentYearDropdownOpen = false;
            if (this.repaymentSelectedMonth) {
                await this.fetchRepaymentBalanceId();
            }
        },
        async fetchRepaymentBalanceId() {
            if (!this.repaymentSelectedMonth || !this.repaymentSelectedYear) return;
            
            try {
                const response = await fetch(`/finance/balance/find-by-period?month=${this.repaymentSelectedMonth}&year=${this.repaymentSelectedYear}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                
                if (data.success && data.balance) {
                    this.repaymentSelectedBalanceId = data.balance.id;
                    this.repaymentBalanceTransfer = data.balance.transfer_balance;
                    this.repaymentBalanceCash = data.balance.cash_balance;
                } else {
                    // Balance not found - set to 0, no error message
                    this.repaymentSelectedBalanceId = null;
                    this.repaymentBalanceTransfer = 0;
                    this.repaymentBalanceCash = 0;
                }
            } catch (error) {
                console.error('Error fetching balance:', error);
                this.repaymentSelectedBalanceId = null;
                this.repaymentBalanceTransfer = 0;
                this.repaymentBalanceCash = 0;
            }
        },
        async startRepaymentWebcam() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Webcam tidak didukung di browser ini. Gunakan browser modern seperti Chrome atau Firefox.');
                return;
            }
            
            const isSecure = window.location.protocol === 'https:' || 
                           window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1';
            
            if (!isSecure) {
                alert('WEBCAM HARUS PAKAI HTTPS! Akses dengan: https://berkah-production.test');
                return;
            }
            
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                this.$refs.repaymentVideo.srcObject = this.stream;
                this.showWebcam = true;
            } catch (err) {
                console.error('Webcam error:', err);
                let errorMsg = 'Tidak dapat mengakses webcam. ';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMsg += 'Permission ditolak!';
                } else if (err.name === 'NotFoundError') {
                    errorMsg += 'Kamera tidak ditemukan!';
                } else {
                    errorMsg += err.message;
                }
                alert(errorMsg);
            }
        },
        async toggleRepaymentCamera() {
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.isMirrored = this.facingMode === 'user';
            
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: this.facingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                this.$refs.repaymentVideo.srcObject = this.stream;
            } catch (err) {
                alert('Gagal mengganti kamera. Error: ' + err.message);
            }
        },
        stopRepaymentWebcam() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.showWebcam = false;
        },
        captureRepaymentPhoto() {
            const video = this.$refs.repaymentVideo;
            const canvas = this.$refs.repaymentCanvas;
            const context = canvas.getContext('2d');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            if (this.isMirrored) {
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
            }
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                const fileInput = document.querySelector('input[name=repayment_proof_image]');
                fileInput.files = dataTransfer.files;
                this.imagePreview = canvas.toDataURL('image/jpeg');
                this.fileName = file.name;
                this.stopRepaymentWebcam();
            }, 'image/jpeg', 0.95);
        }
    }"
    @open-repayment-modal.window="
        showRepaymentModal = true;
        repaymentLoanId = $event.detail.id;
        repaymentLoanCode = $event.detail.loan_balance_period;
        repaymentLoanAmount = $event.detail.amount;
        repaymentRemainingAmount = $event.detail.remaining_amount;
        repaymentPaidDate = new Date().toISOString().split('T')[0];
        repaymentPaymentMethod = '';
        repaymentAmount = '';
        repaymentNotes = '';
        repaymentErrors = {};
        imagePreview = null;
        fileName = '';
        repaymentSelectedBalanceId = null;
        repaymentSelectedMonth = null;
        repaymentSelectedYear = null;
        repaymentBalanceTransfer = 0;
        repaymentBalanceCash = 0;
    "
    x-show="showRepaymentModal"
    @keydown.escape.window="showRepaymentModal = false; stopRepaymentWebcam()"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;">
        
        {{-- Background Overlay --}}
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
        
        {{-- Modal Panel --}}
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.away="showRepaymentModal = false; stopRepaymentWebcam()" 
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Loan Repayment</h3>
                    <button @click="showRepaymentModal = false; stopRepaymentWebcam()" type="button"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                        ✕
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    {{-- Balance Period Selector (Always visible) --}}
                    <div class="mb-6 p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                        <label class="block text-sm font-semibold text-gray-900 mb-3">
                            Select Balance Period <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Month Selector --}}
                            <div class="relative">
                                <button type="button" @click="repaymentMonthDropdownOpen = !repaymentMonthDropdownOpen"
                                    class="w-full flex justify-between items-center rounded-lg border-2 border-primary/40 bg-white px-4 py-2.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary transition-all hover:border-primary">
                                    <span x-text="repaymentSelectedMonthName || 'Select Month'"
                                        :class="!repaymentSelectedMonthName ? 'text-gray-400' : 'text-gray-900'"></span>
                                    <svg class="w-4 h-4 text-primary transition-transform" :class="repaymentMonthDropdownOpen && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="repaymentMonthDropdownOpen" @click.away="repaymentMonthDropdownOpen = false" x-cloak
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="fixed z-[100] mt-1 w-[200px] bg-white border-2 border-primary/30 rounded-lg shadow-2xl"
                                    style="left: auto; top: auto;">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="month in repaymentMonths" :key="month.value">
                                            <li @click="selectRepaymentMonth(month.value)"
                                                class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/10 transition-colors"
                                                :class="{ 'bg-primary/20 font-semibold text-primary': repaymentSelectedMonth === month.value }">
                                                <span x-text="month.name"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            {{-- Year Selector --}}
                            <div class="relative">
                                <button type="button" @click="repaymentYearDropdownOpen = !repaymentYearDropdownOpen"
                                    class="w-full flex justify-between items-center rounded-lg border-2 border-primary/40 bg-white px-4 py-2.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary transition-all hover:border-primary">
                                    <span x-text="repaymentSelectedYear || 'Select Year'"
                                        :class="!repaymentSelectedYear ? 'text-gray-400' : 'text-gray-900'"></span>
                                    <svg class="w-4 h-4 text-primary transition-transform" :class="repaymentYearDropdownOpen && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="repaymentYearDropdownOpen" @click.away="repaymentYearDropdownOpen = false" x-cloak
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="fixed z-[100] mt-1 w-[200px] bg-white border-2 border-primary/30 rounded-lg shadow-2xl"
                                    style="left: auto; top: auto;">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="year in repaymentYears" :key="year">
                                            <li @click="selectRepaymentYear(year)"
                                                class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/10 transition-colors"
                                                :class="{ 'bg-primary/20 font-semibold text-primary': repaymentSelectedYear === year }">
                                                <span x-text="year"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-primary font-medium" x-show="repaymentHasBalancePeriod">
                            <span class="font-semibold">Selected:</span> <span x-text="repaymentSelectedMonthName + ' ' + repaymentSelectedYear"></span>
                        </p>
                    </div>

                    {{-- Content shown only after Balance Period is selected --}}
                    <div x-show="repaymentHasBalancePeriod" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100">
                        
                        {{-- 4 Cards: Transfer, Cash, Loan Amount, Remaining --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                            {{-- Transfer Balance --}}
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(repaymentBalanceTransfer).toLocaleString('id-ID')"></p>
                            </div>
                            {{-- Cash Balance --}}
                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(repaymentBalanceCash).toLocaleString('id-ID')"></p>
                            </div>
                            {{-- Loan Amount --}}
                            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-3 border border-indigo-200">
                                <p class="text-xs text-indigo-600 font-medium mb-1">Loan Amount</p>
                                <p class="text-base font-bold text-indigo-900" x-text="'Rp ' + parseInt(repaymentLoanAmount).toLocaleString('id-ID')"></p>
                            </div>
                            {{-- Remaining --}}
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-3 border border-orange-200">
                                <p class="text-xs text-orange-600 font-medium mb-1">Remaining</p>
                                <p class="text-base font-bold text-orange-900" x-text="'Rp ' + parseInt(repaymentRemainingAmount).toLocaleString('id-ID')"></p>
                            </div>
                        </div>

                    <form id="repaymentLoanForm" @submit.prevent="
                        repaymentErrors = {};
                        let hasValidationError = false;

                        // Validate balance period
                        if (!repaymentSelectedBalanceId) {
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: 'Please select a valid balance period first', type: 'error' }
                            }));
                            return;
                        }

                        // Validate payment method
                        if (!repaymentPaymentMethod) {
                            repaymentErrors.payment_method = ['Payment method is required'];
                            hasValidationError = true;
                        }

                        // Validate amount
                        const amountValue = repaymentAmount.replace(/[^0-9]/g, '');
                        if (!amountValue || parseInt(amountValue) < 1) {
                            repaymentErrors.amount = ['Amount is required and must be at least Rp 1'];
                            hasValidationError = true;
                        } else if (parseInt(amountValue) > parseInt(repaymentRemainingAmount)) {
                            repaymentErrors.amount = ['Amount cannot exceed remaining amount (Rp ' + parseInt(repaymentRemainingAmount).toLocaleString('id-ID') + ')'];
                            hasValidationError = true;
                        }

                        if (hasValidationError) {
                            return;
                        }

                        isSubmittingRepayment = true;
                        const formData = new FormData();
                        formData.append('balance_id', repaymentSelectedBalanceId);
                        formData.append('paid_date', repaymentPaidDate);
                        formData.append('payment_method', repaymentPaymentMethod);
                        formData.append('amount', amountValue);
                        formData.append('notes', repaymentNotes);
                        
                        if (imagePreview && fileName) {
                            const fileInput = document.querySelector('input[name=repayment_proof_image]');
                            if (fileInput && fileInput.files[0]) {
                                formData.append('proof_image', fileInput.files[0]);
                            }
                        }
                        
                        fetch(`/finance/loan-capital/${repaymentLoanId}/repayment`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(async res => {
                            const data = await res.json();
                            return { status: res.status, ok: res.ok, data };
                        })
                        .then(({ status, ok, data }) => {
                            if (ok && data.success) {
                                sessionStorage.setItem('toast_message', data.message || 'Repayment recorded successfully!');
                                sessionStorage.setItem('toast_type', 'success');
                                window.location.reload();
                            } else if (status === 422) {
                                isSubmittingRepayment = false;
                                repaymentErrors = data.errors || {};
                            } else {
                                isSubmittingRepayment = false;
                                repaymentErrors = data.errors || {};
                                if (data.message) {
                                    window.dispatchEvent(new CustomEvent('show-toast', {
                                        detail: { message: data.message, type: 'error' }
                                    }));
                                }
                            }
                        })
                        .catch(err => {
                            isSubmittingRepayment = false;
                            console.error('Repayment error:', err);
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: 'Failed to record repayment. Please try again.', type: 'error' }
                            }));
                        });
                    ">
                        <div class="space-y-4">
                            {{-- Paid Date & Payment Method (Row) --}}
                            <div class="grid grid-cols-2 gap-3">
                                {{-- Paid Date (Locked) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Paid Date <span class="text-red-600">*</span>
                                    </label>
                                    <input type="date" x-model="repaymentPaidDate" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>

                                {{-- Payment Method Dropdown --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Method <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <button type="button" @click="repaymentDropdownOpen = !repaymentDropdownOpen"
                                            :class="repaymentErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="repaymentSelectedPaymentOption ? repaymentSelectedPaymentOption.name : 'Select Method'"
                                                :class="!repaymentSelectedPaymentOption ? 'text-gray-400' : 'text-gray-500'"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="repaymentDropdownOpen && 'rotate-180'" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="repaymentDropdownOpen" @click.away="repaymentDropdownOpen = false" x-cloak
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                            <ul class="max-h-60 overflow-y-auto py-1">
                                                <template x-for="option in repaymentPaymentOptions" :key="option.value">
                                                    <li @click="selectRepaymentPayment(option)"
                                                        class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                        :class="{ 'bg-primary/10 font-medium text-primary': repaymentPaymentMethod === option.value }">
                                                        <span x-text="option.name"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                    <template x-if="repaymentErrors.payment_method">
                                        <p class="mt-1 text-xs text-red-600" x-text="repaymentErrors.payment_method[0]"></p>
                                    </template>
                                </div>
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Amount <span class="text-red-600">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium text-sm">Rp</span>
                                    <input type="text" x-model="repaymentAmount"
                                        @input="repaymentAmount = $event.target.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); delete repaymentErrors.amount"
                                        placeholder="0"
                                        :class="repaymentErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full pl-12 pr-4 py-2 text-sm rounded-md border focus:outline-none focus:ring-2 transition-all">
                                </div>
                                <template x-if="repaymentErrors.amount">
                                    <p class="mt-1 text-xs text-red-600" x-text="repaymentErrors.amount[0]"></p>
                                </template>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea x-model="repaymentNotes" rows="3"
                                    placeholder="Optional notes about this repayment..."
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"></textarea>
                            </div>

                            {{-- Proof Image - OPEN CAM ONLY --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proof of Payment</label>
                                
                                {{-- Webcam Section --}}
                                <div x-show="showWebcam" class="mb-3">
                                    <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                        <video x-ref="repaymentVideo" autoplay playsinline 
                                            :class="{ 'scale-x-[-1]': isMirrored }"
                                            class="w-full h-full object-cover"></video>
                                        <canvas x-ref="repaymentCanvas" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="captureRepaymentPhoto()"
                                        class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Capture
                                        </button>
                                        <button type="button" @click="toggleRepaymentCamera()"
                                        class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="stopRepaymentWebcam()"
                                        class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Close
                                        </button>
                                    </div>
                                </div>

                                {{-- Image Preview --}}
                                <div x-show="imagePreview && !showWebcam" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="imagePreview" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="fileName"></p>
                                            <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                        </div>
                                        <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=repayment_proof_image]').value = ''; startRepaymentWebcam()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=repayment_proof_image]').value = ''"
                                            class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera Button --}}
                                <div x-show="!imagePreview && !showWebcam">
                                    <button type="button" @click="startRepaymentWebcam()"
                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera
                                    </button>
                                </div>
                                <input type="file" name="repayment_proof_image" accept="image/*" class="hidden">
                            </div>
                        </div>
                    </form>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button @click="showRepaymentModal = false; stopRepaymentWebcam()" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="repaymentLoanForm"
                        :disabled="isSubmittingRepayment || !repaymentHasBalancePeriod"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <template x-if="isSubmittingRepayment">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="isSubmittingRepayment ? 'Recording...' : 'Record Repayment'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= IMAGE MODAL (OUTSIDE ROOT DIV) ================= --}}
    <div x-data="{ showImageModal: false, selectedImage: '' }"
         @open-image-modal.window="showImageModal = true; selectedImage = $event.detail.url"
         x-show="showImageModal"
         class="fixed inset-0 z-[60]">
        
        {{-- Background Overlay --}}
        <div @click="showImageModal = false; selectedImage = ''" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
        
        {{-- Modal Panel --}}
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.stop class="relative max-w-3xl w-full flex justify-center items-center" style="max-height: calc(100vh - 6rem);">
                <button @click="showImageModal = false; selectedImage = ''" class="absolute -top-10 right-0 text-white hover:text-gray-300 z-10">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img :src="selectedImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" style="max-height: calc(100vh - 10rem);" alt="Loan proof">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('loan-pagination-container', 'loan-section');
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
