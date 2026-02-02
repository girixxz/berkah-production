@extends('layouts.app')

@section('title', 'Material Report')

@section('content')
    <x-nav-locate :items="['Finance', 'Report', 'Material']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        currentMonth: {{ $month }},
        currentYear: {{ $year }},
        displayText: '{{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }}',
        searchQuery: '{{ $search }}',
        showPurchaseModal: false,
        showExtraPurchaseModal: false,
        extraPurchaseOrderReportId: null,
        extraPurchaseBalanceId: null,
        extraPurchaseBalanceMonth: null,
        extraPurchaseBalanceYear: null,
        extraPurchaseBalanceTransfer: 0,
        extraPurchaseBalanceCash: 0,
        extraPurchaseOrderData: null,
        extraPurchaseMaterialName: '',
        extraPurchaseMaterialSupplierId: null,
        extraPurchaseMaterialSuppliers: [],
        extraPurchaseSupplierDropdownOpen: false,
        extraPurchasePaymentMethod: '',
        extraPurchasePaymentMethodDropdownOpen: false,
        extraPurchasePurchaseAmount: '',
        extraPurchasePurchaseNotes: '',
        extraPurchaseErrors: {},
        isSubmittingExtraPurchase: false,
        showEditMaterialModal: false,
        editMaterialId: null,
        editMaterialData: null,
        editBalanceId: null,
        editBalanceMonth: null,
        editBalanceYear: null,
        editBalanceTransfer: 0,
        editBalanceCash: 0,
        editOrderReportData: null,
        editPurchaseDate: null,
        editPurchaseType: null,
        editMaterialName: '',
        editMaterialSupplierId: null,
        editMaterialSuppliers: [],
        editSupplierDropdownOpen: false,
        editPaymentMethod: '',
        editPaymentMethodDropdownOpen: false,
        editPurchaseAmount: '',
        editPurchaseNotes: '',
        editProofImage: null,
        editMaterialErrors: {},
        isSubmittingEditMaterial: false,
        purchaseErrors: {},
        isSubmittingPurchase: false,
        stream: null,
        showWebcam: false,
        imagePreview: null,
        fileName: '',
        isMirrored: false,
        facingMode: 'environment',
        showDeleteMaterial: null,
        
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
            
            const url = '{{ route('finance.report.material') }}?' + params.toString();
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
                const newMaterials = doc.getElementById('materials-section');
                
                if (newStats) document.getElementById('stats-section').innerHTML = newStats.innerHTML;
                if (newMaterials) document.getElementById('materials-section').innerHTML = newMaterials.innerHTML;
                
                NProgress.done();
            })
            .catch(error => {
                console.error('Error:', error);
                NProgress.done();
            });
        },
        
        matchesSearch(row) {
            const query = this.searchQuery.toLowerCase();
            if (!query || query.trim() === '') return true;
            const invoice = (row.getAttribute('data-invoice') || '').toLowerCase();
            const customer = (row.getAttribute('data-customer') || '').toLowerCase();
            const material = (row.getAttribute('data-material') || '').toLowerCase();
            return invoice.includes(query) || customer.includes(query) || material.includes(query);
        },
        
        // Computed property untuk Extra Purchase Month Name
        get extraPurchaseMonthName() {
            const months = [
                { value: 1, name: 'January' }, { value: 2, name: 'February' }, { value: 3, name: 'March' },
                { value: 4, name: 'April' }, { value: 5, name: 'May' }, { value: 6, name: 'June' },
                { value: 7, name: 'July' }, { value: 8, name: 'August' }, { value: 9, name: 'September' },
                { value: 10, name: 'October' }, { value: 11, name: 'November' }, { value: 12, name: 'December' }
            ];
            const month = months.find(m => m.value === this.extraPurchaseBalanceMonth);
            return month ? month.name : null;
        },
        
        // Computed property untuk Edit Material Month Name
        get editBalanceMonthName() {
            const months = [
                { value: 1, name: 'January' }, { value: 2, name: 'February' }, { value: 3, name: 'March' },
                { value: 4, name: 'April' }, { value: 5, name: 'May' }, { value: 6, name: 'June' },
                { value: 7, name: 'July' }, { value: 8, name: 'August' }, { value: 9, name: 'September' },
                { value: 10, name: 'October' }, { value: 11, name: 'November' }, { value: 12, name: 'December' }
            ];
            const month = months.find(m => m.value === this.editBalanceMonth);
            return month ? month.name : null;
        },

        // Webcam functions
        async startPurchaseWebcam() {
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
                this.$refs.purchaseVideo.srcObject = this.stream;
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
        
        async togglePurchaseCamera() {
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
                this.$refs.purchaseVideo.srcObject = this.stream;
            } catch (err) {
                alert('Gagal mengganti kamera. Error: ' + err.message);
            }
        },
        
        stopPurchaseWebcam() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.showWebcam = false;
        },
        
        capturePurchasePhoto(inputName = 'purchase_proof_image') {
            const video = this.$refs.purchaseVideo;
            const canvas = this.$refs.purchaseCanvas;
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
                const fileInput = document.querySelector(`input[name=${inputName}]`);
                if (fileInput) {
                    fileInput.files = dataTransfer.files;
                }
                this.imagePreview = canvas.toDataURL('image/jpeg');
                this.fileName = file.name;
                this.stopPurchaseWebcam();
            }, 'image/jpeg', 0.95);
        }
    }">

        {{-- Date Navigation (Right Aligned) - 100% mirip Order List --}}
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
        <div id="stats-section" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            {{-- Total Transactions --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_transactions']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Balance Used --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Balance Used</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($stats['balance_used'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Material Orders Table Section --}}
        <div id="materials-section" class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
            {{-- Header: Title Left, Search + Show Per Page + Button Purchase Right --}}
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Material Orders</h2>

                {{-- Search + Show Per Page + Button Purchase --}}
                <div class="flex gap-2 items-center xl:min-w-0">
                    {{-- Search Box --}}
                    <div class="flex-1 xl:min-w-[240px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="searchQuery" placeholder="Search by Invoice, Customer, or Material..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
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
                            const params = new URLSearchParams(window.location.search);
                            params.set('per_page', this.perPage);
                            params.delete('page');
                            
                            const url = '{{ route('finance.report.material') }}?' + params.toString();
                            window.history.pushState({}, '', url);
                            
                            NProgress.start();
                            fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.getElementById('materials-section');
                                
                                if (newSection) {
                                    document.getElementById('materials-section').innerHTML = newSection.innerHTML;
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
                            class="w-15 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                            <span x-text="selected.label"></span>
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

                    {{-- Button + Purchase --}}
                    <button type="button" @click="showPurchaseModal = true; purchaseErrors = {};" 
                        class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-md transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Purchase
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-primary-light text-gray-600">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold rounded-l-lg">No Invoice</th>
                            <th class="py-3 px-4 text-left font-bold">Customer</th>
                            <th class="py-3 px-4 text-left font-bold">Product</th>
                            <th class="py-3 px-4 text-left font-bold">QTY</th>
                            <th class="py-3 px-4 text-left font-bold">Total Expense</th>
                            <th class="py-3 px-4 text-left font-bold">Status</th>
                            <th class="py-3 px-4 text-left font-bold">Date</th>
                            <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody x-data="{ expandedRows: [], openPrimaryMenu: null }">
                        @forelse($materials as $orderReport)
                            @php
                                $order = $orderReport->order ?? null;
                                $invoice = $orderReport->invoice ?? null;
                                $customer = $order->customer ?? null;
                                $productCategory = $order->productCategory->product_name ?? '-';
                                $totalQty = $order ? $order->orderItems->sum('qty') : 0;
                                
                                // Get all material purchases for this order
                                $materialPurchases = $orderReport->materialReports;
                                $totalExpense = $materialPurchases->sum('amount');
                                
                                // Get first purchase date (from first_purchase type)
                                $firstPurchaseMaterial = $materialPurchases->where('purchase_type', 'first_purchase')->first();
                                $firstPurchaseDate = $firstPurchaseMaterial->purchase_date ?? null;
                                
                                // Get balance data dari first purchase
                                $firstPurchaseBalance = $firstPurchaseMaterial ? $firstPurchaseMaterial->balance : null;
                                // Extract month and year from period_start
                                $balanceMonth = $firstPurchaseBalance ? \Carbon\Carbon::parse($firstPurchaseBalance->period_start)->month : null;
                                $balanceYear = $firstPurchaseBalance ? \Carbon\Carbon::parse($firstPurchaseBalance->period_start)->year : null;
                                
                                // Get status from order and order_reports
                                $productionStatus = $order->production_status ?? '-';
                                $lockStatus = $orderReport->lock_status ?? 'draft';
                                
                                // Unique row ID
                                $rowId = $orderReport->id;
                            @endphp
                            <tr data-invoice="{{ $invoice->invoice_no ?? '' }}"
                                data-customer="{{ $customer->customer_name ?? '' }}"
                                data-product="{{ $productCategory }}"
                                x-show="matchesSearch($el)"
                                @click="expandedRows.includes({{ $rowId }}) ? expandedRows = expandedRows.filter(id => id !== {{ $rowId }}) : expandedRows.push({{ $rowId }})"
                                class="hover:bg-gray-50 cursor-pointer">
                                
                                {{-- No Invoice --}}
                                <td class="py-3 px-4 text-[12px]">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-medium text-gray-900">{{ str_replace('INV-', '', $invoice->invoice_no ?? '-') }}</span>
                                        @if ($order && $order->shipping_type)
                                            @if (strtolower($order->shipping_type) === 'pickup')
                                                <span class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">PICKUP</span>
                                            @elseif (strtolower($order->shipping_type) === 'delivery')
                                                <span class="px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 bg-blue-100 rounded">DELIVERY</span>
                                            @endif
                                        @endif
                                        @if ($order && isset($order->priority) && strtolower($order->priority) === 'high')
                                            <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Customer --}}
                                <td class="py-3 px-4 text-[12px]">
                                    <div>
                                        <p class="text-gray-700">{{ $customer->customer_name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $customer->phone ?? '-' }}</p>
                                    </div>
                                </td>

                                {{-- Product --}}
                                <td class="py-3 px-4 text-[12px]">
                                    <span class="text-gray-700">{{ $productCategory }}</span>
                                </td>

                                {{-- QTY --}}
                                <td class="py-3 px-4 text-[12px]">
                                    <span class="text-gray-700">{{ number_format($totalQty) }}</span>
                                </td>

                                {{-- Total Expense --}}
                                <td class="py-3 px-4 text-[12px] text-gray-900 font-semibold">
                                    Rp {{ number_format($totalExpense, 0, ',', '.') }}
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
                                        $statusClass = $statusClasses[$productionStatus] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-1 rounded-full text-[12px] font-medium {{ $statusClass }}">
                                            {{ strtoupper($productionStatus) }}
                                        </span>
                                        @if($lockStatus === 'locked')
                                            <span class="px-2 py-1 rounded-full text-[12px] font-medium bg-purple-100 text-purple-800">
                                                LOCKED
                                            </span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-[12px] font-medium bg-gray-100 text-gray-800">
                                                DRAFT
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Date (First Purchase) --}}
                                <td class="py-3 px-4 text-[12px] text-gray-700">
                                    {{ $firstPurchaseDate ? $firstPurchaseDate->format('d M Y') : '-' }}
                                </td>

                                {{-- Action --}}
                                <td class="py-3 px-4 text-center relative" @click.stop>
                                    {{-- Dropdown Menu --}}
                                    <div class="relative inline-block text-left" x-data="{ 
                                        open: false,
                                        menuId: {{ $rowId }},
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
                                    @close-all-primary-menus.window="if (menuId !== openPrimaryMenu) open = false"
                                    x-init="$watch('open', value => {
                                        if (value) {
                                            openPrimaryMenu = menuId;
                                            window.dispatchEvent(new CustomEvent('close-secondary-menus'));
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
                                        } else {
                                            if (openPrimaryMenu === menuId) {
                                                openPrimaryMenu = null;
                                            }
                                        }
                                    })">
                                        <button x-ref="button" @click="checkPosition(); open = !open" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle" class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                            <a href="{{ $order ? route('admin.orders.show', $order->id) : '#' }}" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View Detail
                                            </a>
                                            @if($lockStatus !== 'locked' || auth()->user()->role === 'owner')
                                                <button type="button" 
                                                    @click="
                                                        extraPurchaseOrderReportId = {{ $orderReport->id }}; 
                                                        extraPurchaseBalanceId = {{ $firstPurchaseBalance->id ?? 'null' }};
                                                        extraPurchaseBalanceMonth = {{ $balanceMonth ?? 'null' }};
                                                        extraPurchaseBalanceYear = {{ $balanceYear ?? 'null' }};
                                                        extraPurchaseBalanceTransfer = {{ $firstPurchaseBalance->transfer_balance ?? 0 }};
                                                        extraPurchaseBalanceCash = {{ $firstPurchaseBalance->cash_balance ?? 0 }};
                                                        extraPurchaseOrderData = {
                                                            id: {{ $orderReport->id }},
                                                            invoice: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }}',
                                                            customer: '{{ $customer->customer_name ?? '' }}',
                                                            product: '{{ $productCategory }}',
                                                            display_name: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }} - {{ $customer->customer_name ?? '' }} ({{ $productCategory }})'
                                                        };
                                                        showExtraPurchaseModal = true; 
                                                        open = false; 
                                                        extraPurchaseErrors = {};
                                                    " 
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Extra Purchase
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Expand Arrow (Absolute positioned at right edge of table) --}}
                                    <div class="absolute right-0 top-1/2 -translate-y-1/2 pr-2">
                                        <button type="button"
                                            @click="expandedRows.includes({{ $rowId }}) ? expandedRows = expandedRows.filter(id => id !== {{ $rowId }}) : expandedRows.push({{ $rowId }})"
                                            class="p-1 hover:bg-gray-100 rounded transition-colors">
                                            <svg class="w-5 h-5 text-gray-400 transition-transform" 
                                                :class="expandedRows.includes({{ $rowId }}) && 'rotate-180'" 
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            {{-- Expandable Detail Row --}}
                            <tr class="border-b border-gray-200">
                                <td colspan="8" class="p-0">
                                    <div x-show="expandedRows.includes({{ $rowId }})"
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
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600 rounded-md">No</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Material</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Supplier</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Payment Method</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Price / Pcs</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Attachment</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                    <th class="py-1.5 px-4 text-center text-[10px] font-semibold text-gray-600">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($materialPurchases as $index => $purchase)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-600">{{ $index + 1 }}</td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-900">{{ $purchase->material_name }}</td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-700">{{ $purchase->materialSupplier->supplier_name ?? '-' }}</td>
                                                        <td class="py-1.5 px-4 text-[10px]">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold 
                                                                {{ $purchase->payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                                                {{ strtoupper($purchase->payment_method) }}
                                                            </span>
                                                        </td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-900 font-semibold">Rp {{ number_format($purchase->amount, 0, ',', '.') }}</td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-700">Rp {{ number_format($totalQty > 0 ? $purchase->amount / $totalQty : 0, 0, ',', '.') }}</td>
                                                        <td class="py-1.5 px-4 text-left">
                                                            @if ($purchase->proof_img)
                                                                <button @click="$dispatch('open-image-modal', { url: '{{ route('finance.report.material.serve-image', $purchase->id) }}' })" 
                                                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                    </svg>
                                                                    View
                                                                </button>
                                                            @else
                                                                <span class="text-[10px] text-gray-400">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-700">{{ $purchase->purchase_date->format('d M Y') }}</td>
                                                        <td class="py-1.5 px-4 text-center">
                                                            @if($lockStatus === 'locked' && auth()->user()->role === 'finance')
                                                                <svg class="w-4 h-4 text-red-600 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                                </svg>
                                                            @else
                                                                <div class="relative inline-block text-left" x-data="{ 
                                                                    open: false, 
                                                                    dropdownStyle: {}, 
                                                                    checkPosition() { 
                                                                        const button = this.$refs.button; 
                                                                        const rect = button.getBoundingClientRect(); 
                                                                        const spaceBelow = window.innerHeight - rect.bottom; 
                                                                        const spaceAbove = rect.top; 
                                                                        const dropUp = spaceBelow < 150 && spaceAbove > spaceBelow; 
                                                                        if (dropUp) { 
                                                                            this.dropdownStyle = { position: 'fixed', top: (rect.top - 150) + 'px', left: (rect.right - 140) + 'px', width: '140px' }; 
                                                                        } else { 
                                                                            this.dropdownStyle = { position: 'fixed', top: (rect.bottom + 4) + 'px', left: (rect.right - 140) + 'px', width: '140px' }; 
                                                                        } 
                                                                    } 
                                                                }" 
                                                                @scroll.window="open = false"
                                                                @close-secondary-menus.window="open = false">
                                                                    <button x-ref="button" @click="
                                                                        checkPosition();
                                                                        open = !open;
                                                                        if (open) {
                                                                            // Tutup primary menu
                                                                            openPrimaryMenu = null;
                                                                            window.dispatchEvent(new CustomEvent('close-all-primary-menus'));
                                                                        }
                                                                    " type="button" 
                                                                        class="inline-flex items-center justify-center w-6 h-6 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                        </svg>
                                                                    </button>
                                                                    <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle" 
                                                                        class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                                        <a href="#" class="px-3 py-1.5 text-[11px] text-blue-600 hover:bg-blue-50 flex items-center gap-1.5"
                                                                            @click.prevent="
                                                                                editMaterialId = {{ $purchase->id }};
                                                                                editMaterialData = {
                                                                                    id: {{ $purchase->id }},
                                                                                    material_name: '{{ $purchase->material_name }}',
                                                                                    supplier_id: {{ $purchase->material_supplier_id }},
                                                                                    supplier_name: '{{ $purchase->materialSupplier->supplier_name ?? '' }}',
                                                                                    payment_method: '{{ $purchase->payment_method }}',
                                                                                    amount: {{ $purchase->amount }},
                                                                                    notes: '{{ addslashes($purchase->notes ?? '') }}',
                                                                                    proof_img: '{{ $purchase->proof_img }}',
                                                                                    purchase_date: '{{ $purchase->purchase_date->format('Y-m-d') }}',
                                                                                    purchase_type: '{{ ucfirst(str_replace('_', ' ', $purchase->purchase_type)) }}'
                                                                                };
                                                                                // Set balance data
                                                                                editBalanceId = {{ $firstPurchaseBalance->id ?? 'null' }};
                                                                                editBalanceMonth = {{ $balanceMonth ?? 'null' }};
                                                                                editBalanceYear = {{ $balanceYear ?? 'null' }};
                                                                                editBalanceTransfer = {{ $firstPurchaseBalance->transfer_balance ?? 0 }};
                                                                                editBalanceCash = {{ $firstPurchaseBalance->cash_balance ?? 0 }};
                                                                                // Set order report data
                                                                                editOrderReportData = {
                                                                                    id: {{ $orderReport->id }},
                                                                                    invoice: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }}',
                                                                                    customer: '{{ $customer->customer_name ?? '' }}',
                                                                                    product: '{{ $productCategory }}',
                                                                                    display_name: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }} - {{ $customer->customer_name ?? '' }} ({{ $productCategory }})'
                                                                                };
                                                                                // Set edit data
                                                                                editPurchaseDate = '{{ $purchase->purchase_date->format('Y-m-d') }}';
                                                                                editPurchaseType = '{{ ucfirst(str_replace('_', ' ', $purchase->purchase_type)) }}';
                                                                                editMaterialName = '{{ $purchase->material_name }}';
                                                                                editMaterialSupplierId = {{ $purchase->material_supplier_id }};
                                                                                editPaymentMethod = '{{ $purchase->payment_method }}';
                                                                                editPurchaseAmount = '{{ $purchase->amount }}';
                                                                                editPurchaseNotes = '{{ addslashes($purchase->notes ?? '') }}';
                                                                                editProofImage = '{{ $purchase->proof_img ? route('finance.report.material.serve-image', $purchase->id) : '' }}';
                                                                                showEditMaterialModal = true;
                                                                                open = false;
                                                                                editMaterialErrors = {};
                                                                            ">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                            </svg>
                                                                            Edit
                                                                        </a>
                                                                        <button type="button" 
                                                                            @click="showDeleteMaterial = {{ $purchase->id }}; open = false"
                                                                            class="w-full text-left px-3 py-1.5 text-[11px] text-red-600 hover:bg-red-50 flex items-center gap-1.5">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                            </svg>
                                                                            Delete
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm">No material orders found for this period.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($materials->hasPages())
                <div class="mt-4" id="materials-pagination-container">
                    {{ $materials->links() }}
                </div>
            @endif
        </div>

        {{-- Add Purchase Modal --}}
        <div x-show="showPurchaseModal" x-cloak
            @keydown.escape.window="showPurchaseModal = false; stopPurchaseWebcam()"
            x-data="{
                balanceMonth: null,
                balanceYear: null,
                balanceId: null,
                balanceTransfer: 0,
                balanceCash: 0,
                balanceMonthDropdownOpen: false,
                balanceYearDropdownOpen: false,
                orderReportId: null,
                orderReports: [],
                orderReportDropdownOpen: false,
                materialName: '',
                materialSupplierId: null,
                materialSuppliers: [],
                supplierDropdownOpen: false,
                paymentMethod: '',
                paymentMethodDropdownOpen: false,
                purchaseAmount: '',
                purchaseNotes: '',
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
                paymentMethodOptions: [
                    { value: 'cash', name: 'Cash' },
                    { value: 'transfer', name: 'Transfer' }
                ],
                init() {
                    const currentYear = 2026;
                    for (let i = 0; i < 10; i++) {
                        this.balanceYearOptions.push({ value: currentYear + i, name: (currentYear + i).toString() });
                    }
                    this.fetchSuppliers();
                },
                get selectedMonthName() {
                    const month = this.balanceMonthOptions.find(m => m.value === this.balanceMonth);
                    return month ? month.name : null;
                },
                get hasBalancePeriod() {
                    return this.balanceMonth !== null && this.balanceYear !== null;
                },
                get selectedOrderReport() {
                    return this.orderReports.find(o => o.id === this.orderReportId) || null;
                },
                get selectedSupplier() {
                    return this.materialSuppliers.find(s => s.id === this.materialSupplierId) || null;
                },
                get selectedPaymentMethod() {
                    return this.paymentMethodOptions.find(p => p.value === this.paymentMethod) || null;
                },
                async selectMonth(month) {
                    this.balanceMonth = month;
                    this.balanceMonthDropdownOpen = false;
                    if (this.balanceYear) {
                        await this.fetchBalanceData();
                        await this.fetchAvailableOrders();
                    }
                },
                async selectYear(year) {
                    this.balanceYear = year;
                    this.balanceYearDropdownOpen = false;
                    if (this.balanceMonth) {
                        await this.fetchBalanceData();
                        await this.fetchAvailableOrders();
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
                },
                async fetchAvailableOrders() {
                    if (!this.balanceMonth || !this.balanceYear) return;
                    
                    try {
                        const response = await fetch(`{{ route('finance.report.material.get-available-orders') }}?month=${this.balanceMonth}&year=${this.balanceYear}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            this.orderReports = data.orders;
                        }
                    } catch (error) {
                        console.error('Error fetching orders:', error);
                        this.orderReports = [];
                    }
                },
                async fetchSuppliers() {
                    try {
                        const response = await fetch('{{ route('finance.report.material.get-suppliers') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            this.materialSuppliers = data.suppliers;
                        }
                    } catch (error) {
                        console.error('Error fetching suppliers:', error);
                    }
                },
                selectOrderReport(order) {
                    this.orderReportId = order.id;
                    this.orderReportDropdownOpen = false;
                },
                selectSupplier(supplier) {
                    this.materialSupplierId = supplier.id;
                    this.supplierDropdownOpen = false;
                },
                selectPaymentMethod(method) {
                    this.paymentMethod = method.value;
                    this.paymentMethodDropdownOpen = false;
                }
            }"
            x-init="
                $watch('showPurchaseModal', value => {
                    if (value) {
                        balanceMonth = null;
                        balanceYear = null;
                        balanceId = null;
                        balanceTransfer = 0;
                        balanceCash = 0;
                        orderReportId = null;
                        orderReports = [];
                        materialName = '';
                        materialSupplierId = null;
                        paymentMethod = '';
                        purchaseAmount = '';
                        purchaseNotes = '';
                        purchaseErrors = {};
                        imagePreview = null;
                        fileName = '';
                    }
                })
            "
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">
            
            {{-- Background Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showPurchaseModal = false; stopPurchaseWebcam()" 
                     class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                    
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">New Material Purchase</h3>
                        <button @click="showPurchaseModal = false; stopPurchaseWebcam()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <form id="addPurchaseForm" x-ref="addPurchaseForm" @submit.prevent="
                            purchaseErrors = {};
                            let hasValidationError = false;

                            if (!balanceMonth || !balanceYear) {
                                purchaseErrors.balance_period = ['Balance period is required'];
                                hasValidationError = true;
                            }

                            if (!orderReportId) {
                                purchaseErrors.order_report_id = ['Order selection is required'];
                                hasValidationError = true;
                            }

                            if (!materialName || materialName.trim() === '') {
                                purchaseErrors.material_name = ['Material name is required'];
                                hasValidationError = true;
                            }

                            if (!materialSupplierId) {
                                purchaseErrors.material_supplier_id = ['Supplier is required'];
                                hasValidationError = true;
                            }

                            if (!paymentMethod) {
                                purchaseErrors.payment_method = ['Payment method is required'];
                                hasValidationError = true;
                            }

                            const amountValue = purchaseAmount.replace(/[^0-9]/g, '');
                            if (!amountValue || parseInt(amountValue) < 1) {
                                purchaseErrors.amount = ['Amount is required and must be at least Rp 1'];
                                hasValidationError = true;
                            }

                            if (!imagePreview || !fileName) {
                                purchaseErrors.proof_image = ['Proof image is required'];
                                hasValidationError = true;
                            }

                            if (hasValidationError) {
                                return;
                            }

                            isSubmittingPurchase = true;
                            const formData = new FormData();
                            formData.append('balance_month', balanceMonth);
                            formData.append('balance_year', balanceYear);
                            formData.append('order_report_id', orderReportId);
                            formData.append('material_name', materialName);
                            if (materialSupplierId) formData.append('material_supplier_id', materialSupplierId);
                            formData.append('payment_method', paymentMethod);
                            formData.append('amount', amountValue);
                            if (purchaseNotes) formData.append('notes', purchaseNotes);
                            
                            if (imagePreview && fileName) {
                                const fileInput = document.querySelector('input[name=purchase_proof_image]');
                                if (fileInput && fileInput.files[0]) {
                                    formData.append('proof_image', fileInput.files[0]);
                                }
                            }
                            
                            fetch('{{ route('finance.report.material.store') }}', {
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
                                    sessionStorage.setItem('toast_message', data.message || 'Material purchase created successfully!');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else if (status === 422) {
                                    isSubmittingPurchase = false;
                                    purchaseErrors = data.errors || {};
                                } else {
                                    isSubmittingPurchase = false;
                                    purchaseErrors = data.errors || {};
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingPurchase = false;
                                console.error('Purchase error:', err);
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: 'Failed to create purchase. Please try again.', type: 'error' }
                                }));
                            });
                        ">
                            <div class="space-y-4">
                                {{-- Balance Period Selector --}}
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
                                    <template x-if="purchaseErrors.balance_period">
                                        <p class="mt-1 text-xs text-red-600" x-text="purchaseErrors.balance_period[0]"></p>
                                    </template>
                                </div>

                                {{-- Content shown only after Balance Period is selected --}}
                                <div x-show="hasBalancePeriod" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100">
                                    
                                    {{-- Balance Cards --}}
                                    <div class="grid grid-cols-2 gap-3 mb-4">
                                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                            <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                            <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(balanceTransfer).toLocaleString('id-ID')"></p>
                                        </div>
                                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                            <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                            <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(balanceCash).toLocaleString('id-ID')"></p>
                                        </div>
                                    </div>

                                    {{-- Select Order Report --}}
                                    <div x-data="{ orderSearchQuery: '' }">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Data Orders <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <button type="button" @click="orderReportDropdownOpen = !orderReportDropdownOpen; orderSearchQuery = ''"
                                                :class="purchaseErrors.order_report_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                <span x-text="selectedOrderReport ? selectedOrderReport.display_name : 'Select Order'"
                                                    :class="!selectedOrderReport ? 'text-gray-400' : 'text-gray-900'" class="truncate"></span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0 ml-2" :class="orderReportDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            
                                            <div x-show="orderReportDropdownOpen" @click.away="orderReportDropdownOpen = false" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-72">
                                                
                                                {{-- Search Input --}}
                                                <div class="sticky top-0 bg-white border-b border-gray-200 p-2">
                                                    <div class="relative">
                                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                        <input type="text" x-model="orderSearchQuery" 
                                                            @click.stop
                                                            placeholder="Search invoice, customer, product..."
                                                            class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                                    </div>
                                                </div>

                                                {{-- Order List --}}
                                                <div class="overflow-y-auto" style="max-height: 200px;">
                                                    <div class="py-1">
                                                        <template x-if="orderReports.length === 0">
                                                            <div class="px-4 py-3 text-sm text-gray-500 text-center">
                                                                No available orders for this period
                                                            </div>
                                                        </template>
                                                        <template x-for="order in orderReports" :key="order.id">
                                                            <button type="button" 
                                                                x-show="!orderSearchQuery || 
                                                                    order.invoice.toLowerCase().includes(orderSearchQuery.toLowerCase()) || 
                                                                    order.customer.toLowerCase().includes(orderSearchQuery.toLowerCase()) || 
                                                                    order.product.toLowerCase().includes(orderSearchQuery.toLowerCase())"
                                                                @click="selectOrderReport(order); orderSearchQuery = ''"
                                                                :class="orderReportId === order.id ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                                x-text="order.display_name">
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="purchaseErrors.order_report_id">
                                            <p class="mt-1 text-xs text-red-600" x-text="purchaseErrors.order_report_id[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Purchase Date & Purchase Type (Locked) --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Purchase Date <span class="text-red-600">*</span>
                                            </label>
                                            <input type="date" value="{{ now()->toDateString() }}" readonly
                                                class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Purchase Type <span class="text-red-600">*</span>
                                            </label>
                                            <input type="text" value="First Purchase" readonly
                                                class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                        </div>
                                    </div>

                                    {{-- Material Name & Supplier (2 columns on desktop) --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {{-- Material Name --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Material Name <span class="text-red-600">*</span>
                                            </label>
                                            <input type="text" x-model="materialName"
                                                :class="purchaseErrors.material_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                placeholder="Enter material name">
                                            <template x-if="purchaseErrors.material_name">
                                                <p class="mt-1 text-xs text-red-600" x-text="purchaseErrors.material_name[0]"></p>
                                            </template>
                                        </div>

                                        {{-- Supplier --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Supplier <span class="text-red-600">*</span>
                                            </label>
                                            <div class="relative">
                                                <button type="button" @click="supplierDropdownOpen = !supplierDropdownOpen"
                                                    :class="purchaseErrors.material_supplier_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                    <span x-text="selectedSupplier ? selectedSupplier.supplier_name : 'Select Supplier'"
                                                        :class="!selectedSupplier ? 'text-gray-400' : 'text-gray-900'"></span>
                                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="supplierDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                
                                                <div x-show="supplierDropdownOpen" @click.away="supplierDropdownOpen = false" x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                                    <div class="py-1">
                                                        <template x-for="supplier in materialSuppliers" :key="supplier.id">
                                                            <button type="button" @click="selectSupplier(supplier)"
                                                                :class="materialSupplierId === supplier.id ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                                x-text="supplier.supplier_name">
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <template x-if="purchaseErrors.material_supplier_id">
                                                <p class="mt-1 text-xs text-red-600" x-text="purchaseErrors.material_supplier_id[0]"></p>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Payment Method & Amount (2 columns on desktop) --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {{-- Payment Method --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Payment Method <span class="text-red-600">*</span>
                                            </label>
                                            <div class="relative">
                                                <button type="button" @click="paymentMethodDropdownOpen = !paymentMethodDropdownOpen"
                                                    :class="purchaseErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                    <span x-text="selectedPaymentMethod ? selectedPaymentMethod.name : 'Select Payment Method'"
                                                        :class="!selectedPaymentMethod ? 'text-gray-400' : 'text-gray-900'"></span>
                                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="paymentMethodDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                
                                                <div x-show="paymentMethodDropdownOpen" @click.away="paymentMethodDropdownOpen = false" x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                                    <div class="py-1">
                                                        <template x-for="method in paymentMethodOptions" :key="method.value">
                                                            <button type="button" @click="selectPaymentMethod(method)"
                                                                :class="paymentMethod === method.value ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                                x-text="method.name">
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <template x-if="purchaseErrors.payment_method">
                                                <p class="mt-1 text-xs text-red-600" x-text="purchaseErrors.payment_method[0]"></p>
                                            </template>
                                        </div>

                                        {{-- Amount --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Amount <span class="text-red-600">*</span>
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                                <input type="text" x-model="purchaseAmount"
                                                    @input="purchaseAmount = purchaseAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                    :class="purchaseErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                    placeholder="0">
                                            </div>
                                            <template x-if="purchaseErrors.amount">
                                                <p class="mt-1 text-xs text-red-600" x-text="purchaseErrors.amount[0]"></p>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Proof Image - Webcam --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Proof of Payment <span class="text-red-600">*</span>
                                        </label>
                                        
                                        {{-- Webcam Section --}}
                                        <div x-show="showWebcam" class="mb-3">
                                            <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                                <video x-ref="purchaseVideo" autoplay playsinline 
                                                    :class="{ 'scale-x-[-1]': isMirrored }"
                                                    class="w-full h-full object-cover"></video>
                                                <canvas x-ref="purchaseCanvas" class="hidden"></canvas>
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <button type="button" @click="capturePurchasePhoto()"
                                                class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Capture
                                                </button>
                                                <button type="button" @click="togglePurchaseCamera()"
                                                class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="stopPurchaseWebcam()"
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
                                                <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=purchase_proof_image]').value = ''; startPurchaseWebcam()"
                                                    class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=purchase_proof_image]').value = ''"
                                                    class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Open Camera Button --}}
                                        <div x-show="!imagePreview && !showWebcam">
                                            <button type="button" @click="startPurchaseWebcam()"
                                            class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Open Camera
                                            </button>
                                        </div>
                                        <input type="file" name="purchase_proof_image" accept="image/*" class="hidden">
                                        <template x-if="purchaseErrors.proof_image">
                                            <p class="mt-1 text-xs text-red-600" x-text="purchaseErrors.proof_image[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Notes --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea x-model="purchaseNotes" rows="3"
                                            class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                            placeholder="Optional notes..."></textarea>
                                    </div>

                                </div>
                                {{-- End: Content shown only after Balance Period is selected --}}
                            </div>

                            {{-- Submit Button --}}
                            <div class="mt-6 flex gap-3">
                                <button type="button" @click="showPurchaseModal = false; stopPurchaseWebcam()"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="isSubmittingPurchase"
                                    :class="isSubmittingPurchase ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                                    class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                    <template x-if="isSubmittingPurchase">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                    <span x-text="isSubmittingPurchase ? 'Processing...' : 'Create Purchase'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Add Purchase Modal --}}

        {{-- Extra Purchase Modal --}}
        <div x-show="showExtraPurchaseModal" x-cloak
            @keydown.escape.window="showExtraPurchaseModal = false; stopPurchaseWebcam()"
            x-data="{
                balanceMonth: null,
                balanceYear: null,
                balanceId: null,
                balanceTransfer: 0,
                balanceCash: 0,
                orderReportData: null,
                materialName: '',
                materialSupplierId: null,
                materialSuppliers: [],
                supplierDropdownOpen: false,
                paymentMethod: '',
                paymentMethodDropdownOpen: false,
                purchaseAmount: '',
                purchaseNotes: '',
                paymentMethodOptions: [
                    { value: 'cash', name: 'Cash' },
                    { value: 'transfer', name: 'Transfer' }
                ],
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
                init() {
                    this.fetchSuppliers();
                    // Watch for modal open and load data langsung dari root scope
                    this.$watch('$root.showExtraPurchaseModal', (value) => {
                        if (value) {
                            // Set data langsung dari root scope
                            this.balanceId = $root.extraPurchaseBalanceId;
                            this.balanceMonth = $root.extraPurchaseBalanceMonth;
                            this.balanceYear = $root.extraPurchaseBalanceYear;
                            this.balanceTransfer = $root.extraPurchaseBalanceTransfer;
                            this.balanceCash = $root.extraPurchaseBalanceCash;
                            this.orderReportData = $root.extraPurchaseOrderData;
                            
                            console.log('Extra Purchase Data Loaded from ROOT:');
                            console.log('Balance Month:', this.balanceMonth, 'Type:', typeof this.balanceMonth);
                            console.log('Balance Year:', this.balanceYear);
                            console.log('Selected Month Name:', this.selectedMonthName);
                        }
                    });
                },
                get selectedMonthName() {
                    const month = this.balanceMonthOptions.find(m => m.value === this.balanceMonth);
                    return month ? month.name : null;
                },
                get selectedSupplier() {
                    return this.materialSuppliers.find(s => s.id === this.materialSupplierId) || null;
                },
                get selectedPaymentMethod() {
                    return this.paymentMethodOptions.find(p => p.value === this.paymentMethod) || null;
                },
                async fetchOrderReportData() {
                    if (!extraPurchaseOrderReportId) return;
                    
                    try {
                        const response = await fetch(`{{ url('finance/report/material/get-order-report') }}/${extraPurchaseOrderReportId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        
                        console.log('API Response:', data);
                        
                        if (data.success && data.order_report && data.balance) {
                            // Set order data
                            this.orderReportData = data.order_report;
                            
                            // Set balance data langsung dari first_purchase
                            this.balanceId = data.balance.id;
                            this.balanceMonth = data.balance.month;
                            this.balanceYear = data.balance.year;
                            this.balanceTransfer = data.balance.transfer_balance;
                            this.balanceCash = data.balance.cash_balance;
                            
                            console.log('Balance Month:', this.balanceMonth, 'Type:', typeof this.balanceMonth);
                            console.log('Balance Year:', this.balanceYear);
                            console.log('Selected Month Name:', this.selectedMonthName);
                        } else {
                            console.error('Failed to fetch order report:', data.message);
                        }
                    } catch (error) {
                        console.error('Error fetching order report:', error);
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
                        }
                    } catch (error) {
                        console.error('Error fetching balance:', error);
                    }
                },
                async fetchSuppliers() {
                    try {
                        const response = await fetch('{{ route('finance.report.material.get-suppliers') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            this.materialSuppliers = data.suppliers;
                        }
                    } catch (error) {
                        console.error('Error fetching suppliers:', error);
                    }
                },
                selectSupplier(supplier) {
                    this.materialSupplierId = supplier.id;
                    this.supplierDropdownOpen = false;
                },
                selectPaymentMethod(method) {
                    this.paymentMethod = method.value;
                    this.paymentMethodDropdownOpen = false;
                }
            }"
            x-init="
                $watch('showExtraPurchaseModal', value => {
                    if (value) {
                        materialName = '';
                        materialSupplierId = null;
                        paymentMethod = '';
                        purchaseAmount = '';
                        purchaseNotes = '';
                        extraPurchaseErrors = {};
                        imagePreview = null;
                        fileName = '';
                        orderReportData = null;
                        balanceMonth = null;
                        balanceYear = null;
                        balanceId = null;
                        balanceTransfer = 0;
                        balanceCash = 0;
                        setTimeout(() => {
                            fetchOrderReportData();
                        }, 100);
                    }
                })
            "
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">
            
            {{-- Background Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showExtraPurchaseModal = false; stopPurchaseWebcam()" 
                     class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                    
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Extra Material Purchase</h3>
                        <button @click="showExtraPurchaseModal = false; stopPurchaseWebcam()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <form id="addExtraPurchaseForm" @submit.prevent="
                            extraPurchaseErrors = {};
                            let hasValidationError = false;

                            if (!materialName || materialName.trim() === '') {
                                extraPurchaseErrors.material_name = ['Material name is required'];
                                hasValidationError = true;
                            }

                            if (!materialSupplierId) {
                                extraPurchaseErrors.material_supplier_id = ['Supplier is required'];
                                hasValidationError = true;
                            }

                            if (!paymentMethod) {
                                extraPurchaseErrors.payment_method = ['Payment method is required'];
                                hasValidationError = true;
                            }

                            const amountValue = purchaseAmount.replace(/[^0-9]/g, '');
                            if (!amountValue || parseInt(amountValue) < 1) {
                                extraPurchaseErrors.amount = ['Amount is required and must be at least Rp 1'];
                                hasValidationError = true;
                            }

                            if (!imagePreview || !fileName) {
                                extraPurchaseErrors.proof_image = ['Proof image is required'];
                                hasValidationError = true;
                            }

                            if (hasValidationError) {
                                return;
                            }

                            isSubmittingExtraPurchase = true;
                            const formData = new FormData();
                            formData.append('order_report_id', extraPurchaseOrderReportId);
                            formData.append('material_name', materialName);
                            if (materialSupplierId) formData.append('material_supplier_id', materialSupplierId);
                            formData.append('payment_method', paymentMethod);
                            formData.append('amount', amountValue);
                            if (purchaseNotes) formData.append('notes', purchaseNotes);
                            
                            if (imagePreview && fileName) {
                                const fileInput = document.querySelector('input[name=extra_purchase_proof_image]');
                                if (fileInput && fileInput.files[0]) {
                                    formData.append('proof_image', fileInput.files[0]);
                                }
                            }
                            
                            fetch('{{ route('finance.report.material.store-extra') }}', {
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
                                    sessionStorage.setItem('toast_message', data.message || 'Extra purchase created successfully!');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else if (status === 422) {
                                    isSubmittingExtraPurchase = false;
                                    extraPurchaseErrors = data.errors || {};
                                } else {
                                    isSubmittingExtraPurchase = false;
                                    extraPurchaseErrors = data.errors || {};
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingExtraPurchase = false;
                                console.error('Extra purchase error:', err);
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: 'Failed to create extra purchase. Please try again.', type: 'error' }
                                }));
                            });
                        ">
                            <div class="space-y-4">
                                {{-- Balance Period Selector (Locked) --}}
                                <div class="mb-6 p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                    <label class="block text-sm font-semibold text-gray-900 mb-3">
                                        Balance Period <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" 
                                        :value="extraPurchaseMonthName && extraPurchaseBalanceYear ? extraPurchaseMonthName + ' ' + extraPurchaseBalanceYear : 'Loading...'"
                                        readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    <p class="mt-2 text-xs text-primary font-medium" x-show="extraPurchaseMonthName && extraPurchaseBalanceYear">
                                        <span class="font-semibold">Selected:</span> <span x-text="extraPurchaseMonthName + ' ' + extraPurchaseBalanceYear"></span>
                                    </p>
                                </div>

                                {{-- Balance Cards --}}
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                        <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                        <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(extraPurchaseBalanceTransfer || 0).toLocaleString('id-ID')"></p>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                        <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                        <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(extraPurchaseBalanceCash || 0).toLocaleString('id-ID')"></p>
                                    </div>
                                </div>

                                {{-- Data Orders (Locked/Readonly) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Data Orders <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" 
                                        :value="extraPurchaseOrderData ? extraPurchaseOrderData.display_name : 'Loading...'"
                                        readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>

                                {{-- Purchase Date & Purchase Type (Locked) --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Purchase Date <span class="text-red-600">*</span>
                                        </label>
                                        <input type="date" value="{{ now()->toDateString() }}" readonly
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Purchase Type <span class="text-red-600">*</span>
                                        </label>
                                        <input type="text" value="Extra Purchase" readonly
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    </div>
                                </div>

                                {{-- Material Name & Supplier --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Material Name --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Material Name <span class="text-red-600">*</span>
                                        </label>
                                        <input type="text" x-model="materialName"
                                            :class="extraPurchaseErrors.material_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                            placeholder="Enter material name">
                                        <template x-if="extraPurchaseErrors.material_name">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraPurchaseErrors.material_name[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Supplier --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Supplier <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <button type="button" @click="supplierDropdownOpen = !supplierDropdownOpen"
                                                :class="extraPurchaseErrors.material_supplier_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                <span x-text="selectedSupplier ? selectedSupplier.supplier_name : 'Select Supplier'"
                                                    :class="!selectedSupplier ? 'text-gray-400' : 'text-gray-900'"></span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="supplierDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            
                                            <div x-show="supplierDropdownOpen" @click.away="supplierDropdownOpen = false" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                                <div class="py-1">
                                                    <template x-for="supplier in materialSuppliers" :key="supplier.id">
                                                        <button type="button" @click="selectSupplier(supplier)"
                                                            :class="materialSupplierId === supplier.id ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                            x-text="supplier.supplier_name">
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="extraPurchaseErrors.material_supplier_id">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraPurchaseErrors.material_supplier_id[0]"></p>
                                        </template>
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
                                            <button type="button" @click="paymentMethodDropdownOpen = !paymentMethodDropdownOpen"
                                                :class="extraPurchaseErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                <span x-text="selectedPaymentMethod ? selectedPaymentMethod.name : 'Select Payment Method'"
                                                    :class="!selectedPaymentMethod ? 'text-gray-400' : 'text-gray-900'"></span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="paymentMethodDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            
                                            <div x-show="paymentMethodDropdownOpen" @click.away="paymentMethodDropdownOpen = false" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                                <div class="py-1">
                                                    <template x-for="method in paymentMethodOptions" :key="method.value">
                                                        <button type="button" @click="selectPaymentMethod(method)"
                                                            :class="paymentMethod === method.value ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                            x-text="method.name">
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="extraPurchaseErrors.payment_method">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraPurchaseErrors.payment_method[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Amount --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Amount <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                            <input type="text" x-model="purchaseAmount"
                                                @input="purchaseAmount = purchaseAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                :class="extraPurchaseErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                placeholder="0">
                                        </div>
                                        <template x-if="extraPurchaseErrors.amount">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraPurchaseErrors.amount[0]"></p>
                                        </template>
                                    </div>
                                </div>

                                {{-- Proof Image - Webcam (Same as Purchase modal) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Proof of Payment <span class="text-red-600">*</span>
                                    </label>
                                    
                                    {{-- Webcam Section --}}
                                    <div x-show="showWebcam" class="mb-3">
                                        <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                            <video x-ref="purchaseVideo" autoplay playsinline 
                                                :class="{ 'scale-x-[-1]': isMirrored }"
                                                class="w-full h-full object-cover"></video>
                                            <canvas x-ref="purchaseCanvas" class="hidden"></canvas>
                                        </div>
                                        <div class="flex gap-2 mt-3">
                                            <button type="button" @click="capturePurchasePhoto('extra_purchase_proof_image')"
                                            class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Capture
                                            </button>
                                            <button type="button" @click="togglePurchaseCamera()"
                                            class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="stopPurchaseWebcam()"
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
                                            <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=extra_purchase_proof_image]').value = ''; startPurchaseWebcam()"
                                                class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=extra_purchase_proof_image]').value = ''"
                                                class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Open Camera Button --}}
                                    <div x-show="!imagePreview && !showWebcam">
                                        <button type="button" @click="startPurchaseWebcam()"
                                        class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Open Camera
                                        </button>
                                    </div>
                                    <input type="file" name="extra_purchase_proof_image" accept="image/*" class="hidden">
                                    <template x-if="extraPurchaseErrors.proof_image">
                                        <p class="mt-1 text-xs text-red-600" x-text="extraPurchaseErrors.proof_image[0]"></p>
                                    </template>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Notes
                                    </label>
                                    <textarea x-model="purchaseNotes" rows="3"
                                        class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                        placeholder="Optional notes..."></textarea>
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="mt-6 flex gap-3">
                                <button type="button" @click="showExtraPurchaseModal = false; stopPurchaseWebcam()"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="isSubmittingExtraPurchase"
                                    :class="isSubmittingExtraPurchase ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                                    class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                    <template x-if="isSubmittingExtraPurchase">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                    <span x-text="isSubmittingExtraPurchase ? 'Processing...' : 'Create Extra Purchase'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Extra Purchase Modal --}}

        {{-- Edit Material Modal --}}
        <div x-show="showEditMaterialModal" x-cloak
            @keydown.escape.window="showEditMaterialModal = false; stopPurchaseWebcam()"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
            <div @click.away="showEditMaterialModal = false; stopPurchaseWebcam()"
                class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Material Purchase</h3>
                    <button @click="showEditMaterialModal = false; stopPurchaseWebcam()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="
                        if (isSubmittingEditMaterial) return;
                        isSubmittingEditMaterial = true;
                        editMaterialErrors = {};
                        
                        const formData = new FormData();
                        formData.append('_method', 'PUT');
                        formData.append('material_name', editMaterialName || '');
                        formData.append('material_supplier_id', editMaterialSupplierId || '');
                        formData.append('payment_method', editPaymentMethod || '');
                        formData.append('amount', editPurchaseAmount || '');
                        formData.append('notes', editPurchaseNotes || '');
                        
                        // Handle proof image upload (jika ada foto baru)
                        const fileInput = document.querySelector('input[name=edit_material_proof_image]');
                        if (fileInput && fileInput.files[0]) {
                            formData.append('proof_image', fileInput.files[0]);
                        }
                        
                        fetch(`{{ url('finance/report/material') }}/${editMaterialId}`, {
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
                                sessionStorage.setItem('toast_message', data.message || 'Material purchase updated successfully!');
                                sessionStorage.setItem('toast_type', 'success');
                                window.location.reload();
                            } else if (status === 422) {
                                isSubmittingEditMaterial = false;
                                editMaterialErrors = data.errors || {};
                            } else {
                                isSubmittingEditMaterial = false;
                                editMaterialErrors = data.errors || {};
                                if (data.message) {
                                    window.dispatchEvent(new CustomEvent('show-toast', {
                                        detail: { message: data.message, type: 'error' }
                                    }));
                                }
                            }
                        })
                        .catch(err => {
                            isSubmittingEditMaterial = false;
                            console.error('Edit material error:', err);
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: 'Failed to update material purchase. Please try again.', type: 'error' }
                            }));
                        });
                    ">
                        <div class="space-y-4">
                            {{-- Balance Period Selector (Locked) --}}
                            <div class="mb-6 p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                <label class="block text-sm font-semibold text-gray-900 mb-3">
                                    Balance Period <span class="text-red-600">*</span>
                                </label>
                                <input type="text" 
                                    :value="editBalanceMonthName && editBalanceYear ? editBalanceMonthName + ' ' + editBalanceYear : 'Loading...'"
                                    readonly
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                <p class="mt-2 text-xs text-primary font-medium" x-show="editBalanceMonthName && editBalanceYear">
                                    <span class="font-semibold">Selected:</span> <span x-text="editBalanceMonthName + ' ' + editBalanceYear"></span>
                                </p>
                            </div>

                            {{-- Balance Cards --}}
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                    <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                    <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(editBalanceTransfer || 0).toLocaleString('id-ID')"></p>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                    <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                    <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(editBalanceCash || 0).toLocaleString('id-ID')"></p>
                                </div>
                            </div>

                            {{-- Data Orders (Locked/Readonly) --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Orders <span class="text-red-600">*</span>
                                </label>
                                <input type="text" 
                                    :value="editOrderReportData ? editOrderReportData.display_name : 'Loading...'"
                                    readonly
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                            </div>

                            {{-- Purchase Date & Purchase Type (Locked) --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Purchase Date <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="editPurchaseDate" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Purchase Type <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="editPurchaseType" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>
                            </div>

                            {{-- Material Name & Supplier --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Material Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Material Name <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" x-model="editMaterialName"
                                        :class="editMaterialErrors.material_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                        placeholder="Enter material name">
                                    <template x-if="editMaterialErrors.material_name">
                                        <p class="mt-1 text-xs text-red-600" x-text="editMaterialErrors.material_name[0]"></p>
                                    </template>
                                </div>

                                {{-- Supplier --}}
                                <div x-data="{ 
                                    fetchSuppliers: async function() {
                                        try {
                                            const response = await fetch('{{ route('finance.report.material.get-suppliers') }}', {
                                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                                            });
                                            const data = await response.json();
                                            if (data.success) {
                                                editMaterialSuppliers = data.suppliers;
                                            }
                                        } catch (error) {
                                            console.error('Error fetching suppliers:', error);
                                        }
                                    }
                                }" x-init="if (editMaterialSuppliers.length === 0) fetchSuppliers()">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Supplier <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <button type="button" @click="editSupplierDropdownOpen = !editSupplierDropdownOpen"
                                            :class="editMaterialErrors.material_supplier_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="editMaterialSuppliers.find(s => s.id === editMaterialSupplierId)?.supplier_name || 'Select Supplier'" 
                                                :class="!editMaterialSupplierId && 'text-gray-400'"></span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="editSupplierDropdownOpen" @click.away="editSupplierDropdownOpen = false" x-cloak
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            <template x-for="supplier in editMaterialSuppliers" :key="supplier.id">
                                                <button type="button" @click="editMaterialSupplierId = supplier.id; editSupplierDropdownOpen = false"
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors"
                                                    :class="editMaterialSupplierId === supplier.id && 'bg-primary/10 font-medium text-primary'">
                                                    <span x-text="supplier.supplier_name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <template x-if="editMaterialErrors.material_supplier_id">
                                        <p class="mt-1 text-xs text-red-600" x-text="editMaterialErrors.material_supplier_id[0]"></p>
                                    </template>
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
                                            :class="editMaterialErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="editPaymentMethod ? (editPaymentMethod === 'cash' ? 'Cash' : 'Transfer') : 'Select Payment Method'" 
                                                :class="!editPaymentMethod && 'text-gray-400'"></span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="editPaymentMethodDropdownOpen" @click.away="editPaymentMethodDropdownOpen = false" x-cloak
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                            <button type="button" @click="editPaymentMethod = 'cash'; editPaymentMethodDropdownOpen = false"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors"
                                                :class="editPaymentMethod === 'cash' && 'bg-primary/10 font-medium text-primary'">
                                                Cash
                                            </button>
                                            <button type="button" @click="editPaymentMethod = 'transfer'; editPaymentMethodDropdownOpen = false"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors"
                                                :class="editPaymentMethod === 'transfer' && 'bg-primary/10 font-medium text-primary'">
                                                Transfer
                                            </button>
                                        </div>
                                    </div>
                                    <template x-if="editMaterialErrors.payment_method">
                                        <p class="mt-1 text-xs text-red-600" x-text="editMaterialErrors.payment_method[0]"></p>
                                    </template>
                                </div>

                                {{-- Amount --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Amount <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                                        <input type="number" x-model="editPurchaseAmount"
                                            :class="editMaterialErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md border pl-12 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                            placeholder="0" min="1">
                                    </div>
                                    <template x-if="editMaterialErrors.amount">
                                        <p class="mt-1 text-xs text-red-600" x-text="editMaterialErrors.amount[0]"></p>
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
                                        <video x-ref="purchaseVideo" autoplay playsinline 
                                            :class="{ 'scale-x-[-1]': isMirrored }"
                                            class="w-full h-full object-cover"></video>
                                        <canvas x-ref="purchaseCanvas" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="capturePurchasePhoto('edit_material_proof_image')"
                                        class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Capture
                                        </button>
                                        <button type="button" @click="togglePurchaseCamera()"
                                        class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="stopPurchaseWebcam()"
                                        class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Close
                                        </button>
                                    </div>
                                </div>

                                {{-- Image Preview (Existing or Captured) --}}
                                <div x-show="(imagePreview || editProofImage) && !showWebcam" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="imagePreview || editProofImage" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="fileName || (editProofImage ? 'webcam_' + Date.now() + '.jpg' : '')"></p>
                                            <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                        </div>
                                        <button type="button" @click="editProofImage = null; imagePreview = null; fileName = ''; document.querySelector('input[name=edit_material_proof_image]').value = ''; startPurchaseWebcam()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="editProofImage = null; imagePreview = null; fileName = ''; document.querySelector('input[name=edit_material_proof_image]').value = ''"
                                            class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera Button --}}
                                <div x-show="!imagePreview && !showWebcam && !editProofImage">
                                    <button type="button" @click="startPurchaseWebcam()"
                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera
                                    </button>
                                </div>
                                <input type="file" name="edit_material_proof_image" accept="image/*" class="hidden">
                                <template x-if="editMaterialErrors.proof_image">
                                    <p class="mt-1 text-xs text-red-600" x-text="editMaterialErrors.proof_image[0]"></p>
                                </template>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea x-model="editPurchaseNotes" rows="3"
                                    class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                    placeholder="Optional notes..."></textarea>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <div class="mt-6 flex gap-3">
                            <button type="button" @click="showEditMaterialModal = false; stopPurchaseWebcam(); editProofImage = null; imagePreview = null"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingEditMaterial"
                                :class="isSubmittingEditMaterial ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                                class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <template x-if="isSubmittingEditMaterial">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <span x-text="isSubmittingEditMaterial ? 'Updating...' : 'Update Material'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- End Edit Material Modal --}}

        {{-- Delete Confirmation Modal --}}
        <div x-show="showDeleteMaterial !== null" x-cloak class="fixed inset-0 z-50">
            {{-- Background Overlay --}}
            <div x-show="showDeleteMaterial !== null" @click="showDeleteMaterial = null"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showDeleteMaterial = null"
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
                        Delete Material Purchase?
                    </h3>

                    {{-- Message --}}
                    <p class="text-sm text-gray-600 text-center mb-6">
                        Are you sure you want to delete this material purchase? The balance will be restored to the original amount.
                    </p>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <button type="button" @click="showDeleteMaterial = null"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <form :action="`{{ url('finance/report/material') }}/${showDeleteMaterial}`"
                            method="POST" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                                Yes, Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Delete Confirmation Modal --}}

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
                <img :src="selectedImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" style="max-height: calc(100vh - 10rem);" alt="Material proof">
            </div>
        </div>
    </div>
@endsection
