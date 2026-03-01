@extends('layouts.app')

@section('title', 'Salary Report')

@section('content')
    <x-nav-locate :items="['Finance', 'Report', 'Salary']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        currentMonth: new Date().getMonth() + 1,
        currentYear: new Date().getFullYear(),
        displayText: '',

        {{-- Lock & Extract state --}}
        currentPeriodLocked: {{ $reportPeriod && $reportPeriod->lock_status === 'locked' ? 'true' : 'false' }},
        extractLoading: false,

        {{-- Data: array of employee groups --}}
        salaryData: [],

        {{-- Stats --}}
        stats: {
            total_employees: 0,
            total_active_employees: 0,
            total_salary: 0,
            balance_transfer: 0,
            balance_cash: 0,
        },

        {{-- Search --}}
        searchQuery: '',

        {{-- Per Page (persisted in localStorage) --}}
        perPage: parseInt(localStorage.getItem('salary_per_page')) || 10,
        currentPage: 1,

        {{-- Expanded rows --}}
        expandedRows: [],

        {{-- Edit Modal --}}
        showEditModal: false,
        editId: null,
        {{-- Extra Salary Modal --}}
        showExtraSalaryModal: false,
        extraSalaryEmployeeId: null,

        {{-- Delete Confirm --}}
        showDeleteConfirm: null,

        init() {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            this.displayText = monthNames[this.currentMonth - 1] + ' ' + this.currentYear;

            this.fetchData();

            // Toast from sessionStorage (for AJAX operations)
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

        {{-- ==================== NAVIGATION ==================== --}}
        navigateMonth(direction) {
            let newMonth = this.currentMonth;
            let newYear = this.currentYear;

            if (direction === 'prev') {
                newMonth--;
                if (newMonth < 1) { newMonth = 12; newYear--; }
            } else if (direction === 'next') {
                newMonth++;
                if (newMonth > 12) { newMonth = 1; newYear++; }
            } else if (direction === 'reset') {
                const now = new Date();
                newMonth = now.getMonth() + 1;
                newYear = now.getFullYear();
            }

            this.currentMonth = newMonth;
            this.currentYear = newYear;

            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            this.displayText = monthNames[newMonth - 1] + ' ' + newYear;

            this.fetchData();
        },

        {{-- ==================== FETCH DATA ==================== --}}
        async fetchData() {
            NProgress.start();
            try {
                const res = await fetch(`{{ route('finance.report.salary') }}?month=${this.currentMonth}&year=${this.currentYear}&ajax=1`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.salaryData = data.data.salaryData || [];
                    this.stats = data.data.stats || this.stats;
                    this.currentPeriodLocked = data.data.periodLocked || false;
                    this.hasExtracted = data.data.hasExtracted || false;
                    this.searchQuery = '';
                    this.currentPage = 1;
                    this.expandedRows = [];
                }
            } catch (e) {
                console.error('Failed to fetch salary data:', e);
            } finally {
                NProgress.done();
            }
        },

        {{-- ==================== EXTRACT ==================== --}}
        async extractData() {
            this.extractLoading = true;
            try {
                const res = await axios.post('{{ url('finance/report/salary/extract') }}', {
                    balance_month: this.currentMonth,
                    balance_year: this.currentYear,
                });
                if (res.data.success) {
                    this.hasExtracted = true;
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: res.data.message, type: 'success' }
                    }));
                    await this.fetchData();
                }
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to extract data';
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: msg, type: 'error' }
                }));
            } finally {
                this.extractLoading = false;
            }
        },

        {{-- ==================== DELETE ==================== --}}
        async deleteExpense(id) {
            try {
                const res = await axios.delete(`{{ url('finance/report/salary') }}/${id}`, { headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Salary report deleted and balance restored!', type: 'success' }
                }));
                this.showDeleteConfirm = null;
                await this.fetchData();
            } catch (e) {
                const msg = e.response?.data?.message || 'Failed to delete';
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: msg, type: 'error' }
                }));
            }
        },

        {{-- ==================== HELPERS ==================== --}}
        formatCurrency(value) {
            if (!value && value !== 0) return 'Rp 0';
            return 'Rp ' + Number(value).toLocaleString('id-ID');
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            // Handle both 'YYYY-MM-DD' and ISO 8601 (from Carbon serialization)
            const clean = dateStr.length === 10 ? dateStr + 'T00:00:00' : dateStr;
            const d = new Date(clean);
            if (isNaN(d.getTime())) return '-';
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: '2-digit' });
        },

        formatDateFull(dateStr) {
            if (!dateStr) return '-';
            const clean = dateStr.length === 10 ? dateStr + 'T00:00:00' : dateStr;
            const d = new Date(clean);
            if (isNaN(d.getTime())) return '-';
            const day = String(d.getDate()).padStart(2, '0');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const mon = months[d.getMonth()];
            const yr = String(d.getFullYear()).slice(-2);
            const hr = String(d.getHours()).padStart(2, '0');
            const mn = String(d.getMinutes()).padStart(2, '0');
            return `${day} ${mon} ${yr} (${hr}:${mn})`;
        },

        salaryTypeLabel(typeName) {
            const labels = {
                'monthly_1x': 'Monthly 1x',
                'monthly_2x': 'Monthly 2x',
                'project_3x': 'Project 3x',
            };
            return labels[typeName] || typeName || '-';
        },

        {{-- ==================== SEARCH & PAGINATION ==================== --}}
        get filteredData() {
            if (!this.searchQuery.trim()) return this.salaryData;
            const q = this.searchQuery.toLowerCase();
            return this.salaryData.filter(item => {
                const name = (item.employee_name || '').toLowerCase();
                const type = this.salaryTypeLabel(item.salary_type).toLowerCase();
                return name.includes(q) || type.includes(q);
            });
        },

        get totalPages() {
            return Math.ceil(this.filteredData.length / this.perPage) || 1;
        },

        get paginatedData() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredData.slice(start, start + this.perPage);
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
                this.expandedRows = [];
            }
        },

        get visiblePages() {
            const pages = [];
            const total = this.totalPages;
            const current = this.currentPage;
            let start = Math.max(current - 2, 1);
            let end = Math.min(start + 4, total);
            if (end - start < 4) start = Math.max(end - 4, 1);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        toggleRow(id) {
            if (this.expandedRows.includes(id)) {
                this.expandedRows = this.expandedRows.filter(r => r !== id);
            } else {
                this.expandedRows = [id];
            }
        },
    }">

        {{-- ==================== HEADER: Lock Status + Date Navigation ==================== --}}
        <div class="flex flex-col sm:flex-row items-center sm:justify-between gap-3 mb-6">
            {{-- Left: Lock Badge + Extract Button --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Lock Status Badge --}}
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg border font-semibold text-sm flex-shrink-0"
                    :class="currentPeriodLocked ? 'bg-red-100 border-red-300 text-red-800' : 'bg-green-100 border-green-300 text-green-800'">
                    <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
                        <template x-if="!currentPeriodLocked">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        </template>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                            :class="currentPeriodLocked ? 'bg-red-500' : 'bg-green-500'"></span>
                    </span>
                    <span x-text="currentPeriodLocked ? 'Locked' : 'Unlocked'"></span>
                    <template x-if="!currentPeriodLocked">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </template>
                    <template x-if="currentPeriodLocked">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </template>
                </div>

                {{-- Extract Data Button --}}
                <template x-if="!currentPeriodLocked">
                    <button type="button" @click="extractData()" :disabled="extractLoading"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg border font-semibold text-sm flex-shrink-0 bg-violet-100 border-violet-300 text-violet-800 hover:bg-violet-200 transition-colors disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                        <template x-if="!extractLoading">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </template>
                        <template x-if="extractLoading">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <span x-text="extractLoading ? 'Extracting...' : 'Extract Data'"></span>
                    </button>
                </template>
            </div>

            {{-- Right: Date Navigation --}}
            <div class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto justify-center sm:justify-end">
                <button type="button" @click="navigateMonth('prev')"
                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div class="px-3 py-2 text-center min-w-[140px]">
                    <span class="text-base font-semibold text-gray-900 whitespace-nowrap" x-text="displayText"></span>
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
        </div>

        {{-- ==================== STATISTICS CARDS ==================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Employee Entries --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Employee Entries</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            <span x-text="stats.total_employees"></span>
                            <span class="text-sm font-normal text-gray-400">/ <span x-text="stats.total_active_employees"></span></span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- BCA Balance --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Transfer Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatCurrency(stats.balance_transfer)"></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Cash Balance --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Cash Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatCurrency(stats.balance_cash)"></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Salary Expense --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Salary Expense</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatCurrency(stats.total_salary)"></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== SALARY EXPENSE TABLE ==================== --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
            {{-- Header: Title + Search + Per Page + Button --}}
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Salary Expense</h2>

                <div class="flex gap-2 items-center xl:min-w-0">
                    {{-- Search Box --}}
                    <div class="flex-1 xl:min-w-[240px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="searchQuery" @input="currentPage = 1" placeholder="Search by Employee or Salary Type..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    {{-- Show Per Page --}}
                    <div x-data="{ open: false }" class="relative flex-shrink-0">
                        <button type="button" @click="open = !open"
                            class="w-15 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors cursor-pointer">
                            <span x-text="perPage"></span>
                            <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
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
                                <template x-for="opt in [5, 10, 15, 20, 25, 50]" :key="opt">
                                    <li @click="perPage = opt; localStorage.setItem('salary_per_page', opt); open = false; currentPage = 1"
                                        class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                        :class="{ 'bg-primary/10 font-medium text-primary': perPage === opt }">
                                        <span x-text="opt"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Primary Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-primary-light text-gray-600">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold rounded-l-lg">Employee Name</th>
                            <th class="py-3 px-4 text-left font-bold">Salary Type</th>
                            <th class="py-3 px-4 text-left font-bold">Salary Total</th>
                            <th class="py-3 px-4 text-left font-bold">Status</th>
                            <th class="py-3 px-4 text-left font-bold">Date</th>
                            <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                        </tr>
                    </thead>
                    <template x-for="(item, idx) in paginatedData" :key="item.employee_salary_id || idx">
                        <tbody>
                                    {{-- PRIMARY ROW --}}
                                    <tr class="hover:bg-gray-50 cursor-pointer border-b border-gray-100"
                                        @click="toggleRow(item.employee_salary_id)">

                                        {{-- Employee Name --}}
                                        <td class="py-3 px-4 text-[12px]">
                                            <span class="font-medium text-gray-900" x-text="item.employee_name"></span>
                                        </td>

                                        {{-- Salary Type --}}
                                        <td class="py-3 px-4 text-[12px]">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700"
                                                x-text="salaryTypeLabel(item.salary_type)"></span>
                                        </td>

                                        {{-- Salary Total --}}
                                        <td class="py-3 px-4 text-[12px] text-gray-900 font-semibold" x-text="formatCurrency(item.total_amount)"></td>

                                        {{-- Status --}}
                                        <td class="py-3 px-4 text-[12px]">
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                    :class="(item.payment_count >= item.expected_payments && !item.has_draft)
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-amber-100 text-amber-700'"
                                                    x-text="(item.payment_count >= item.expected_payments && !item.has_draft) ? 'Completed' : 'Pending'"></span>
                                                <span class="text-[10px] text-gray-400" x-text="'(' + item.payment_count + '/' + item.expected_payments + ')'"></span>
                                            </div>
                                        </td>

                                        {{-- Date (Latest) --}}
                                        <td class="py-3 px-4 text-[12px] text-gray-700" x-text="formatDate(item.latest_date)"></td>

                                        {{-- Action --}}
                                        <td class="py-3 px-4 text-center relative" @click.stop>
                                            {{-- Dropdown Menu --}}
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
                                                        this.dropdownStyle = { position: 'fixed', top: (rect.top - 200) + 'px', left: (rect.right - 160) + 'px', width: '180px' };
                                                    } else {
                                                        this.dropdownStyle = { position: 'fixed', top: (rect.bottom + 8) + 'px', left: (rect.right - 160) + 'px', width: '180px' };
                                                    }
                                                }
                                            }"
                                            @scroll.window="open = false"
                                            x-init="$watch('open', value => {
                                                if (value) {
                                                    const closeOnScroll = () => { open = false; };
                                                    const scrollableContainer = document.querySelector('.overflow-x-auto');
                                                    if (scrollableContainer) {
                                                        scrollableContainer.addEventListener('scroll', closeOnScroll, { once: true, passive: true });
                                                    }
                                                    const mainContent = document.querySelector('main');
                                                    if (mainContent) {
                                                        mainContent.addEventListener('scroll', closeOnScroll, { once: true, passive: true });
                                                    }
                                                    window.addEventListener('scroll', closeOnScroll, { once: true, passive: true });
                                                    window.addEventListener('resize', closeOnScroll, { once: true, passive: true });
                                                }
                                            })">
                                                <button x-ref="button" @click="checkPosition(); open = !open" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    </svg>
                                                </button>
                                                <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle" class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                    <button type="button"
                                                        @click="toggleRow(item.employee_salary_id); open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View Detail
                                                    </button>
                                                    <template x-if="item.salary_type !== 'monthly_1x' && !currentPeriodLocked">
                                                        <button type="button"
                                                            @click="showExtraSalaryModal = true; extraSalaryEmployeeId = item.employee_salary_id; open = false"
                                                            class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                            </svg>
                                                            Extra Salary
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Expand Arrow (Absolute positioned at right edge) --}}
                                            <div class="absolute right-0 top-1/2 -translate-y-1/2 pr-2">
                                                <button type="button" @click="toggleRow(item.employee_salary_id)" class="p-1 hover:bg-gray-100 rounded transition-colors">
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform"
                                                        :class="expandedRows.includes(item.employee_salary_id) && 'rotate-180'"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- SECONDARY (Expandable) ROW --}}
                                    <tr class="border-b border-gray-200">
                                        <td colspan="6" class="p-0">
                                            <div x-show="expandedRows.includes(item.employee_salary_id)"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 max-h-0"
                                                x-transition:enter-end="opacity-100 max-h-[1000px]"
                                                x-transition:leave="transition ease-in duration-200"
                                                x-transition:leave-start="opacity-100 max-h-[1000px]"
                                                x-transition:leave-end="opacity-0 max-h-0"
                                                class="overflow-hidden bg-gray-50">
                                                <div class="bg-white pl-8">
                                                    <table class="w-full">
                                                        <thead class="bg-gray-100">
                                                            <tr>
                                                                <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600 rounded-l-md">No</th>
                                                                <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Payment Method</th>
                                                                <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                                <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof</th>
                                                                <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Status</th>
                                                                <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                                <th class="py-1.5 px-4 text-center text-[10px] font-semibold text-gray-600 rounded-r-md">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="(payment, pIdx) in (item.payments || [])" :key="payment.id || pIdx">
                                                                <tr class="hover:bg-gray-50 border-b border-gray-50">
                                                                    {{-- No --}}
                                                                    <td class="py-1.5 px-4 text-[10px] text-gray-600" x-text="pIdx + 1"></td>

                                                                    {{-- Payment Method --}}
                                                                    <td class="py-1.5 px-4 text-[10px]">
                                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                            :class="payment.payment_method === 'null' || !payment.payment_method ? 'bg-gray-100 text-gray-500' : (payment.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700')"
                                                                            x-text="payment.payment_method === 'null' || !payment.payment_method ? '-' : payment.payment_method.toUpperCase()"></span>
                                                                    </td>

                                                                    {{-- Amount --}}
                                                                    <td class="py-1.5 px-4 text-[10px] text-gray-900 font-semibold" x-text="formatCurrency(payment.amount)"></td>

                                                                    {{-- Proof --}}
                                                                    <td class="py-1.5 px-4 text-[10px]">
                                                                        <template x-if="payment.proof_img">
                                                                            <button @click.stop="$dispatch('open-image-modal', { url: payment.proof_img_url })"
                                                                                class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                                </svg>
                                                                                View
                                                                            </button>
                                                                        </template>
                                                                        <template x-if="!payment.proof_img">
                                                                            <span class="text-[10px] text-gray-400">-</span>
                                                                        </template>
                                                                    </td>

                                                                    {{-- Status (Draft/Fixed) --}}
                                                                    <td class="py-1.5 px-4 text-[10px]">
                                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                            :class="payment.report_status === 'fixed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                                            x-text="payment.report_status === 'fixed' ? 'Fixed' : 'Draft'"></span>
                                                                    </td>

                                                                    {{-- Date --}}
                                                                    <td class="py-1.5 px-4 text-[10px] text-gray-700" x-text="formatDateFull(payment.updated_at)"></td>

                                                                    {{-- Action --}}
                                                                    <td class="py-1.5 px-4 text-center" @click.stop>
                                                                        <template x-if="currentPeriodLocked">
                                                                            <svg class="w-4 h-4 text-red-600 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                                            </svg>
                                                                        </template>
                                                                        <template x-if="!currentPeriodLocked">
                                                                            <div class="relative inline-block text-left" x-data="{
                                                                                open: false,
                                                                                dropdownStyle: {},
                                                                                checkPosition() {
                                                                                    const rect = this.$refs.secBtn.getBoundingClientRect();
                                                                                    const spaceBelow = window.innerHeight - rect.bottom;
                                                                                    if (spaceBelow < 120) {
                                                                                        this.dropdownStyle = { position: 'fixed', top: (rect.top - 90) + 'px', left: (rect.right - 140) + 'px', width: '140px' };
                                                                                    } else {
                                                                                        this.dropdownStyle = { position: 'fixed', top: (rect.bottom + 4) + 'px', left: (rect.right - 140) + 'px', width: '140px' };
                                                                                    }
                                                                                }
                                                                            }" @scroll.window="open = false">
                                                                                <button x-ref="secBtn" @click="checkPosition(); open = !open" type="button"
                                                                                    class="inline-flex items-center justify-center w-6 h-6 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                                    </svg>
                                                                                </button>
                                                                                <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle"
                                                                                    class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                                                    <button type="button"
                                                                                        @click="
                                                                                            editId = payment.id;
                                                                                            showEditModal = true;
                                                                                            open = false;
                                                                                        "
                                                                                        class="w-full text-left px-3 py-1.5 text-[11px] text-blue-600 hover:bg-blue-50 flex items-center gap-1.5">
                                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                                        </svg>
                                                                                        Edit
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        @click="showDeleteConfirm = payment.id; open = false"
                                                                                        class="w-full text-left px-3 py-1.5 text-[11px] text-red-600 hover:bg-red-50 flex items-center gap-1.5">
                                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                                        </svg>
                                                                                        Delete
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                    </td>
                                                                </tr>
                                                            </template>

                                                            {{-- Empty state for payments --}}
                                                            <template x-if="!item.payments || item.payments.length === 0">
                                                                <tr>
                                                                    <td colspan="7" class="py-4 text-center text-[11px] text-gray-400">
                                                                        No salary payments recorded yet.
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                        </tbody>
                    </template>

                    {{-- Empty State --}}
                    <template x-if="filteredData.length === 0">
                        <tbody>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <p class="text-sm">No salary data found for this period.</p>
                                        <template x-if="!currentPeriodLocked">
                                            <p class="text-xs text-gray-400">Click "Extract Data" to load employee salary data.</p>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </template>
                </table>
            </div>

            {{-- Pagination (matching Material page style) --}}
            <div class="mt-4 flex flex-col items-center gap-3">
                {{-- Info Text --}}
                <div class="text-sm text-gray-600">
                    Showing <span x-text="((currentPage - 1) * perPage) + 1"></span>
                    to <span x-text="Math.min(currentPage * perPage, filteredData.length)"></span>
                    of <span x-text="filteredData.length"></span> entries
                </div>

                {{-- Pagination Navigation --}}
                <div class="flex items-center gap-1">
                    {{-- Previous Button --}}
                    <template x-if="currentPage === 1">
                        <button disabled class="w-9 h-9 flex items-center justify-center rounded-md text-gray-400 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </template>
                    <template x-if="currentPage > 1">
                        <button @click="goToPage(currentPage - 1)" class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </template>

                    {{-- First page + ellipsis --}}
                    <template x-if="visiblePages.length > 0 && visiblePages[0] > 1">
                        <button @click="goToPage(1)" class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition text-sm cursor-pointer">1</button>
                    </template>
                    <template x-if="visiblePages.length > 0 && visiblePages[0] > 2">
                        <span class="px-2 text-gray-400 text-sm">...</span>
                    </template>

                    {{-- Page Numbers --}}
                    <template x-for="page in visiblePages" :key="page">
                        <button @click="goToPage(page)"
                            class="w-9 h-9 flex items-center justify-center rounded-md text-sm cursor-pointer"
                            :class="page === currentPage
                                ? 'bg-primary text-white font-medium'
                                : 'bg-white text-gray-600 hover:bg-gray-100 transition'"
                            x-text="page"></button>
                    </template>

                    {{-- Last page + ellipsis --}}
                    <template x-if="visiblePages.length > 0 && visiblePages[visiblePages.length - 1] < totalPages - 1">
                        <span class="px-2 text-gray-400 text-sm">...</span>
                    </template>
                    <template x-if="visiblePages.length > 0 && visiblePages[visiblePages.length - 1] < totalPages">
                        <button @click="goToPage(totalPages)" class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition text-sm cursor-pointer" x-text="totalPages"></button>
                    </template>

                    {{-- Next Button --}}
                    <template x-if="currentPage < totalPages">
                        <button @click="goToPage(currentPage + 1)" class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </template>
                    <template x-if="currentPage >= totalPages">
                        <button disabled class="w-9 h-9 flex items-center justify-center rounded-md text-gray-400 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- ==================== EDIT SALARY MODAL ==================== --}}
        <div x-show="showEditModal" x-cloak
            @keydown.escape.window="if (showEditModal) { showEditModal = false; editStopWebcam(); }"
            x-data="{
                {{-- Form fields --}}
                editPaymentMethod: '',
                editPaymentMethodDropdownOpen: false,
                editAmount: '',
                editNotes: '',
                isSubmitting: false,
                editErrors: {},

                {{-- Read-only data --}}
                employeeName: '',
                salaryType: '',
                salaryDate: '',
                paymentSequence: 1,
                balancePeriodText: '',
                balanceTransfer: 0,
                balanceCash: 0,
                currentProofUrl: null,

                {{-- Webcam & Image state --}}
                showWebcam: false,
                stream: null,
                imagePreview: null,
                fileName: '',
                isMirrored: true,
                facingMode: 'user',

                {{-- Webcam methods --}}
                async editStartWebcam() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: this.facingMode }
                        });
                        this.$refs.editWebcamVideo.srcObject = this.stream;
                        this.showWebcam = true;
                    } catch (e) {
                        console.error('Camera error:', e);
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Unable to access camera. Please check permissions.', type: 'error' }
                        }));
                    }
                },

                editStopWebcam() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                        this.stream = null;
                    }
                    this.showWebcam = false;
                },

                async editToggleCamera() {
                    this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
                    this.isMirrored = this.facingMode === 'user';
                    this.editStopWebcam();
                    await this.editStartWebcam();
                },

                editCapturePhoto() {
                    const video = this.$refs.editWebcamVideo;
                    const canvas = this.$refs.editWebcamCanvas;
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    if (this.isMirrored) {
                        ctx.translate(canvas.width, 0);
                        ctx.scale(-1, 1);
                    }
                    ctx.drawImage(video, 0, 0);
                    canvas.toBlob((blob) => {
                        const file = new File([blob], 'salary_proof_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        const input = document.querySelector('input[name=edit_salary_proof_image]');
                        if (input) input.files = dt.files;
                        this.imagePreview = URL.createObjectURL(blob);
                        this.fileName = file.name;
                        this.editStopWebcam();
                    }, 'image/jpeg', 0.9);
                },

                loadPaymentData() {
                    const targetId = editId;
                    for (const group of salaryData) {
                        const payment = (group.payments || []).find(p => p.id === targetId);
                        if (payment) {
                            this.editPaymentMethod = (payment.payment_method && payment.payment_method !== 'null') ? payment.payment_method : '';
                            this.editAmount = payment.amount > 0 ? Number(payment.amount).toLocaleString('id-ID').replace(/,/g, '.') : '';
                            this.editNotes = payment.notes || '';
                            this.salaryDate = payment.salary_date || '';
                            this.paymentSequence = payment.payment_sequence || 1;
                            this.currentProofUrl = payment.proof_img_url || null;
                            this.employeeName = group.employee_name || '';
                            
                            {{-- Format salary type --}}
                            const typeMap = {
                                'monthly_1x': 'Monthly (1x)',
                                'monthly_2x': 'Monthly (2x)',
                                'project_3x': 'Project (3x)',
                            };
                            this.salaryType = typeMap[group.salary_type] || group.salary_type || '';

                            {{-- Balance info from stats --}}
                            this.balanceTransfer = stats.balance_transfer || 0;
                            this.balanceCash = stats.balance_cash || 0;

                            const monthNames = ['January','February','March','April','May','June',
                                               'July','August','September','October','November','December'];
                            this.balancePeriodText = monthNames[currentMonth - 1] + ' ' + currentYear;
                            break;
                        }
                    }
                },
            }"
            x-init="
                $watch('showEditModal', value => {
                    if (value) {
                        editErrors = {};
                        isSubmitting = false;
                        imagePreview = null;
                        fileName = '';
                        showWebcam = false;
                        editPaymentMethodDropdownOpen = false;
                        const inp = document.querySelector('input[name=edit_salary_proof_image]');
                        if (inp) inp.value = '';
                        $nextTick(() => loadPaymentData());
                    } else {
                        editStopWebcam();
                    }
                })
            "
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;">

            <div @click.away="showEditModal = false; editStopWebcam()"
                 class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">

                {{-- Modal Header - Sticky --}}
                <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Salary Payment</h3>
                    <button @click="showEditModal = false; editStopWebcam()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <form id="editSalaryForm" @submit.prevent="
                        editErrors = {};
                        let hasError = false;

                        if (!editPaymentMethod) {
                            editErrors.payment_method = ['Payment method is required'];
                            hasError = true;
                        }
                        const rawAmount = editAmount.replace(/[^0-9]/g, '');
                        if (!rawAmount || parseInt(rawAmount) < 1) {
                            editErrors.amount = ['Amount is required and must be at least Rp 1'];
                            hasError = true;
                        }
                        const proofInput = document.querySelector('input[name=edit_salary_proof_image]');
                        if (!proofInput || !proofInput.files[0]) {
                            editErrors.proof_img = ['Proof of payment is required'];
                            hasError = true;
                        }
                        if (hasError) return;

                        isSubmitting = true;
                        const formData = new FormData();
                        formData.append('payment_method', editPaymentMethod);
                        formData.append('amount', rawAmount);
                        if (editNotes) formData.append('notes', editNotes);
                        if (proofInput && proofInput.files[0]) {
                            formData.append('proof_img', proofInput.files[0]);
                        }

                        fetch(`{{ url('finance/report/salary') }}/${editId}`, {
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
                                showEditModal = false;
                                editStopWebcam();
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: data.message || 'Salary payment updated!', type: 'success' }
                                }));
                                fetchData();
                            } else if (status === 422) {
                                isSubmitting = false;
                                editErrors = data.errors || {};
                            } else {
                                isSubmitting = false;
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: data.message || 'Failed to update.', type: 'error' }
                                }));
                            }
                        })
                        .catch(err => {
                            isSubmitting = false;
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: 'Request failed. Please try again.', type: 'error' }
                            }));
                        });
                    ">
                        <div class="space-y-4">

                            {{-- Balance Period (Locked) --}}
                            <div class="mb-2 p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                <label class="block text-sm font-semibold text-gray-900 mb-3">
                                    Balance Period <span class="text-red-600">*</span>
                                </label>
                                <input type="text" :value="balancePeriodText || 'Loading...'" readonly
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                <p class="mt-2 text-xs text-primary font-medium" x-show="balancePeriodText">
                                    <span class="font-semibold">Selected:</span> <span x-text="balancePeriodText"></span>
                                </p>
                            </div>

                            {{-- Balance Cards --}}
                            <div class="grid grid-cols-2 gap-3 mb-2">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                    <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                    <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(balanceTransfer || 0).toLocaleString('id-ID')"></p>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                    <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                    <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(balanceCash || 0).toLocaleString('id-ID')"></p>
                                </div>
                            </div>

                            {{-- Employee Name & Salary Type (Locked) --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Employee Name <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="employeeName" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Salary Type <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="salaryType" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>
                            </div>

                            {{-- Salary Date & Payment Sequence (Locked) --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Salary Date <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="salaryDate" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Sequence <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="paymentSequence" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>
                            </div>

                            {{-- Payment Method & Amount --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Payment Method --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Method <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <button type="button" @click="editPaymentMethodDropdownOpen = !editPaymentMethodDropdownOpen"
                                            :class="editErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors cursor-pointer">
                                            <span x-text="editPaymentMethod ? (editPaymentMethod === 'cash' ? 'Cash' : 'Transfer') : 'Select Payment Method'"
                                                :class="!editPaymentMethod && 'text-gray-400'"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="editPaymentMethodDropdownOpen && 'rotate-180'"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="editPaymentMethodDropdownOpen" @click.away="editPaymentMethodDropdownOpen = false" x-cloak
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                            <button type="button" @click="editPaymentMethod = 'transfer'; editPaymentMethodDropdownOpen = false"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors cursor-pointer"
                                                :class="editPaymentMethod === 'transfer' && 'bg-primary/10 font-medium text-primary'">
                                                Transfer
                                            </button>
                                            <button type="button" @click="editPaymentMethod = 'cash'; editPaymentMethodDropdownOpen = false"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors cursor-pointer"
                                                :class="editPaymentMethod === 'cash' && 'bg-primary/10 font-medium text-primary'">
                                                Cash
                                            </button>
                                        </div>
                                    </div>
                                    <template x-if="editErrors.payment_method">
                                        <p class="mt-1 text-xs text-red-600" x-text="editErrors.payment_method[0]"></p>
                                    </template>
                                </div>

                                {{-- Amount --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Amount <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                                        <input type="text" x-model="editAmount"
                                            @input="editAmount = editAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                            :class="editErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md border pl-12 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                            placeholder="0">
                                    </div>
                                    <template x-if="editErrors.amount">
                                        <p class="mt-1 text-xs text-red-600" x-text="editErrors.amount[0]"></p>
                                    </template>
                                </div>
                            </div>

                            {{-- Proof of Payment --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Proof of Payment <span class="text-red-600">*</span>
                                </label>

                                {{-- Webcam Section --}}
                                <div x-show="showWebcam" class="mb-3">
                                    <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                        <video x-ref="editWebcamVideo" autoplay playsinline
                                            :class="{ 'scale-x-[-1]': isMirrored }"
                                            class="w-full h-full object-cover"></video>
                                        <canvas x-ref="editWebcamCanvas" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="editCapturePhoto()"
                                            class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Capture
                                        </button>
                                        <button type="button" @click="editToggleCamera()"
                                            class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="editStopWebcam()"
                                            class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Close
                                        </button>
                                    </div>
                                </div>

                                {{-- Image Preview (Existing or Captured) --}}
                                <div x-show="(imagePreview || currentProofUrl) && !showWebcam" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="imagePreview || currentProofUrl" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="fileName || (currentProofUrl ? 'Current proof image' : '')"></p>
                                            <p class="text-xs text-green-600 mt-1" x-text="imagePreview ? '&#10003; New image ready to upload' : '&#10003; Existing proof image'"></p>
                                        </div>
                                        <button type="button" @click="currentProofUrl = null; imagePreview = null; fileName = ''; document.querySelector('input[name=edit_salary_proof_image]').value = ''; editStartWebcam()"
                                            class="text-blue-600 hover:text-blue-700 p-1 cursor-pointer" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="currentProofUrl = null; imagePreview = null; fileName = ''; document.querySelector('input[name=edit_salary_proof_image]').value = ''"
                                            class="text-red-600 hover:text-red-700 p-1 cursor-pointer" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera Button --}}
                                <div x-show="!imagePreview && !showWebcam && !currentProofUrl">
                                    <button type="button" @click="editStartWebcam()"
                                        class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700 cursor-pointer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera
                                    </button>
                                </div>
                                <input type="file" name="edit_salary_proof_image" accept="image/*" class="hidden">
                                <template x-if="editErrors.proof_img">
                                    <p class="mt-1 text-xs text-red-600" x-text="editErrors.proof_img[0]"></p>
                                </template>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea x-model="editNotes" rows="3"
                                    class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                    placeholder="Optional notes..."></textarea>
                            </div>

                            {{-- Status Info --}}
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-center gap-2 text-sm text-amber-700">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Saving will change the status from <strong>Draft</strong> to <strong>Fixed</strong>.</span>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                {{-- Modal Footer - Sticky --}}
                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                    <button type="button" @click="showEditModal = false; editStopWebcam()"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" form="editSalaryForm" :disabled="isSubmitting"
                        :class="isSubmitting ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark cursor-pointer'"
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <template x-if="isSubmitting">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="isSubmitting ? 'Updating...' : 'Update Payment'"></span>
                    </button>
                </div>
            </div>
        </div>
        {{-- End Edit Salary Modal --}}

        {{-- ==================== DELETE CONFIRMATION MODAL ==================== --}}
        <div x-show="showDeleteConfirm !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50" @click="showDeleteConfirm = null"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 p-6" @click.stop>
                <div class="text-center">
                    <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Salary Payment</h3>
                    <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete this salary payment? Balance will be restored.</p>
                    <div class="flex gap-3">
                        <button type="button" @click="showDeleteConfirm = null"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" @click="deleteExpense(showDeleteConfirm)"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors cursor-pointer">
                            Yes, Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ================= IMAGE MODAL (OUTSIDE ROOT DIV) ================= --}}
    <div x-data="{ showImageModal: false, selectedImage: '' }"
         @open-image-modal.window="showImageModal = true; selectedImage = $event.detail.url"
         x-show="showImageModal"
         x-cloak
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
                <img :src="selectedImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" style="max-height: calc(100vh - 10rem);" alt="Salary proof">
            </div>
        </div>
    </div>
@endsection
