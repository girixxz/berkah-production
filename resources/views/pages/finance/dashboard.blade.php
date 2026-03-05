@extends('layouts.app')

@section('title', 'Finance Dashboard')

@section('content')
    <x-nav-locate :items="['Finance', 'Dashboard']" />

    <div x-data="{
        currentMonth: {{ $currentDate->month }},
        currentYear: {{ $currentDate->year }},
        displayText: '{{ $currentDate->format('F Y') }}',

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

            this.loadMonth(newMonth, newYear);
        },

        loadMonth(month, year) {
            this.currentMonth = month;
            this.currentYear = year;

            const params = new URLSearchParams(window.location.search);
            params.set('month', month);
            params.set('year', year);
            params.delete('page');

            const url = '{{ route('finance.dashboard') }}?' + params.toString();
            window.history.pushState({}, '', url);

            const monthNames = ['January','February','March','April','May','June',
                               'July','August','September','October','November','December'];
            this.displayText = monthNames[month - 1] + ' ' + year;

            NProgress.start();
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newCards = doc.getElementById('cards-section');
                const newTable = doc.getElementById('table-section');

                if (newCards) document.getElementById('cards-section').innerHTML = newCards.innerHTML;
                if (newTable) document.getElementById('table-section').innerHTML = newTable.innerHTML;

                // Extract chart data from hidden element in AJAX response
                const chartDataEl = doc.getElementById('chart-data');
                if (chartDataEl) {
                    const chartData = JSON.parse(chartDataEl.textContent);
                    window.dispatchEvent(new CustomEvent('dashboard-chart-update', {
                        detail: { categories: chartData.categories, values: chartData.values }
                    }));
                }

                NProgress.done();
            })
            .catch(error => { console.error('Error:', error); NProgress.done(); });
        },

    }">

        {{-- Date Navigation --}}
        <div class="flex items-center justify-end gap-2 mb-6">
            <button type="button" @click="navigateMonth('prev')"
                class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="px-3 py-2 text-center min-w-[140px]">
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

        {{-- Hidden chart data for AJAX updates --}}
        <script id="chart-data" type="application/json">@json(['categories' => $categorySummary->pluck('label')->values(), 'values' => $categorySummary->pluck('total_qty')->values()])</script>

        {{-- ==================== SECTION: STATS CARDS ==================== --}}
        <div id="cards-section">
            {{-- Row 1: Main Financial Overview --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                {{-- Total Bill --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Bill</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($totalBill, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">From reported orders</p>
                        </div>
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Expense --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Expense</p>
                            <p class="text-2xl font-bold text-orange-600 mt-1">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">Material, Partner, Operational, Salary</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Net Income --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Net Profit</p>
                            <p class="text-2xl font-bold {{ $netIncome >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">Rp {{ number_format($netIncome, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">Income - Expense</p>
                        </div>
                        <div class="w-12 h-12 {{ $netIncome >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 {{ $netIncome >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 2: Income Details --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                {{-- Total Income --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Income</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">Paid from reported orders</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Remaining --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Remaining</p>
                            <p class="text-2xl font-bold text-red-600 mt-1">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">Unpaid from reported orders</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Saving Rate --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Saving Rate</p>
                            <p class="text-2xl font-bold {{ $savingRate >= 0 ? 'text-primary' : 'text-red-600' }} mt-1">{{ number_format($savingRate, 1) }}%</p>
                            <p class="text-[10px] text-gray-400 mt-1">(Net Income / Total Bill) × 100%</p>
                        </div>
                        <div class="w-12 h-12 {{ $savingRate >= 0 ? 'bg-primary/10' : 'bg-red-100' }} rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 {{ $savingRate >= 0 ? 'text-primary' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 3: Balance Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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

                {{-- Transfer Balance --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Transfer Balance</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">Rp {{ number_format($transferBalance, 0, ',', '.') }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Cash Balance --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Cash Balance</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">Rp {{ number_format($cashBalance, 0, ',', '.') }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== SECTION: TABLE + CHART (2-col grid) ==================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- LEFT: Product Category Summary Table --}}
        <div id="table-section" class="bg-white border border-gray-200 rounded-lg p-5 flex flex-col">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Product Category Summary</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Based on reported orders this period</p>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto flex-1 flex flex-col">
                <table class="min-w-full text-sm flex-1">
                    <thead class="bg-primary-light text-gray-600">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold rounded-l-lg w-16">No</th>
                            <th class="py-3 px-4 text-left font-bold">Categories</th>
                            <th class="py-3 px-4 text-left font-bold">QTY</th>
                            <th class="py-3 px-4 text-left font-bold rounded-r-lg">Total Bill</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorySummary as $index => $category)
                            <tr class="hover:bg-gray-50 border-b border-gray-100">
                                <td class="py-5 px-4 text-[12px] text-gray-600">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-5 px-4 text-[12px] font-medium text-gray-900">
                                    {{ $category->label }}
                                </td>
                                <td class="py-5 px-4 text-[12px] text-gray-900">
                                    {{ number_format($category->total_qty, 0, ',', '.') }} pcs
                                </td>
                                <td class="py-5 px-4 text-[12px] text-gray-900 font-semibold">
                                    Rp {{ number_format($category->total_bill, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach

                        {{-- Totals Row --}}
                        <tr class="bg-gray-50">
                            <td class="py-5 px-4 text-[12px] font-bold text-gray-900 text-center" colspan="3">Total</td>
                            <td class="py-5 px-4 text-[12px] font-bold text-gray-900">
                                Rp {{ number_format($categorySummary->sum('total_bill'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        {{-- RIGHT: Trend Order Product Category Chart --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4 md:p-6" x-data="{
            chart: null,
            init() {
                const categories = @js($categorySummary->pluck('label')->values());
                const values = @js($categorySummary->pluck('total_qty')->values());

                const options = {
                    series: [{
                        name: 'QTY',
                        data: values
                    }],
                    chart: {
                        type: 'bar',
                        height: 340,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            borderRadius: 4,
                            distributed: true,
                            columnWidth: '40%',
                            dataLabels: {
                                position: 'top'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        offsetY: -20,
                        style: {
                            fontSize: '11px',
                            colors: ['#333']
                        },
                        formatter: (val) => Math.floor(val)
                    },
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: { fontSize: '11px' }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: { fontSize: '11px' },
                            formatter: (val) => Math.floor(val)
                        },
                        min: 0,
                        forceNiceScale: true,
                        tickAmount: 5
                    },
                    grid: {
                        borderColor: '#f1f1f1',
                        strokeDashArray: 4,
                        yaxis: { lines: { show: true } },
                        xaxis: { lines: { show: false } }
                    },
                    legend: { show: false },
                    tooltip: {
                        y: {
                            formatter: (val) => val + ' pcs'
                        }
                    }
                };

                this.chart = new ApexCharts(this.$refs.chartContainer, options);
                this.chart.render();

                // Listen for month nav updates
                window.addEventListener('dashboard-chart-update', (e) => {
                    this.chart.updateOptions({
                        xaxis: { categories: e.detail.categories },
                        series: [{ data: e.detail.values }]
                    });
                });
            }
        }">
            <div class="mb-2">
                <h3 class="text-base font-semibold text-gray-900">Trend Order Product Category</h3>
                <p class="text-xs text-gray-500 mt-0.5">QTY per category this period</p>
            </div>
            <div x-ref="chartContainer"></div>
        </div>

        </div>{{-- End 2-col grid --}}
    </div>
@endsection
