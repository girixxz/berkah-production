@extends('layouts.app')

@section('title', 'Internal Transfer')

@section('content')
    <x-nav-locate :items="['Finance', 'Internal Transfer']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        searchQuery: '{{ request('search') }}',
        transferTypeFilter: '{{ $transferType }}',
        currentMonth: {{ $month }},
        currentYear: {{ $year }},
        displayText: '{{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }}',
        showAddTransferModal: false,
        transferErrors: {},
        isSubmittingTransfer: false,
        stream: null,
        showWebcam: false,
        imagePreview: null,
        fileName: '',
        isMirrored: false,
        transferDate: '{{ now()->toDateString() }}',
        transferType: '',
        transferAmount: '',
        transferNotes: '',
        transferDropdownOpen: false,
        transferTypeOptions: [
            { value: 'transfer_to_cash', name: 'Transfer to Cash' },
            { value: 'cash_to_transfer', name: 'Cash to Transfer' }
        ],
        
        get selectedTransferType() {
            return this.transferTypeOptions.find(o => o.value === this.transferType) || null;
        },
        
        selectTransferType(option) {
            this.transferType = option.value;
            this.transferDropdownOpen = false;
        },
        
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
        
        matchesSearch(row) {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const query = this.searchQuery.toLowerCase();
            const notes = (row.getAttribute('data-notes') || '').toLowerCase();
            const amount = (row.getAttribute('data-amount') || '').toLowerCase();
            return notes.includes(query) || amount.includes(query);
        },
        
        get hasVisibleRows() {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const tbody = document.querySelector('tbody');
            if (!tbody) return true;
            const rows = tbody.querySelectorAll('tr[data-notes]');
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
            
            this.loadMonth(newMonth, newYear);
        },
        
        loadMonth(month, year) {
            this.currentMonth = month;
            this.currentYear = year;
            
            const params = new URLSearchParams(window.location.search);
            params.set('month', month);
            params.set('year', year);
            
            const url = '{{ route('finance.internal-transfer') }}?' + params.toString();
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
                
                // Update stats cards
                const newStatsSection = doc.getElementById('stats-section');
                if (newStatsSection) {
                    document.getElementById('stats-section').innerHTML = newStatsSection.innerHTML;
                }
                
                // Update table
                const newTransferSection = doc.getElementById('transfer-section');
                if (newTransferSection) {
                    document.getElementById('transfer-section').innerHTML = newTransferSection.innerHTML;
                    setupPagination('transfer-pagination-container', 'transfer-section');
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
            params.set('transfer_type', this.transferTypeFilter);
            if (this.searchQuery) params.set('search', this.searchQuery);
            
            const perPageValue = this.getPerPageValue();
            if (perPageValue) params.set('per_page', perPageValue);
            
            const url = '{{ route('finance.internal-transfer') }}?' + params.toString();
            window.history.pushState({}, '', url);
            
            NProgress.start();
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newSection = doc.getElementById('transfer-section');
                if (newSection) {
                    document.getElementById('transfer-section').innerHTML = newSection.innerHTML;
                    setupPagination('transfer-pagination-container', 'transfer-section');
                }
                NProgress.done();
            })
            .catch(error => {
                console.error('Error:', error);
                NProgress.done();
            });
        },
        
        getPerPageValue() {
            const perPageContainer = document.querySelector('[x-data*=perPage]');
            if (!perPageContainer) return null;
            const alpineData = Alpine.$data(perPageContainer);
            return alpineData ? alpineData.perPage : null;
        },
        
        // Webcam & Image handling
        stream: null,
        showWebcam: false,
        imagePreview: null,
        fileName: '',
        isMirrored: false,
        facingMode: 'environment',
        
        async startTransferWebcam() {
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
                this.$refs.transferVideo.srcObject = this.stream;
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
        
        async toggleTransferCamera() {
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
                this.$refs.transferVideo.srcObject = this.stream;
            } catch (err) {
                alert('Gagal mengganti kamera. Error: ' + err.message);
            }
        },
        
        stopTransferWebcam() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.showWebcam = false;
        },
        
        captureTransferPhoto() {
            const video = this.$refs.transferVideo;
            const canvas = this.$refs.transferCanvas;
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
                const fileInput = document.querySelector('input[name=transfer_proof_image]');
                fileInput.files = dataTransfer.files;
                this.imagePreview = canvas.toDataURL('image/jpeg');
                this.fileName = file.name;
                this.stopTransferWebcam();
            }, 'image/jpeg', 0.95);
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

        {{-- ================= SECTION 2: STATS CARDS ================= --}}
        <div id="stats-section" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Total Balance --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($balance->total_balance ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Transfer Balance --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Transfer Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($balance->transfer_balance ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Cash Balance --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Cash Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($balance->cash_balance ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 3: TABLE ================= --}}
        <div class="bg-white border border-gray-200 rounded-lg" id="transfer-section">
            {{-- Header: Filters & Actions --}}
            <div class="p-5 border-b border-gray-200">
                <div class="flex flex-col xl:flex-row xl:items-center gap-4">
                    {{-- Transfer Type Filter Buttons - Full width on mobile (3 buttons) --}}
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="transferTypeFilter = 'all'; applyFilter()"
                            :class="transferTypeFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            All
                        </button>
                        <button type="button" @click="transferTypeFilter = 'transfer_to_cash'; applyFilter()"
                            :class="transferTypeFilter === 'transfer_to_cash' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            TF to Cash
                        </button>
                        <button type="button" @click="transferTypeFilter = 'cash_to_transfer'; applyFilter()"
                            :class="transferTypeFilter === 'cash_to_transfer' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors text-center">
                            Cash to TF
                        </button>
                    </div>

                    {{-- Right: Search, Show Per Page, Add Button --}}
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:flex-1 xl:ml-auto xl:gap-2 xl:min-w-0">
                        {{-- Search, Show Per Page & Add Button - Same row --}}
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
                                        placeholder="Search by notes or amount..."
                                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
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
                                    const url = '{{ route('finance.internal-transfer') }}?' + params.toString();
                                    window.history.pushState({}, '', url);
                                    
                                    NProgress.start();
                                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                        .then(response => response.text())
                                        .then(html => {
                                            const parser = new DOMParser();
                                            const doc = parser.parseFromString(html, 'text/html');
                                            const newSection = doc.getElementById('transfer-section');
                                            if (newSection) {
                                                document.getElementById('transfer-section').innerHTML = newSection.innerHTML;
                                                setupPagination('transfer-pagination-container', 'transfer-section');
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

                        {{-- Add Transfer Button - Separate row on mobile, same row on desktop --}}
                        <button type="button" @click="showAddTransferModal = true; transferErrors = {};"
                            class="w-full xl:w-auto px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark transition flex items-center justify-center gap-2 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Transfer
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
                            <th class="py-3 px-4 text-left font-bold">Period</th>
                            <th class="py-3 px-4 text-left font-bold">Transfer Type</th>
                            <th class="py-3 px-4 text-left font-bold">Amount</th>
                            <th class="py-3 px-4 text-left font-bold">Notes</th>
                            <th class="py-3 px-4 text-left font-bold rounded-r-lg">Attachment</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" x-data="{
                        get hasResults() {
                            if (!searchQuery || searchQuery.trim() === '') return true;
                            const search = searchQuery.toLowerCase();
                            return {{ Js::from($allTransfers->map(fn($t) => strtolower(($t->notes ?? '') . ' ' . $t->amount))) }}
                                .some(text => text.includes(search));
                        }
                    }">
                        @forelse ($transfers as $transfer)
                            <tr data-notes="{{ $transfer->notes }}" data-amount="{{ $transfer->amount }}"
                                x-show="searchQuery.trim() === ''">
                                <td class="py-3 px-4 whitespace-nowrap text-gray-500">
                                    {{ $transfer->transfer_date->format('d M Y') }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-500">
                                    {{ $transfer->period }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($transfer->transfer_type === 'transfer_to_cash')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Transfer → Cash
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Cash → Transfer
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-gray-900">
                                    Rp {{ number_format($transfer->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-gray-500">
                                    {{ Str::limit($transfer->notes ?? '-', 30) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-left">
                                    @if ($transfer->proof_img)
                                        <button @click="
                                            const imageModal = document.querySelector('[x-data*=showImageModal]');
                                            if (imageModal) {
                                                const alpine = Alpine.$data(imageModal);
                                                alpine.selectedImage = '{{ route('finance.internal-transfer.serve-image', $transfer->id) }}';
                                                alpine.showImageModal = true;
                                            }
                                        "
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
                            <tr x-show="searchQuery.trim() === ''">
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    No internal transfers found for this period.
                                </td>
                            </tr>
                        @endforelse

                        {{-- No search results row --}}
                        <tr x-show="!hasResults && searchQuery.trim() !== ''" x-cloak>
                            <td colspan="6" class="py-8 text-center text-gray-500">
                                No transfers found matching "<span x-text="searchQuery"></span>".
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($transfers->hasPages())
                <div id="transfer-pagination-container" class="px-5 pb-5">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>

        {{-- Add Transfer Modal --}}
    <div x-show="showAddTransferModal" x-cloak
        @keydown.escape.window="showAddTransferModal = false; stopTransferWebcam()"
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
                // Generate year options (2025 onwards - fitur mulai Feb 2025)
                const currentYear = new Date().getFullYear();
                const startYear = 2025;
                for (let year = startYear; year <= currentYear + 5; year++) {
                    this.balanceYearOptions.push({ value: year, name: year.toString() });
                }
            },
            get filteredMonthOptions() {
                // Jika tahun 2025, hanya tampilkan Feb-Des (fitur mulai Feb 2025)
                if (this.balanceYear === 2025) {
                    return this.balanceMonthOptions.filter(m => m.value >= 2);
                }
                return this.balanceMonthOptions;
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
        x-init="
            $watch('showAddTransferModal', value => {
                if (value) {
                    transferDate = '{{ now()->toDateString() }}';
                    transferType = '';
                    transferAmount = '';
                    transferNotes = '';
                    transferErrors = {};
                    imagePreview = null;
                    fileName = '';
                    
                    // Reset balance period
                    balanceMonth = null;
                    balanceYear = null;
                    balanceId = null;
                    balanceTransfer = 0;
                    balanceCash = 0;
                }
            })
        "
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        
        {{-- Background Overlay --}}
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
        
        {{-- Modal Panel --}}
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.away="showAddTransferModal = false; stopTransferWebcam()" 
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">New Internal Transfer</h3>
                    <button @click="showAddTransferModal = false; stopTransferWebcam()" type="button"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                        ✕
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">

                    <form id="addTransferForm" x-ref="addTransferForm" @submit.prevent="
                        transferErrors = {};
                        let hasValidationError = false;

                        // Validate balance period
                        if (!balanceMonth || !balanceYear) {
                            transferErrors.balance_period = ['Balance period is required'];
                            hasValidationError = true;
                        }

                        // Validate transfer type
                        if (!transferType) {
                            transferErrors.transfer_type = ['Transfer type is required'];
                            hasValidationError = true;
                        }

                        // Validate amount
                        const amountValue = transferAmount.replace(/[^0-9]/g, '');
                        if (!amountValue || parseInt(amountValue) < 1) {
                            transferErrors.amount = ['Amount is required and must be at least Rp 1'];
                            hasValidationError = true;
                        }

                        if (hasValidationError) {
                            return;
                        }

                        isSubmittingTransfer = true;
                        const formData = new FormData();
                        formData.append('balance_month', balanceMonth);
                        formData.append('balance_year', balanceYear);
                        formData.append('transfer_date', transferDate);
                        formData.append('transfer_type', transferType);
                        formData.append('amount', amountValue);
                        formData.append('notes', transferNotes);
                        
                        if (imagePreview && fileName) {
                            const fileInput = document.querySelector('input[name=transfer_proof_image]');
                            if (fileInput && fileInput.files[0]) {
                                formData.append('proof_image', fileInput.files[0]);
                            }
                        }
                        
                        fetch('{{ route('finance.internal-transfer.store') }}', {
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
                                sessionStorage.setItem('toast_message', data.message || 'Transfer created successfully!');
                                sessionStorage.setItem('toast_type', 'success');
                                window.location.reload();
                            } else if (status === 422) {
                                isSubmittingTransfer = false;
                                transferErrors = data.errors || {};
                            } else {
                                isSubmittingTransfer = false;
                                transferErrors = data.errors || {};
                                if (data.message) {
                                    window.dispatchEvent(new CustomEvent('show-toast', {
                                        detail: { message: data.message, type: 'error' }
                                    }));
                                }
                            }
                        })
                        .catch(err => {
                            isSubmittingTransfer = false;
                            console.error('Transfer error:', err);
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: 'Failed to create transfer. Please try again.', type: 'error' }
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
                                                <template x-for="month in filteredMonthOptions" :key="month.value">
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
                                <template x-if="transferErrors.balance_period">
                                    <p class="mt-1 text-xs text-red-600" x-text="transferErrors.balance_period[0]"></p>
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

                            {{-- Transfer Date & Transfer Type (Row) --}}
                            <div class="grid grid-cols-2 gap-3">
                                {{-- Transfer Date (Locked) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Transfer Date <span class="text-red-600">*</span>
                                    </label>
                                    <input type="date" x-model="transferDate" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>

                                {{-- Transfer Type Dropdown --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Transfer Type <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <button type="button" @click="transferDropdownOpen = !transferDropdownOpen"
                                            :class="transferErrors.transfer_type ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="selectedTransferType ? selectedTransferType.name : 'Select Type'"
                                                :class="!selectedTransferType ? 'text-gray-400' : 'text-gray-900'"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="transferDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        
                                        <div x-show="transferDropdownOpen" @click.away="transferDropdownOpen = false" x-cloak
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                            <div class="py-1">
                                                <template x-for="option in transferTypeOptions" :key="option.value">
                                                    <button type="button" @click="selectTransferType(option)"
                                                        :class="transferType === option.value ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                        class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                        x-text="option.name">
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <template x-if="transferErrors.transfer_type">
                                        <p class="mt-1 text-xs text-red-600" x-text="transferErrors.transfer_type[0]"></p>
                                    </template>
                                </div>
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Amount <span class="text-red-600">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                    <input type="text" x-model="transferAmount"
                                        @input="transferAmount = transferAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                        :class="transferErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                        placeholder="0">
                                </div>
                                <template x-if="transferErrors.amount">
                                    <p class="mt-1 text-xs text-red-600" x-text="transferErrors.amount[0]"></p>
                                </template>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea x-model="transferNotes" rows="3"
                                    class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                    placeholder="Optional transfer notes..."></textarea>
                            </div>

                            {{-- Proof Image - OPEN CAM ONLY --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proof of Payment</label>
                                
                                {{-- Webcam Section --}}
                                <div x-show="showWebcam" class="mb-3">
                                    <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                        <video x-ref="transferVideo" autoplay playsinline 
                                            :class="{ 'scale-x-[-1]': isMirrored }"
                                            class="w-full h-full object-cover"></video>
                                        <canvas x-ref="transferCanvas" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="captureTransferPhoto()"
                                        class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Capture
                                        </button>
                                        <button type="button" @click="toggleTransferCamera()"
                                        class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="stopTransferWebcam()"
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
                                        <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=transfer_proof_image]').value = ''; startTransferWebcam()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=transfer_proof_image]').value = ''"
                                            class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera Button --}}
                                <div x-show="!imagePreview && !showWebcam">
                                    <button type="button" @click="startTransferWebcam()"
                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera
                                    </button>
                                </div>
                                <input type="file" name="transfer_proof_image" accept="image/*" class="hidden">
                            </div>

                            </div>
                            {{-- End: Content shown only after Balance Period is selected --}}
                        </div>

                        {{-- Submit Button --}}
                        <div class="mt-6 flex gap-3">
                            <button type="button" @click="showAddTransferModal = false; stopTransferWebcam()"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingTransfer"
                                :class="isSubmittingTransfer ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                                class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <template x-if="isSubmittingTransfer">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <span x-text="isSubmittingTransfer ? 'Processing...' : 'Create Transfer'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End Add Transfer Modal --}}

    </div>
    {{-- End Root Alpine State --}}

    {{-- ================= IMAGE MODAL ================= --}}
    <div x-data="{
        showImageModal: false,
        selectedImage: '',
        init() {
            this.$watch('showImageModal', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        }
    }" x-show="showImageModal" x-cloak
        class="fixed inset-0 z-50">
        
        {{-- Background Overlay --}}
        <div x-show="showImageModal" @click="showImageModal = false; selectedImage = ''" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
        
        {{-- Modal Panel --}}
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.stop class="relative max-w-3xl w-full flex justify-center items-center" style="max-height: calc(100vh - 6rem);">
                <button @click="showImageModal = false; selectedImage = ''" class="absolute -top-10 right-0 text-white hover:text-gray-300 z-10">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img :src="selectedImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" style="max-height: calc(100vh - 10rem);" alt="Transfer proof">
            </div>
        </div>
    </div>
@endsection
