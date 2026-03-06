@extends('layouts.app')

@section('title', 'Loan Capital')

@section('content')
    <x-nav-locate :items="['Finance', 'Loan Capital']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        currentMonth: {{ $currentDate->month }},
        currentYear: {{ $currentDate->year }},
        displayText: '{{ $currentDate->format('F Y') }}',
        searchQuery: '{{ request('search', '') }}',
        statusFilter: '{{ $statusFilter }}',
        showCreateModal: false,
        createErrors: {},
        isSubmittingLoan: false,
        showRepaymentModal: false,
        repaymentErrors: {},
        isSubmittingRepayment: false,
        repaymentLoanId: null,
        repaymentLoanAmount: 0,
        repaymentRemaining: 0,
        repaymentLoanPeriod: '',
        showDetailModal: false,
        detailLoan: null,
        stream: null,
        showWebcam: false,
        imagePreview: null,
        fileName: '',
        isMirrored: false,
        facingMode: 'environment',

        init() {
            @if(session('toast_message'))
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { 
                            message: '{{ session('toast_message') }}',
                            type: '{{ session('toast_type', 'success') }}'
                        }
                    }));
                }, 300);
            @endif

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

            const url = '{{ route('finance.loan-capital') }}?' + params.toString();
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

                const newStats = doc.getElementById('stats-section');
                const newTable = doc.getElementById('table-section');

                if (newStats) document.getElementById('stats-section').innerHTML = newStats.innerHTML;
                if (newTable) document.getElementById('table-section').innerHTML = newTable.innerHTML;

                this.reinitPagination();
                NProgress.done();
            })
            .catch(error => { console.error('Error:', error); NProgress.done(); });
        },

        loadStatus(status) {
            this.statusFilter = status;

            const params = new URLSearchParams(window.location.search);
            params.set('status', status);
            params.set('month', this.currentMonth);
            params.set('year', this.currentYear);
            params.delete('page');

            const url = '{{ route('finance.loan-capital') }}?' + params.toString();
            window.history.pushState({}, '', url);

            NProgress.start();
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newTable = doc.getElementById('table-section');
                if (newTable) document.getElementById('table-section').innerHTML = newTable.innerHTML;

                this.reinitPagination();
                NProgress.done();
            })
            .catch(error => { console.error('Error:', error); NProgress.done(); });
        },

        matchesSearch(row) {
            const query = this.searchQuery.toLowerCase();
            if (!query || query.trim() === '') return true;
            const period = (row.getAttribute('data-period') || '').toLowerCase();
            const notes = (row.getAttribute('data-notes') || '').toLowerCase();
            const method = (row.getAttribute('data-method') || '').toLowerCase();
            const amount = (row.getAttribute('data-amount') || '').toLowerCase();
            return period.includes(query) || notes.includes(query) || method.includes(query) || amount.includes(query);
        },

        get hasVisibleRows() {
            if (!this.searchQuery || this.searchQuery.trim() === '') return true;
            const tbody = document.querySelector('#table-section tbody');
            if (!tbody) return true;
            const rows = tbody.querySelectorAll('tr[data-period]');
            for (let row of rows) {
                if (this.matchesSearch(row)) return true;
            }
            return false;
        },

        // Webcam functions
        async startLoanWebcam() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Webcam tidak didukung di browser ini.');
                return;
            }
            const isSecure = window.location.protocol === 'https:' || 
                           window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1';
            if (!isSecure) {
                alert('WEBCAM HARUS PAKAI HTTPS!');
                return;
            }
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: this.facingMode, width: { ideal: 1280 }, height: { ideal: 720 } } 
                });
                this.$refs.loanVideo.srcObject = this.stream;
                this.showWebcam = true;
            } catch (err) {
                console.error('Webcam error:', err);
                alert('Tidak dapat mengakses webcam. ' + err.message);
            }
        },

        async toggleLoanCamera() {
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.isMirrored = this.facingMode === 'user';
            if (this.stream) this.stream.getTracks().forEach(track => track.stop());
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: this.facingMode, width: { ideal: 1280 }, height: { ideal: 720 } } 
                });
                this.$refs.loanVideo.srcObject = this.stream;
            } catch (err) {
                alert('Gagal mengganti kamera. ' + err.message);
            }
        },

        stopLoanWebcam() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.showWebcam = false;
        },

        captureLoanPhoto() {
            const video = this.$refs.loanVideo;
            const canvas = this.$refs.loanCanvas;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored) { context.translate(canvas.width, 0); context.scale(-1, 1); }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                const fileInput = document.querySelector('input[name=loan_proof_image]');
                if (fileInput) { fileInput.value = ''; fileInput.files = dataTransfer.files; }
                this.imagePreview = canvas.toDataURL('image/jpeg');
                this.fileName = file.name;
                this.stopLoanWebcam();
            }, 'image/jpeg', 0.95);
        },

reinitPagination() {
            this.$nextTick(() => {
                setupLoanPagination('loans-pagination-container', 'table-section', this);
            });
        }
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

        {{-- Statistics Cards --}}
        <div id="stats-section" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            {{-- Total Balance --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Balance</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($totalBalance, 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Outstanding (All Time) --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Outstanding <span class="text-[10px] text-gray-400">(All Time)</span></p>
                        <p class="text-2xl font-bold text-red-600 mt-1">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Data Table Section --}}
        <div id="table-section" class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
            {{-- Header --}}
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-4">
                {{-- Left: Filter Buttons --}}
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadStatus('all')"
                        :class="statusFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer">
                        All
                    </button>
                    <button type="button" @click="loadStatus('outstanding')"
                        :class="statusFilter === 'outstanding' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer">
                        Outstanding
                    </button>
                    <button type="button" @click="loadStatus('paid_off')"
                        :class="statusFilter === 'paid_off' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer">
                        Paid Off
                    </button>
                </div>

                {{-- Right: Search + Per Page + Button --}}
                <div class="flex gap-2 items-center xl:min-w-0">
                    {{-- Search Box --}}
                    <div class="flex-1 xl:min-w-[240px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="searchQuery" placeholder="Search notes, method..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
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
                            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.getElementById('table-section');
                                if (newSection) document.getElementById('table-section').innerHTML = newSection.innerHTML;
                                NProgress.done();
                            })
                            .catch(error => { console.error('Error:', error); NProgress.done(); });
                        }
                    }" class="relative flex-shrink-0">
                        <button type="button" @click="open = !open"
                            class="w-15 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors cursor-pointer">
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

                    {{-- Button + New Loan --}}
                    <button type="button" @click="showCreateModal = true; createErrors = {};"
                        class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-md transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Loan
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-primary-light text-gray-600">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold rounded-l-lg">Balance Period</th>
                            <th class="py-3 px-4 text-left font-bold">Payment Method</th>
                            <th class="py-3 px-4 text-left font-bold">Loan Amount</th>
                            <th class="py-3 px-4 text-left font-bold">Paid</th>
                            <th class="py-3 px-4 text-left font-bold">Remaining</th>
                            <th class="py-3 px-4 text-left font-bold">Attachment</th>
                            <th class="py-3 px-4 text-left font-bold">Status</th>
                            <th class="py-3 px-4 text-left font-bold">Date</th>
                            <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody x-data="{ openPrimaryMenu: null }">
                        @forelse($loans as $loan)
                            @php
                                $balancePeriod = $loan->balance ? $loan->balance->period_start->format('F Y') : '-';
                                $repayments = $loan->repayments;
                                $totalRepaid = $repayments->sum('amount');
                                $remaining = $loan->loan_amount - $totalRepaid;
                                $rowId = $loan->id;
                            @endphp
                            <tr data-period="{{ $balancePeriod }}"
                                data-notes="{{ $loan->notes }}"
                                data-method="{{ $loan->payment_method }}"
                                data-amount="{{ $loan->loan_amount }}"
                                x-show="matchesSearch($el)"
                                class="hover:bg-gray-50 border-b border-gray-100">

                                {{-- Balance Period --}}
                                <td class="py-3 px-4 text-[12px] font-medium text-gray-900">
                                    {{ $balancePeriod }}
                                </td>

                                {{-- Payment Method --}}
                                <td class="py-3 px-4 text-[12px]">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold 
                                        {{ $loan->payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ strtoupper($loan->payment_method) }}
                                    </span>
                                </td>

                                {{-- Loan Amount --}}
                                <td class="py-3 px-4 text-[12px] text-gray-900 font-semibold">
                                    Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}
                                </td>

                                {{-- Paid --}}
                                <td class="py-3 px-4 text-[12px] text-green-600 font-semibold">
                                    Rp {{ number_format($totalRepaid, 0, ',', '.') }}
                                </td>

                                {{-- Remaining --}}
                                <td class="py-3 px-4 text-[12px] font-semibold {{ $remaining > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </td>

                                {{-- Attachment --}}
                                <td class="py-3 px-4 text-[12px]" @click.stop>
                                    @if($loan->proof_img)
                                        <button @click="$dispatch('open-image-modal', { url: '{{ route('finance.loan-capital.serve-image', $loan->id) }}?t={{ $loan->updated_at->timestamp }}' })"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            View
                                        </button>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-3 px-4">
                                    @if($loan->status === 'outstanding')
                                        <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-800">OUTSTANDING</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-green-100 text-green-800">PAID OFF</span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="py-3 px-4 text-[12px] text-gray-700">
                                    {{ $loan->loan_date->format('d M Y') }}
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
                                                this.dropdownStyle = { position: 'fixed', top: (rect.top - 120) + 'px', left: (rect.right - 160) + 'px', width: '180px' }; 
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
                                            window.dispatchEvent(new CustomEvent('close-all-primary-menus'));
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
                                            {{-- Show Detail --}}
                                            <button type="button" @click="open = false; showDetailModal = true; detailLoan = {
                                                id: {{ $loan->id }},
                                                balance_period: '{{ $balancePeriod }}',
                                                payment_method: '{{ $loan->payment_method }}',
                                                loan_amount: {{ $loan->loan_amount }},
                                                total_repaid: {{ $totalRepaid }},
                                                remaining: {{ $remaining }},
                                                status: '{{ $loan->status }}',
                                                loan_date: '{{ $loan->loan_date->format('d M Y') }}',
                                                notes: {{ json_encode($loan->notes ?: '-') }},
                                                proof_img: {{ $loan->proof_img ? "'" . route('finance.loan-capital.serve-image', $loan->id) . '?t=' . $loan->updated_at->timestamp . "'" : 'null' }},
                                                repayments: [
                                                    @foreach($repayments as $rep)
                                                    {
                                                        balance_period: '{{ $rep->balance ? $rep->balance->period_start->format('F Y') : '-' }}',
                                                        payment_method: '{{ $rep->payment_method }}',
                                                        amount: {{ $rep->amount }},
                                                        paid_date: '{{ $rep->paid_date->format('d M Y') }}',
                                                        proof_img: {{ $rep->proof_img ? "'" . route('finance.loan-capital.serve-repayment-image', $rep->id) . '?t=' . $rep->updated_at->timestamp . "'" : 'null' }},
                                                        notes: {{ json_encode($rep->notes ?: '-') }}
                                                    },
                                                    @endforeach
                                                ]
                                            }"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 cursor-pointer">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Show Detail
                                            </button>
                                            {{-- Repayment (only if outstanding) --}}
                                            @if($loan->status === 'outstanding')
                                            <button type="button" @click="open = false; repaymentLoanId = {{ $loan->id }}; repaymentLoanAmount = {{ $loan->loan_amount }}; repaymentRemaining = {{ $remaining }}; repaymentLoanPeriod = '{{ $balancePeriod }}'; showRepaymentModal = true;"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 cursor-pointer">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Repayment
                                            </button>
                                            @endif
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm text-gray-500">No loan data found for this period</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- No Results (search) --}}
            <div x-show="searchQuery && !hasVisibleRows" x-cloak class="py-12 text-center">
                <p class="text-sm text-gray-500">No results found for "<span x-text="searchQuery" class="font-medium"></span>"</p>
            </div>

            {{-- Pagination Component (always visible like Material Report) --}}
            <div id="loans-pagination-container" class="mt-4">
                <x-custom-pagination :paginator="$loans" />
            </div>
        </div>

        {{-- ==================== NEW LOAN MODAL ==================== --}}
        <div x-show="showCreateModal" x-cloak
            @keydown.escape.window="showCreateModal = false; stopLoanWebcam()"
            x-data="{
                balanceMonth: null,
                balanceYear: null,
                balanceId: null,
                balanceTransfer: 0,
                balanceCash: 0,
                periodValidated: false,
                periodError: '',
                isValidatingPeriod: false,
                loanDate: '',
                loanPaymentMethod: '',
                loanAmount: '',
                loanNotes: '',
                monthDropdownOpen: false,
                yearDropdownOpen: false,
                paymentMethodDropdownOpen: false,
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
                yearOptions: Array.from({length: 5}, (_, i) => new Date().getFullYear() - 2 + i),
                paymentMethodOptions: [
                    { value: 'cash', name: 'Cash' },
                    { value: 'transfer', name: 'Transfer' }
                ],
                get selectedMonthName() {
                    const month = this.balanceMonthOptions.find(m => m.value === this.balanceMonth);
                    return month ? month.name : null;
                },
                get selectedPaymentMethod() {
                    return this.paymentMethodOptions.find(o => o.value === this.loanPaymentMethod) || null;
                },
                setToday() {
                    const now = new Date();
                    this.balanceMonth = now.getMonth() + 1;
                    this.balanceYear = now.getFullYear();
                },
                async validatePeriod() {
                    if (!this.balanceMonth || !this.balanceYear) return;
                    
                    this.isValidatingPeriod = true;
                    this.periodError = '';
                    this.balanceId = null;
                    this.balanceTransfer = 0;
                    this.balanceCash = 0;
                    
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
                        
                        this.periodValidated = true;
                        this.isValidatingPeriod = false;
                    } catch (error) {
                        console.error('Error fetching balance:', error);
                        this.periodError = 'Failed to fetch balance data. Please try again.';
                        this.isValidatingPeriod = false;
                    }
                },
                goBackToStep1() {
                    this.periodValidated = false;
                    this.periodError = '';
                },
                selectPaymentMethod(option) {
                    this.loanPaymentMethod = option.value;
                    this.paymentMethodDropdownOpen = false;
                }
            }"
            x-init="
                $watch('showCreateModal', value => {
                    if (value) {
                        balanceMonth = null;
                        balanceYear = null;
                        balanceId = null;
                        balanceTransfer = 0;
                        balanceCash = 0;
                        periodValidated = false;
                        periodError = '';
                        isValidatingPeriod = false;
                        loanDate = new Date().toISOString().slice(0, 10);
                        loanPaymentMethod = '';
                        loanAmount = '';
                        loanNotes = '';
                        createErrors = {};
                        isSubmittingLoan = false;
                        imagePreview = null;
                        fileName = '';
                        showWebcam = false;
                        stopLoanWebcam();
                        const fileInput = document.querySelector('input[name=loan_proof_image]');
                        if (fileInput) fileInput.value = '';
                    }
                })
            "
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">
            
            {{-- Background Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showCreateModal = false; stopLoanWebcam()" 
                     class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                    
                    {{-- Modal Header - Sticky --}}
                    <div class="sticky top-0 z-10 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">New Loan Capital</h3>
                        <button @click="showCreateModal = false; stopLoanWebcam()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                            ✕
                        </button>
                    </div>

                    {{-- Step 1: Period Selection (non-scrollable, above modal body) --}}
                    <div x-show="!periodValidated" class="px-6 py-5 border-b border-gray-200 flex-shrink-0">
                        <div class="text-center mb-5">
                            <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h4 class="text-base font-semibold text-gray-900">Select Balance Period</h4>
                            <p class="text-sm text-gray-500 mt-1">Choose the balance period for this loan</p>
                        </div>

                        <div class="space-y-4">
                            {{-- Month & Year Select --}}
                            <div class="grid grid-cols-2 gap-3">
                                {{-- Month --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                    <div class="relative">
                                        <button type="button" @click="monthDropdownOpen = !monthDropdownOpen"
                                            class="w-full flex justify-between items-center rounded-md border border-gray-200 px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors cursor-pointer">
                                            <span x-text="selectedMonthName || 'Select Month'"
                                                :class="!selectedMonthName ? 'text-gray-400' : 'text-gray-900'"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="monthDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="monthDropdownOpen" @click.away="monthDropdownOpen = false" x-cloak
                                            x-transition
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            <div class="py-1">
                                                <template x-for="m in balanceMonthOptions" :key="m.value">
                                                    <button type="button" @click="balanceMonth = m.value; monthDropdownOpen = false"
                                                        :class="balanceMonth === m.value ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                        class="w-full text-left px-4 py-2 text-sm transition-colors cursor-pointer"
                                                        x-text="m.name">
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Year --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                                    <div class="relative">
                                        <button type="button" @click="yearDropdownOpen = !yearDropdownOpen"
                                            class="w-full flex justify-between items-center rounded-md border border-gray-200 px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors cursor-pointer">
                                            <span x-text="balanceYear || 'Select Year'"
                                                :class="!balanceYear ? 'text-gray-400' : 'text-gray-900'"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="yearDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="yearDropdownOpen" @click.away="yearDropdownOpen = false" x-cloak
                                            x-transition
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                            <div class="py-1">
                                                <template x-for="y in yearOptions" :key="y">
                                                    <button type="button" @click="balanceYear = y; yearDropdownOpen = false"
                                                        :class="balanceYear === y ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                        class="w-full text-left px-4 py-2 text-sm transition-colors cursor-pointer"
                                                        x-text="y">
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Today Button --}}
                            <button type="button" @click="setToday()"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2 border border-primary text-primary hover:bg-primary/5 rounded-lg text-sm font-medium transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Today
                            </button>

                            {{-- Period Error --}}
                            <template x-if="periodError">
                                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm text-red-700 font-medium" x-text="periodError"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Modal Body (only visible in Step 2) --}}
                    <div x-show="periodValidated" class="flex-1 overflow-y-auto px-6 py-6">
                        <form id="addLoanForm" x-ref="addLoanForm" @submit.prevent="
                            createErrors = {};
                            let hasError = false;

                            if (!loanPaymentMethod) {
                                createErrors.payment_method = ['Payment method is required'];
                                hasError = true;
                            }

                            const amountValue = loanAmount.replace(/[^0-9]/g, '');
                            if (!amountValue || parseInt(amountValue) < 1) {
                                createErrors.amount = ['Amount is required and must be at least Rp 1'];
                                hasError = true;
                            }

                            if (!imagePreview || !fileName) {
                                createErrors.proof_image = ['Proof image is required'];
                                hasError = true;
                            }

                            if (hasError) return;

                            isSubmittingLoan = true;
                            const formData = new FormData();
                            formData.append('_token', '{{ csrf_token() }}');
                            formData.append('balance_month', balanceMonth);
                            formData.append('balance_year', balanceYear);
                            formData.append('loan_date', loanDate);
                            formData.append('payment_method', loanPaymentMethod);
                            formData.append('amount', amountValue);
                            formData.append('notes', loanNotes || '');

                            const fileInput = document.querySelector('input[name=loan_proof_image]');
                            if (fileInput && fileInput.files[0]) {
                                formData.append('image', fileInput.files[0]);
                            }

                            fetch('{{ route('finance.loan-capital.store') }}', {
                                method: 'POST',
                                body: formData,
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                            })
                            .then(async res => {
                                const data = await res.json();
                                return { status: res.status, ok: res.ok, data };
                            })
                            .then(({ status, ok, data }) => {
                                if (ok && data.success) {
                                    sessionStorage.setItem('toast_message', data.message || 'Loan added successfully');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else if (status === 422) {
                                    isSubmittingLoan = false;
                                    createErrors = data.errors || {};
                                } else {
                                    isSubmittingLoan = false;
                                    createErrors = data.errors || {};
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingLoan = false;
                                createErrors = { amount: ['Network error. Please try again.'] };
                            });
                        ">
                            <div class="space-y-5">

                                {{-- ====== STEP 2: Full Form (after period validated) ====== --}}
                                <div x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100">
                                    
                                    <div class="space-y-4">
                                        {{-- Balance Period Card --}}
                                        <div class="p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Balance Period</label>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-primary/40 text-primary font-semibold text-sm">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        <span x-text="selectedMonthName + ' ' + balanceYear"></span>
                                                    </span>
                                                </div>
                                                <button type="button" @click="goBackToStep1()"
                                                    class="text-sm text-primary hover:text-primary-dark font-medium flex items-center gap-1 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                    Change
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Balance Cards --}}
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                                <p class="text-xs text-blue-600 font-medium mb-1">BCA Balance</p>
                                                <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(balanceTransfer).toLocaleString('id-ID')"></p>
                                            </div>
                                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                                <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                                <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(balanceCash).toLocaleString('id-ID')"></p>
                                            </div>
                                        </div>

                                        {{-- Loan Date --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Loan Date <span class="text-red-600">*</span>
                                            </label>
                                            <input type="date" x-model="loanDate"
                                                class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors">
                                        </div>

                                        {{-- Payment Method --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Payment Method <span class="text-red-600">*</span>
                                            </label>
                                            <div class="relative">
                                                <button type="button" @click="paymentMethodDropdownOpen = !paymentMethodDropdownOpen"
                                                    :class="createErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors cursor-pointer">
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
                                                                :class="loanPaymentMethod === method.value ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                class="w-full text-left px-4 py-2 text-sm transition-colors cursor-pointer"
                                                                x-text="method.name">
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <template x-if="createErrors.payment_method">
                                                <p class="mt-1 text-xs text-red-600" x-text="createErrors.payment_method[0]"></p>
                                            </template>
                                        </div>

                                        {{-- Loan Amount --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Loan Amount <span class="text-red-600">*</span>
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                                <input type="text" x-model="loanAmount"
                                                    @input="loanAmount = loanAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                    :class="createErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                    placeholder="0">
                                            </div>
                                            <template x-if="createErrors.amount">
                                                <p class="mt-1 text-xs text-red-600" x-text="createErrors.amount[0]"></p>
                                            </template>
                                        </div>

                                        {{-- Proof of Payment - Webcam (Material Report style) --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Proof of Payment <span class="text-red-600">*</span>
                                            </label>
                                            
                                            {{-- Webcam Section --}}
                                            <div x-show="showWebcam" class="mb-3">
                                                <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                                    <video x-ref="loanVideo" autoplay playsinline 
                                                        :class="{ 'scale-x-[-1]': isMirrored }"
                                                        class="w-full h-full object-cover"></video>
                                                    <canvas x-ref="loanCanvas" class="hidden"></canvas>
                                                </div>
                                                <div class="flex gap-2 mt-3">
                                                    <button type="button" @click="captureLoanPhoto()"
                                                    class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2 cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        Capture
                                                    </button>
                                                    <button type="button" @click="toggleLoanCamera()"
                                                    class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="stopLoanWebcam()"
                                                    class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1 cursor-pointer">
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
                                                    <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=loan_proof_image]').value = ''; startLoanWebcam()"
                                                        class="text-blue-600 hover:text-blue-700 p-1 cursor-pointer" title="Retake photo">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=loan_proof_image]').value = ''"
                                                        class="text-red-600 hover:text-red-700 p-1 cursor-pointer" title="Delete photo">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Open Camera Button --}}
                                            <div x-show="!imagePreview && !showWebcam">
                                                <button type="button" @click="startLoanWebcam()"
                                                class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700 cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Open Camera
                                                </button>
                                            </div>
                                            <input type="file" name="loan_proof_image" accept="image/*" class="hidden">
                                            <template x-if="createErrors.proof_image">
                                                <p class="mt-1 text-xs text-red-600" x-text="createErrors.proof_image[0]"></p>
                                            </template>
                                        </div>

                                        {{-- Notes --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                            <textarea x-model="loanNotes" rows="3"
                                                class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                                placeholder="Optional notes..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                {{-- End Step 2 --}}
                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer - Sticky --}}
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                        <button type="button" @click="showCreateModal = false; stopLoanWebcam()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors cursor-pointer">
                            Cancel
                        </button>
                        {{-- Step 1: Continue Button --}}
                        <button x-show="!periodValidated" type="button" @click="validatePeriod()"
                            :disabled="!balanceMonth || !balanceYear || isValidatingPeriod"
                            :class="(!balanceMonth || !balanceYear || isValidatingPeriod) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark cursor-pointer'"
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <template x-if="isValidatingPeriod">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="isValidatingPeriod ? 'Loading...' : 'Continue'"></span>
                        </button>
                        {{-- Step 2: Save Button --}}
                        <button x-show="periodValidated" type="submit" form="addLoanForm" :disabled="isSubmittingLoan"
                            :class="isSubmittingLoan ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark cursor-pointer'"
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <template x-if="isSubmittingLoan">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="isSubmittingLoan ? 'Processing...' : 'Save Loan'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== REPAYMENT MODAL (2-Step like New Loan) ==================== --}}
    <div x-show="showRepaymentModal" x-cloak
        @keydown.escape.window="showRepaymentModal = false; stopRpWebcam()"
        x-data="{
            rpBalanceMonth: null,
            rpBalanceYear: null,
            rpBalanceId: null,
            rpBalanceTransfer: 0,
            rpBalanceCash: 0,
            rpPeriodValidated: false,
            rpPeriodError: '',
            rpIsValidating: false,
            rpPaidDate: '',
            rpPaymentMethod: '',
            rpAmount: '',
            rpNotes: '',
            rpMonthDropdownOpen: false,
            rpYearDropdownOpen: false,
            rpPaymentMethodDropdownOpen: false,
            rpBalanceMonthOptions: [
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
            rpYearOptions: Array.from({length: 5}, (_, i) => new Date().getFullYear() - 2 + i),
            rpPaymentMethodOptions: [
                { value: 'cash', name: 'Cash' },
                { value: 'transfer', name: 'Transfer' }
            ],
            get rpSelectedMonthName() {
                const month = this.rpBalanceMonthOptions.find(m => m.value === this.rpBalanceMonth);
                return month ? month.name : null;
            },
            get rpSelectedPaymentMethod() {
                return this.rpPaymentMethodOptions.find(o => o.value === this.rpPaymentMethod) || null;
            },
            rpSetToday() {
                const now = new Date();
                this.rpBalanceMonth = now.getMonth() + 1;
                this.rpBalanceYear = now.getFullYear();
            },
            async rpValidatePeriod() {
                if (!this.rpBalanceMonth || !this.rpBalanceYear) return;
                
                this.rpIsValidating = true;
                this.rpPeriodError = '';
                this.rpBalanceId = null;
                this.rpBalanceTransfer = 0;
                this.rpBalanceCash = 0;
                
                try {
                    const response = await fetch(`/finance/balance/find-by-period?month=${this.rpBalanceMonth}&year=${this.rpBalanceYear}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    
                    if (data.success && data.balance) {
                        this.rpBalanceId = data.balance.id;
                        this.rpBalanceTransfer = data.balance.transfer_balance;
                        this.rpBalanceCash = data.balance.cash_balance;
                    } else {
                        this.rpBalanceId = null;
                        this.rpBalanceTransfer = 0;
                        this.rpBalanceCash = 0;
                    }
                    
                    this.rpPeriodValidated = true;
                    this.rpIsValidating = false;
                } catch (error) {
                    console.error('Error fetching balance:', error);
                    this.rpPeriodError = 'Failed to fetch balance data. Please try again.';
                    this.rpIsValidating = false;
                }
            },
            rpGoBackToStep1() {
                this.rpPeriodValidated = false;
                this.rpPeriodError = '';
            },
            rpSelectPaymentMethod(option) {
                this.rpPaymentMethod = option.value;
                this.rpPaymentMethodDropdownOpen = false;
            },
            rpStream: null,
            rpShowWebcam: false,
            rpImagePreview: null,
            rpFileName: '',
            rpIsMirrored: false,
            rpFacingMode: 'environment',
            async startRpWebcam() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Webcam tidak didukung di browser ini.');
                    return;
                }
                const isSecure = window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                if (!isSecure) { alert('WEBCAM HARUS PAKAI HTTPS!'); return; }
                try {
                    this.rpStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: this.rpFacingMode, width: { ideal: 1280 }, height: { ideal: 720 } } });
                    this.$refs.rpVideo.srcObject = this.rpStream;
                    this.rpShowWebcam = true;
                } catch (err) { console.error('Webcam error:', err); alert('Tidak dapat mengakses webcam. ' + err.message); }
            },
            async toggleRpCamera() {
                this.rpFacingMode = this.rpFacingMode === 'user' ? 'environment' : 'user';
                this.rpIsMirrored = this.rpFacingMode === 'user';
                if (this.rpStream) this.rpStream.getTracks().forEach(track => track.stop());
                try {
                    this.rpStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: this.rpFacingMode, width: { ideal: 1280 }, height: { ideal: 720 } } });
                    this.$refs.rpVideo.srcObject = this.rpStream;
                } catch (err) { alert('Gagal mengganti kamera. ' + err.message); }
            },
            stopRpWebcam() {
                if (this.rpStream) { this.rpStream.getTracks().forEach(track => track.stop()); this.rpStream = null; }
                this.rpShowWebcam = false;
            },
            captureRpPhoto() {
                const video = this.$refs.rpVideo;
                const canvas = this.$refs.rpCanvas;
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                if (this.rpIsMirrored) { context.translate(canvas.width, 0); context.scale(-1, 1); }
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.toBlob((blob) => {
                    const file = new File([blob], 'webcam_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    const fileInput = document.querySelector('input[name=repayment_proof_image]');
                    if (fileInput) { fileInput.value = ''; fileInput.files = dataTransfer.files; }
                    this.rpImagePreview = canvas.toDataURL('image/jpeg');
                    this.rpFileName = file.name;
                    this.stopRpWebcam();
                }, 'image/jpeg', 0.95);
            }
        }"
        x-init="
            $watch('showRepaymentModal', value => {
                if (value) {
                    rpBalanceMonth = null;
                    rpBalanceYear = null;
                    rpBalanceId = null;
                    rpBalanceTransfer = 0;
                    rpBalanceCash = 0;
                    rpPeriodValidated = false;
                    rpPeriodError = '';
                    rpIsValidating = false;
                    rpPaidDate = new Date().toISOString().slice(0, 10);
                    rpPaymentMethod = '';
                    rpAmount = '';
                    rpNotes = '';
                    repaymentErrors = {};
                    isSubmittingRepayment = false;
                    rpImagePreview = null;
                    rpFileName = '';
                    rpShowWebcam = false;
                    stopRpWebcam();
                    const fileInput = document.querySelector('input[name=repayment_proof_image]');
                    if (fileInput) fileInput.value = '';
                }
            })
        "
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        
        {{-- Background Overlay --}}
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
        
        {{-- Modal Panel --}}
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.away="showRepaymentModal = false; stopRpWebcam()" 
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                
                {{-- Modal Header --}}
                <div class="sticky top-0 z-10 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Loan Repayment</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Loan: <span x-text="repaymentLoanPeriod" class="font-medium"></span> &mdash; Remaining: <span class="text-red-600 font-semibold" x-text="'Rp ' + parseInt(repaymentRemaining).toLocaleString('id-ID')"></span></p>
                    </div>
                    <button @click="showRepaymentModal = false; stopRpWebcam()" type="button"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                        ✕
                    </button>
                </div>

                {{-- Step 1: Period Selection (non-scrollable) --}}
                <div x-show="!rpPeriodValidated" class="px-6 py-5 border-b border-gray-200 flex-shrink-0">
                    <div class="text-center mb-5">
                        <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h4 class="text-base font-semibold text-gray-900">Select Balance Period</h4>
                        <p class="text-sm text-gray-500 mt-1">Choose which balance to use for repayment</p>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Month --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                <div class="relative">
                                    <button type="button" @click="rpMonthDropdownOpen = !rpMonthDropdownOpen"
                                        class="w-full flex justify-between items-center rounded-md border border-gray-200 px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors cursor-pointer">
                                        <span x-text="rpSelectedMonthName || 'Select Month'"
                                            :class="!rpSelectedMonthName ? 'text-gray-400' : 'text-gray-900'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="rpMonthDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div x-show="rpMonthDropdownOpen" @click.away="rpMonthDropdownOpen = false" x-cloak
                                        x-transition
                                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                        <div class="py-1">
                                            <template x-for="m in rpBalanceMonthOptions" :key="m.value">
                                                <button type="button" @click="rpBalanceMonth = m.value; rpMonthDropdownOpen = false"
                                                    :class="rpBalanceMonth === m.value ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                    class="w-full text-left px-4 py-2 text-sm transition-colors cursor-pointer"
                                                    x-text="m.name">
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Year --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                                <div class="relative">
                                    <button type="button" @click="rpYearDropdownOpen = !rpYearDropdownOpen"
                                        class="w-full flex justify-between items-center rounded-md border border-gray-200 px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors cursor-pointer">
                                        <span x-text="rpBalanceYear || 'Select Year'"
                                            :class="!rpBalanceYear ? 'text-gray-400' : 'text-gray-900'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="rpYearDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div x-show="rpYearDropdownOpen" @click.away="rpYearDropdownOpen = false" x-cloak
                                        x-transition
                                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                        <div class="py-1">
                                            <template x-for="y in rpYearOptions" :key="y">
                                                <button type="button" @click="rpBalanceYear = y; rpYearDropdownOpen = false"
                                                    :class="rpBalanceYear === y ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                    class="w-full text-left px-4 py-2 text-sm transition-colors cursor-pointer"
                                                    x-text="y">
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Today Button --}}
                        <button type="button" @click="rpSetToday()"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 border border-primary text-primary hover:bg-primary/5 rounded-lg text-sm font-medium transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Today
                        </button>

                        <template x-if="rpPeriodError">
                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm text-red-700 font-medium" x-text="rpPeriodError"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Step 2: Repayment Form (scrollable) --}}
                <div x-show="rpPeriodValidated" class="flex-1 overflow-y-auto px-6 py-6">
                    <form id="addRepaymentForm" x-ref="addRepaymentForm" @submit.prevent="
                        repaymentErrors = {};
                        let hasError = false;

                        if (!rpPaymentMethod) {
                            repaymentErrors.payment_method = ['Payment method is required'];
                            hasError = true;
                        }

                        const amountValue = rpAmount.replace(/[^0-9]/g, '');
                        if (!amountValue || parseInt(amountValue) < 1) {
                            repaymentErrors.amount = ['Amount is required and must be at least Rp 1'];
                            hasError = true;
                        } else if (parseInt(amountValue) > repaymentRemaining) {
                            repaymentErrors.amount = ['Amount cannot exceed remaining: Rp ' + parseInt(repaymentRemaining).toLocaleString('id-ID')];
                            hasError = true;
                        }

                        if (hasError) return;

                        isSubmittingRepayment = true;
                        const formData = new FormData();
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('balance_id', rpBalanceId);
                        formData.append('paid_date', rpPaidDate);
                        formData.append('payment_method', rpPaymentMethod);
                        formData.append('amount', amountValue);
                        formData.append('notes', rpNotes || '');

                        const fileInput = document.querySelector('input[name=repayment_proof_image]');
                        if (fileInput && fileInput.files[0]) {
                            formData.append('proof_image', fileInput.files[0]);
                        }

                        fetch('/finance/loan-capital/' + repaymentLoanId + '/repayment', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        })
                        .then(async res => {
                            const data = await res.json();
                            return { status: res.status, ok: res.ok, data };
                        })
                        .then(({ status, ok, data }) => {
                            if (ok && data.success) {
                                sessionStorage.setItem('toast_message', data.message || 'Repayment recorded successfully');
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
                            repaymentErrors = { amount: ['Network error. Please try again.'] };
                        });
                    ">
                        <div class="space-y-5">
                            <div x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100">
                                
                                <div class="space-y-4">
                                    {{-- Balance Period Card --}}
                                    <div class="p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-900 mb-2">Balance Period</label>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-primary/40 text-primary font-semibold text-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span x-text="rpSelectedMonthName + ' ' + rpBalanceYear"></span>
                                                </span>
                                            </div>
                                            <button type="button" @click="rpGoBackToStep1()"
                                                class="text-sm text-primary hover:text-primary-dark font-medium flex items-center gap-1 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                                </svg>
                                                Change
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Balance Cards --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                            <p class="text-xs text-blue-600 font-medium mb-1">BCA Balance</p>
                                            <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(rpBalanceTransfer).toLocaleString('id-ID')"></p>
                                        </div>
                                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                            <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                            <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(rpBalanceCash).toLocaleString('id-ID')"></p>
                                        </div>
                                    </div>

                                    {{-- Paid Date --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Paid Date <span class="text-red-600">*</span>
                                        </label>
                                        <input type="date" x-model="rpPaidDate"
                                            class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors">
                                    </div>

                                    {{-- Payment Method --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Payment Method <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <button type="button" @click="rpPaymentMethodDropdownOpen = !rpPaymentMethodDropdownOpen"
                                                :class="repaymentErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors cursor-pointer">
                                                <span x-text="rpSelectedPaymentMethod ? rpSelectedPaymentMethod.name : 'Select Payment Method'"
                                                    :class="!rpSelectedPaymentMethod ? 'text-gray-400' : 'text-gray-900'"></span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="rpPaymentMethodDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div x-show="rpPaymentMethodDropdownOpen" @click.away="rpPaymentMethodDropdownOpen = false" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                                <div class="py-1">
                                                    <template x-for="method in rpPaymentMethodOptions" :key="method.value">
                                                        <button type="button" @click="rpSelectPaymentMethod(method)"
                                                            :class="rpPaymentMethod === method.value ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2 text-sm transition-colors cursor-pointer"
                                                            x-text="method.name">
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="repaymentErrors.payment_method">
                                            <p class="mt-1 text-xs text-red-600" x-text="repaymentErrors.payment_method[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Amount --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Repayment Amount <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                            <input type="text" x-model="rpAmount"
                                                @input="rpAmount = rpAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                :class="repaymentErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                placeholder="0">
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Max: <span class="font-medium text-red-600" x-text="'Rp ' + parseInt(repaymentRemaining).toLocaleString('id-ID')"></span></p>
                                        <template x-if="repaymentErrors.amount">
                                            <p class="mt-1 text-xs text-red-600" x-text="repaymentErrors.amount[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Proof of Payment - Webcam --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Proof of Payment <span class="text-xs text-gray-400">(optional)</span>
                                        </label>
                                        
                                        {{-- Webcam Section --}}
                                        <div x-show="rpShowWebcam" class="mb-3">
                                            <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                                <video x-ref="rpVideo" autoplay playsinline 
                                                    :class="{ 'scale-x-[-1]': rpIsMirrored }"
                                                    class="w-full h-full object-cover"></video>
                                                <canvas x-ref="rpCanvas" class="hidden"></canvas>
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <button type="button" @click="captureRpPhoto()"
                                                class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Capture
                                                </button>
                                                <button type="button" @click="toggleRpCamera()"
                                                class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="stopRpWebcam()"
                                                class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Close
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Image Preview --}}
                                        <div x-show="rpImagePreview && !rpShowWebcam" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                            <div class="flex items-center gap-3">
                                                <img :src="rpImagePreview" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900" x-text="rpFileName"></p>
                                                    <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                                </div>
                                                <button type="button" @click="rpImagePreview = null; rpFileName = ''; document.querySelector('input[name=repayment_proof_image]').value = ''; startRpWebcam()"
                                                    class="text-blue-600 hover:text-blue-700 p-1 cursor-pointer" title="Retake photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="rpImagePreview = null; rpFileName = ''; document.querySelector('input[name=repayment_proof_image]').value = ''"
                                                    class="text-red-600 hover:text-red-700 p-1 cursor-pointer" title="Delete photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Open Camera Button --}}
                                        <div x-show="!rpImagePreview && !rpShowWebcam">
                                            <button type="button" @click="startRpWebcam()"
                                            class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700 cursor-pointer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Open Camera
                                            </button>
                                        </div>
                                        <input type="file" name="repayment_proof_image" accept="image/*" class="hidden">
                                        <template x-if="repaymentErrors.proof_image">
                                            <p class="mt-1 text-xs text-red-600" x-text="repaymentErrors.proof_image[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Notes --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                        <textarea x-model="rpNotes" rows="3"
                                            class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                            placeholder="Optional notes..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Modal Footer --}}
                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                    <button type="button" @click="showRepaymentModal = false; stopRpWebcam()"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors cursor-pointer">
                        Cancel
                    </button>
                    {{-- Step 1: Continue --}}
                    <button x-show="!rpPeriodValidated" type="button" @click="rpValidatePeriod()"
                        :disabled="!rpBalanceMonth || !rpBalanceYear || rpIsValidating"
                        :class="(!rpBalanceMonth || !rpBalanceYear || rpIsValidating) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark cursor-pointer'"
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <template x-if="rpIsValidating">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="rpIsValidating ? 'Loading...' : 'Continue'"></span>
                    </button>
                    {{-- Step 2: Save --}}
                    <button x-show="rpPeriodValidated" type="submit" form="addRepaymentForm" :disabled="isSubmittingRepayment"
                        :class="isSubmittingRepayment ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark cursor-pointer'"
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <template x-if="isSubmittingRepayment">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="isSubmittingRepayment ? 'Processing...' : 'Save Repayment'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== SHOW DETAIL MODAL ==================== --}}
    <div x-show="showDetailModal" x-cloak
        @keydown.escape.window="showDetailModal = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
        
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.away="showDetailModal = false" 
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                
                {{-- Header --}}
                <div class="sticky top-0 z-10 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Loan Detail</h3>
                    <button @click="showDetailModal = false" type="button"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                        ✕
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6" x-show="detailLoan">
                    <div class="space-y-5">
                        {{-- Loan Info Grid --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Balance Period</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="detailLoan?.balance_period"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Loan Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="detailLoan?.loan_date"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Payment Method</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold"
                                    :class="detailLoan?.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                    x-text="detailLoan?.payment_method?.toUpperCase()"></span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Status</p>
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold"
                                    :class="detailLoan?.status === 'outstanding' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                    x-text="detailLoan?.status === 'outstanding' ? 'OUTSTANDING' : 'PAID OFF'"></span>
                            </div>
                        </div>

                        {{-- Amount Cards --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-gray-50 rounded-xl p-3 border border-gray-200 text-center">
                                <p class="text-xs text-gray-500 mb-1">Loan Amount</p>
                                <p class="text-sm font-bold text-gray-900" x-text="'Rp ' + parseInt(detailLoan?.loan_amount || 0).toLocaleString('id-ID')"></p>
                            </div>
                            <div class="bg-green-50 rounded-xl p-3 border border-green-200 text-center">
                                <p class="text-xs text-green-600 mb-1">Total Paid</p>
                                <p class="text-sm font-bold text-green-700" x-text="'Rp ' + parseInt(detailLoan?.total_repaid || 0).toLocaleString('id-ID')"></p>
                            </div>
                            <div class="bg-red-50 rounded-xl p-3 border border-red-200 text-center">
                                <p class="text-xs text-red-600 mb-1">Remaining</p>
                                <p class="text-sm font-bold text-red-700" x-text="'Rp ' + parseInt(detailLoan?.remaining || 0).toLocaleString('id-ID')"></p>
                            </div>
                        </div>

                        {{-- Proof Image --}}
                        <template x-if="detailLoan?.proof_img">
                            <div>
                                <p class="text-xs text-gray-500 mb-2">Proof Image</p>
                                <img :src="detailLoan.proof_img" class="w-full max-h-48 object-contain rounded-lg border border-gray-200 cursor-pointer"
                                    @click="$dispatch('open-image-modal', { url: detailLoan.proof_img })">
                            </div>
                        </template>

                        {{-- Notes --}}
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Notes</p>
                            <p class="text-sm text-gray-700" x-text="detailLoan?.notes"></p>
                        </div>

                        {{-- Repayment History --}}
                        <template x-if="detailLoan?.repayments?.length > 0">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 mb-3">Repayment History</p>
                                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="py-2 px-3 text-left text-[10px] font-semibold text-gray-600">No</th>
                                                <th class="py-2 px-3 text-left text-[10px] font-semibold text-gray-600">From Balance</th>
                                                <th class="py-2 px-3 text-left text-[10px] font-semibold text-gray-600">Method</th>
                                                <th class="py-2 px-3 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                <th class="py-2 px-3 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                <th class="py-2 px-3 text-left text-[10px] font-semibold text-gray-600">Proof</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(rep, idx) in detailLoan.repayments" :key="idx">
                                                <tr class="border-t border-gray-100 hover:bg-gray-50">
                                                    <td class="py-2 px-3 text-[11px] text-gray-600" x-text="idx + 1"></td>
                                                    <td class="py-2 px-3 text-[11px] text-gray-900" x-text="rep.balance_period"></td>
                                                    <td class="py-2 px-3 text-[11px]">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                            :class="rep.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                                            x-text="rep.payment_method.toUpperCase()"></span>
                                                    </td>
                                                    <td class="py-2 px-3 text-[11px] text-gray-900 font-semibold" x-text="'Rp ' + parseInt(rep.amount).toLocaleString('id-ID')"></td>
                                                    <td class="py-2 px-3 text-[11px] text-gray-700" x-text="rep.paid_date"></td>
                                                    <td class="py-2 px-3 text-[11px]">
                                                        <template x-if="rep.proof_img">
                                                            <button @click="$dispatch('open-image-modal', { url: rep.proof_img })"
                                                                class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                                View
                                                            </button>
                                                        </template>
                                                        <template x-if="!rep.proof_img">
                                                            <span class="text-gray-400">-</span>
                                                        </template>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>

                        <template x-if="!detailLoan?.repayments?.length">
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-400">No repayment history yet</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                    <button type="button" @click="showDetailModal = false"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors cursor-pointer">
                        Close
                    </button>
                    <button x-show="detailLoan?.status === 'outstanding'" type="button"
                        @click="showDetailModal = false; repaymentLoanId = detailLoan.id; repaymentLoanAmount = detailLoan.loan_amount; repaymentRemaining = detailLoan.remaining; repaymentLoanPeriod = detailLoan.balance_period; showRepaymentModal = true;"
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium transition-colors cursor-pointer flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Make Repayment
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- ==================== IMAGE VIEW MODAL (outside root div) ==================== --}}
    <div x-data="{ showImageModal: false, imageUrl: '' }"
        @open-image-modal.window="imageUrl = $event.detail.url; showImageModal = true"
        x-show="showImageModal" x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showImageModal = false"></div>
        <div class="relative max-w-3xl w-full" @click.stop>
            <button @click="showImageModal = false"
                class="absolute -top-10 right-0 text-white hover:text-gray-300 transition-colors cursor-pointer">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img :src="imageUrl" class="w-full rounded-lg shadow-2xl" alt="Proof Image">
        </div>
    </div>

    {{-- Pagination AJAX Script --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupLoanPagination('loans-pagination-container', 'table-section');
        });

        function setupLoanPagination(containerId, sectionId, alpineComponent = null) {
            const container = document.getElementById(containerId);
            if (!container) return;

            container.addEventListener('click', function(e) {
                const link = e.target.closest('a[href*="page="]');
                if (!link) return;

                e.preventDefault();
                const url = new URL(link.getAttribute('href'), window.location.origin);

                // Preserve month/year/status filters if Alpine component available
                if (alpineComponent) {
                    url.searchParams.set('month', alpineComponent.currentMonth);
                    url.searchParams.set('year', alpineComponent.currentYear);
                    url.searchParams.set('status', alpineComponent.statusFilter);
                }

                NProgress.start();
                fetch(url.toString(), {
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
                            setupLoanPagination(containerId, sectionId, alpineComponent);

                            // Scroll to pagination area (center)
                            setTimeout(() => {
                                const paginationContainer = document.getElementById(containerId);
                                if (paginationContainer) {
                                    paginationContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }, 100);
                        }
                        NProgress.done();
                        window.history.pushState({}, '', url.toString());
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        NProgress.done();
                    });
            });
        }
    </script>
    @endpush
@endsection
