@extends('layouts.app')

@section('title', 'Loan Repayment History')

@section('content')
    <x-nav-locate :items="['Finance', 'Loan Capital', 'Repayment History']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        searchQuery: '{{ request('search') }}',
        paymentMethodFilter: '{{ $paymentMethodFilter }}',
        currentMonth: {{ $currentDate->month }},
        currentYear: {{ $currentDate->year }},
        displayText: '{{ $currentDate->format('F Y') }}',
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
            
            const url = '{{ route('finance.loan-capital.repayment-history') }}?' + params.toString();
            window.history.pushState({}, '', url);
            
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
                
                const newSection = doc.getElementById('repayment-section');
                if (newSection) {
                    document.getElementById('repayment-section').innerHTML = newSection.innerHTML;
                    setupPagination('repayment-pagination-container', 'repayment-section');
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
            params.set('payment_method', this.paymentMethodFilter);
            if (this.searchQuery) params.set('search', this.searchQuery);
            
            const perPageValue = this.getPerPageValue();
            if (perPageValue) params.set('per_page', perPageValue);
            
            const url = '{{ route('finance.loan-capital.repayment-history') }}?' + params.toString();
            window.history.pushState({}, '', url);
            
            NProgress.start();
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newSection = doc.getElementById('repayment-section');
                if (newSection) {
                    document.getElementById('repayment-section').innerHTML = newSection.innerHTML;
                    setupPagination('repayment-pagination-container', 'repayment-section');
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

        {{-- ================= SECTION 2: TABLE ================= --}}
        <div class="bg-white border border-gray-200 rounded-lg" id="repayment-section">
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    {{-- Title (Left) - Smaller on mobile --}}
                    <h2 class="text-base xl:text-xl font-bold text-gray-900">Loan Repayment History</h2>
                    
                    {{-- Back Button (Right) - Icon only on mobile, with text on desktop --}}
                    <a href="{{ route('finance.loan-capital') }}" 
                        class="inline-flex items-center gap-2 px-3 xl:px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700 hidden xl:inline">Back to Loan</span>
                    </a>
                </div>
            </div>

            {{-- Filters & Actions --}}
            <div class="p-5 border-b border-gray-200">
                <div class="flex flex-col xl:flex-row xl:items-center gap-4">
                    {{-- Payment Method Filter Buttons --}}
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="paymentMethodFilter = 'all'; applyFilter();"
                            :class="paymentMethodFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            All
                        </button>
                        <button type="button" @click="paymentMethodFilter = 'transfer'; applyFilter();"
                            :class="paymentMethodFilter === 'transfer' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-blue-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Transfer
                        </button>
                        <button type="button" @click="paymentMethodFilter = 'cash'; applyFilter();"
                            :class="paymentMethodFilter === 'cash' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-green-50'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Cash
                        </button>
                    </div>

                    {{-- Right: Search & Show Per Page --}}
                    <div class="flex gap-2 items-center xl:flex-1 xl:ml-auto">
                        {{-- Search --}}
                        <div class="flex-1 xl:min-w-[180px]">
                            <div class="relative">
                                <input type="text" x-model="searchQuery" @input.debounce.300ms="applyFilter()"
                                    placeholder="Search by Trx or Note..."
                                    class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        {{-- Show Per Page --}}
                        <div x-data="{
                            open: false,
                            perPage: {{ $perPage }},
                            options: [
                                { value: 10, label: '10' },
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
                                const url = '{{ route('finance.loan-capital.repayment-history') }}?' + params.toString();
                                window.history.pushState({}, '', url);
                                
                                NProgress.start();
                                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                    .then(response => response.text())
                                    .then(html => {
                                        const parser = new DOMParser();
                                        const doc = parser.parseFromString(html, 'text/html');
                                        const newSection = doc.getElementById('repayment-section');
                                        if (newSection) {
                                            document.getElementById('repayment-section').innerHTML = newSection.innerHTML;
                                            setupPagination('repayment-pagination-container', 'repayment-section');
                                        }
                                        NProgress.done();
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        NProgress.done();
                                    });
                            }
                        }" class="relative flex-shrink-0">
                            <button @click="open = !open"
                                class="flex items-center gap-1.5 px-3 py-2 text-sm border border-gray-300 rounded-md bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
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
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto px-5 pb-5">
                <table class="w-full text-sm">
                    <thead class="bg-primary-light text-gray-600">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold rounded-l-lg">Paid Date</th>
                            <th class="py-3 px-4 text-left font-bold">No. Trx (Loan)</th>
                            <th class="py-3 px-4 text-left font-bold">Payment Method</th>
                            <th class="py-3 px-4 text-left font-bold">Amount</th>
                            <th class="py-3 px-4 text-left font-bold">Note</th>
                            <th class="py-3 px-4 text-left font-bold rounded-r-lg">Attachment</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($repayments as $repayment)
                            <tr>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-500">
                                    {{ $repayment->paid_date->format('d M Y') }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-gray-900">
                                    {{ $repayment->loanCapital->loan_code }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                        {{ $repayment->payment_method === 'transfer' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                        {{ ucfirst($repayment->payment_method) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-900">
                                    Rp {{ number_format($repayment->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-gray-500">
                                    {{ Str::limit($repayment->notes ?? '-', 30) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-left">
                                    @if ($repayment->proof_img)
                                        <button @click="$dispatch('open-image-modal', { url: '{{ route('finance.loan-capital.serve-repayment-image', $repayment->id) }}' })"
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="font-medium">No repayment records found</p>
                                        <p class="text-sm text-gray-400 mt-1">There are no repayments for this period</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div id="repayment-pagination-container" class="px-6 py-4 border-t border-gray-200">
                <x-custom-pagination :paginator="$repayments" />
            </div>
        </div>
    </div>

    {{-- ================= IMAGE MODAL ================= --}}
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
                <img :src="selectedImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" style="max-height: calc(100vh - 10rem);" alt="Repayment proof">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('repayment-pagination-container', 'repayment-section');
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
                            setupPagination(containerId, sectionId);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        }
    </script>
@endpush
