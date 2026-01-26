@extends('layouts.app')

@section('title', 'Order List Report')

@section('content')
    <x-nav-locate :items="['Finance', 'Report', 'Order List']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        currentMonth: {{ $month }},
        currentYear: {{ $year }},
        displayText: '{{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }}',
        searchTShirt: '',
        searchMakloon: '',
        searchHoodie: '',
        searchPants: '',
        showDeleteConfirm: null,
        showLockConfirm: null,
        lockAction: null,
        
        init() {
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
            
            this.loadMonth(newMonth, newYear);
        },
        
        loadMonth(month, year) {
            this.currentMonth = month;
            this.currentYear = year;
            
            const params = new URLSearchParams(window.location.search);
            params.set('month', month);
            params.set('year', year);
            
            const url = '{{ route('finance.report.order-list') }}?' + params.toString();
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
                
                const newStats = doc.getElementById('stats-section');
                const newTables = doc.getElementById('tables-section');
                
                if (newStats) document.getElementById('stats-section').innerHTML = newStats.innerHTML;
                if (newTables) document.getElementById('tables-section').innerHTML = newTables.innerHTML;
                
                NProgress.done();
            })
            .catch(error => {
                console.error('Error:', error);
                NProgress.done();
            });
        },
        
        matchesSearch(row, searchKey) {
            const query = this[searchKey].toLowerCase();
            if (!query || query.trim() === '') return true;
            const invoice = (row.getAttribute('data-invoice') || '').toLowerCase();
            const customer = (row.getAttribute('data-customer') || '').toLowerCase();
            const product = (row.getAttribute('data-product') || '').toLowerCase();
            return invoice.includes(query) || customer.includes(query) || product.includes(query);
        }
    }">

        {{-- Date Navigation (Right Aligned) - 100% mirip Internal Transfer --}}
        <div class="flex items-center justify-end gap-3 mb-6">
            <button type="button" @click="navigateMonth('prev')" 
                class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="px-4 py-2 text-center min-w-[150px]">
                <span class="text-base font-semibold text-gray-900 whitespace-nowrap" x-text="displayText">
                    {{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
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

        {{-- Statistics Cards --}}
        <div id="stats-section" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($stats['total_bill'], 0, ',', '.') }}</p>
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
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($stats['remaining_due'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- T-Shirt --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">T-Shirt</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['tshirt_count']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Makloon --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Makloon</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['makloon_count']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Hoodie / Polo / Jersey --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Hoodie / Polo / Etc</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['hoodie_count']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Pants --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Pants</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['pants_count']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tables Section --}}
        <div id="tables-section">
        {{-- T-SHIRT TABLE --}}
        @include('pages.finance.report.partials.product-table', [
            'productType' => 't-shirt',
            'title' => 'T-Shirt Orders',
            'reports' => $reportsByType['t-shirt'],
            'searchKey' => 'searchTShirt'
        ])

        {{-- MAKLOON TABLE --}}
        @include('pages.finance.report.partials.product-table', [
            'productType' => 'makloon',
            'title' => 'Makloon Orders',
            'reports' => $reportsByType['makloon'],
            'searchKey' => 'searchMakloon'
        ])

        {{-- HOODIE/POLO/JERSEY TABLE --}}
        @include('pages.finance.report.partials.product-table', [
            'productType' => 'hoodie_polo_jersey',
            'title' => 'Hoodie / Polo / Jersey Orders',
            'reports' => $reportsByType['hoodie_polo_jersey'],
            'searchKey' => 'searchHoodie'
        ])

        {{-- PANTS TABLE --}}
        @include('pages.finance.report.partials.product-table', [
            'productType' => 'pants',
            'title' => 'Pants Orders',
            'reports' => $reportsByType['pants'],
            'searchKey' => 'searchPants'
        ])
        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="showDeleteConfirm !== null" x-cloak class="fixed inset-0 z-50">
            {{-- Background Overlay --}}
            <div x-show="showDeleteConfirm !== null" @click="showDeleteConfirm = null"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showDeleteConfirm = null"
                    class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                    {{-- Icon --}}
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>

                    {{-- Title --}}
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                        Remove Report?
                    </h3>

                    {{-- Message --}}
                    <p class="text-sm text-gray-600 text-center mb-6">
                        Are you sure you want to remove this report? This will update the order's report status back to unreported.
                    </p>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <button type="button" @click="showDeleteConfirm = null"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <form :action="`{{ url('finance/report/order-list') }}/${showDeleteConfirm}`"
                            method="POST" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                                Yes, Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lock/Unlock Confirmation Modal --}}
        <div x-show="showLockConfirm !== null" x-cloak class="fixed inset-0 z-50">
            {{-- Background Overlay --}}
            <div x-show="showLockConfirm !== null" @click="showLockConfirm = null; lockAction = null"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showLockConfirm = null; lockAction = null"
                    class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                    {{-- Icon --}}
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4"
                        :class="lockAction === 'locked' ? 'bg-orange-100' : 'bg-purple-100'">
                        <svg class="w-6 h-6" :class="lockAction === 'locked' ? 'text-orange-600' : 'text-purple-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <template x-if="lockAction === 'locked'">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </template>
                            <template x-if="lockAction === 'draft'">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </template>
                        </svg>
                    </div>

                    {{-- Title --}}
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                        <span x-text="lockAction === 'locked' ? 'Unlock Report?' : 'Lock Report?'"></span>
                    </h3>

                    {{-- Message --}}
                    <p class="text-sm text-gray-600 text-center mb-6">
                        <template x-if="lockAction === 'locked'">
                            <span>Are you sure you want to unlock this report? It will be moved to Draft status.</span>
                        </template>
                        <template x-if="lockAction === 'draft'">
                            <span>Are you sure you want to lock this report? Once locked, it cannot be edited or deleted.</span>
                        </template>
                    </p>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <button type="button" @click="showLockConfirm = null; lockAction = null"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <form :action="`{{ url('finance/report/order-list') }}/${showLockConfirm}/toggle-lock`"
                            method="POST" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="w-full px-4 py-2 rounded-md text-sm font-medium text-white transition-colors"
                                :class="lockAction === 'locked' ? 'bg-orange-600 hover:bg-orange-700' : 'bg-purple-600 hover:bg-purple-700'">
                                <span x-text="lockAction === 'locked' ? 'Yes, Unlock' : 'Yes, Lock'"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

@endsection
