@extends('layouts.app')

@section('title', 'Operational Report')

@section('content')
    <x-nav-locate :items="['Finance', 'Report', 'Operational']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        currentMonth: {{ $month }},
        currentYear: {{ $year }},
        displayText: '',

        {{-- Lock state (read-only, dikendalikan dari Order List) --}}
        currentPeriodLocked: {{ $periodLocked ? 'true' : 'false' }},

        {{-- Extract state --}}
        extractLoading: false,

        {{-- Data arrays from controller --}}
        fixCost1Data: @js($fixCost1->toArray()),
        fixCost2Data: @js($fixCost2->toArray()),
        printingSupplyData: @js($printingSupply->toArray()),
        dailyData: @js($daily->toArray()),

        {{-- Stats --}}
        stats: @js($stats),

        {{-- Search States --}}
        searchFixCost1: '',
        searchFixCost2: '',
        searchPrintingSupply: '',
        searchDaily: '',

        {{-- Per Page States --}}
        perPageFixCost1: 10,
        perPageFixCost2: 10,
        perPagePrintingSupply: 10,
        perPageDaily: 10,

        {{-- Current Page States --}}
        currentPageFixCost1: 1,
        currentPageFixCost2: 1,
        currentPagePrintingSupply: 1,
        currentPageDaily: 1,

        {{-- Add Modal State --}}
        showAddModal: false,
        addCategory: '',
        addCategoryLabel: '',
        addForm: {
            operational_name: '',
            payment_method: '',
            amount: '',
            notes: '',
        },
        addErrors: {},
        addLoading: false,
        operationalListOptions: [],

        {{-- Add Modal - Balance Period State --}}
        addBalanceMonth: null,
        addBalanceYear: null,
        addBalanceId: null,
        addBalanceTransfer: 0,
        addBalanceCash: 0,
        addBalanceMonthDropdownOpen: false,
        addBalanceYearDropdownOpen: false,
        addPeriodValidated: false,
        addPeriodError: '',
        addPaymentMethodDropdownOpen: false,
        addOperationalNameDropdownOpen: false,

        {{-- Edit Modal State --}}
        showEditModal: false,
        editId: null,
        editCategory: '',
        editCategoryLabel: '',
        editForm: {
            name: '',
            payment_method: '',
            amount: '',
            proof_image: null,
            date: '',
            notes: '',
        },
        editErrors: {},
        editLoading: false,
        editListOptions: [],
        editBalanceTransfer: 0,
        editBalanceCash: 0,
        editProofImage: '',

        {{-- Delete Confirm State --}}
        showDeleteConfirm: null,
        deleteCategory: '',

        {{-- Image Preview/Webcam State --}}
        showImagePreview: false,
        imagePreviewSrc: '',
        showWebcam: false,
        stream: null,
        imagePreview: null,
        fileName: '',
        isMirrored: false,
        facingMode: 'environment',

        {{-- Proof 2 Webcam State --}}
        showWebcam2: false,
        stream2: null,
        imagePreview2: null,
        fileName2: '',
        isMirrored2: false,
        facingMode2: 'environment',

        {{-- Group expand/collapse state --}}
        openGroups: { fixCost1: {}, fixCost2: {}, printingSupply: {}, daily: {} },

        {{-- Extra Expense Modal State --}}
        showExtraModal: false,
        extraParentId: null,
        extraParentName: '',
        extraCategory: '',
        extraCategoryLabel: '',
        extraForm: { payment_method: '', amount: '', notes: '' },
        extraErrors: {},
        extraLoading: false,
        extraPaymentDropdownOpen: false,
        extraBalanceTransfer: 0,
        extraBalanceCash: 0,

        {{-- Edit Proof 2 --}}
        editProofImage2: '',
        removeProof2: false,

        {{-- Initialization --}}
        init() {
            // Read URL params to restore month/year on page refresh
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('month')) this.currentMonth = parseInt(urlParams.get('month'));
            if (urlParams.has('year')) this.currentYear = parseInt(urlParams.get('year'));

            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            this.displayText = monthNames[this.currentMonth - 1] + ' ' + this.currentYear;
            
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

            {{-- Also check Laravel flash session (from delete redirect) --}}
            @if(session('toast_message'))
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: '{{ session('toast_message') }}', type: '{{ session('toast_type', 'success') }}' }
                    }));
                }, 300);
            @endif

            // Restore per-page from localStorage
            const savedPerPage = JSON.parse(localStorage.getItem('op_perPage') || '{}');
            if (savedPerPage.fixCost1) this.perPageFixCost1 = savedPerPage.fixCost1;
            if (savedPerPage.fixCost2) this.perPageFixCost2 = savedPerPage.fixCost2;
            if (savedPerPage.printingSupply) this.perPagePrintingSupply = savedPerPage.printingSupply;
            if (savedPerPage.daily) this.perPageDaily = savedPerPage.daily;

            {{-- Reset pagination when search or perPage changes --}}
            this.$watch('searchFixCost1', () => { this.currentPageFixCost1 = 1; });
            this.$watch('perPageFixCost1', (v) => { this.currentPageFixCost1 = 1; this.savePerPage(); });
            this.$watch('searchFixCost2', () => { this.currentPageFixCost2 = 1; });
            this.$watch('perPageFixCost2', (v) => { this.currentPageFixCost2 = 1; this.savePerPage(); });
            this.$watch('searchPrintingSupply', () => { this.currentPagePrintingSupply = 1; });
            this.$watch('perPagePrintingSupply', (v) => { this.currentPagePrintingSupply = 1; this.savePerPage(); });
            this.$watch('searchDaily', () => { this.currentPageDaily = 1; });
            this.$watch('perPageDaily', (v) => { this.currentPageDaily = 1; this.savePerPage(); });
        },

        savePerPage() {
            localStorage.setItem('op_perPage', JSON.stringify({
                fixCost1: this.perPageFixCost1,
                fixCost2: this.perPageFixCost2,
                printingSupply: this.perPagePrintingSupply,
                daily: this.perPageDaily,
            }));
        },

        {{-- Month Navigation --}}
        async navigateMonth(direction) {
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

            // Update URL params so month persists on refresh
            const url = new URL(window.location);
            url.searchParams.set('month', newMonth);
            url.searchParams.set('year', newYear);
            window.history.pushState({}, '', url);

            // Fetch new data via AJAX
            await this.fetchData();
        },

        {{-- Fetch data for current month/year --}}
        async fetchData() {
            try {
                NProgress.start();
                const res = await axios.get(`{{ route('finance.report.operational') }}`, {
                    params: { month: this.currentMonth, year: this.currentYear },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.data.success) {
                    // Update all data
                    this.fixCost1Data = res.data.data.fixCost1;
                    this.fixCost2Data = res.data.data.fixCost2;
                    this.printingSupplyData = res.data.data.printingSupply;
                    this.dailyData = res.data.data.daily;
                    this.stats = res.data.data.stats;
                    this.currentPeriodLocked = res.data.data.periodLocked;

                    // Reset search and pagination
                    this.searchFixCost1 = '';
                    this.searchFixCost2 = '';
                    this.searchPrintingSupply = '';
                    this.searchDaily = '';
                }
            } catch (e) {
                console.error('Failed to fetch data:', e);
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Failed to load data', type: 'error' }
                }));
            } finally {
                NProgress.done();
            }
        },

        {{-- Extract data from operation_lists --}}
        async extractData() {
            this.extractLoading = true;
            try {
                const res = await axios.post('{{ route('finance.report.operational.extract') }}', {
                    balance_month: this.currentMonth,
                    balance_year: this.currentYear,
                });
                if (res.data.success) {
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

        {{-- Delete expense via AJAX --}}
        async deleteExpense(id) {
            if (!confirm('Delete this expense? Balance will be restored.')) {
                return;
            }

            try {
                const res = await axios.delete(`{{ url('finance/report/operational') }}/${id}`);
                
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Operational expense deleted and balance restored!', type: 'success' }
                }));
                await this.fetchData();
            } catch (e) {
                const msg = e.response?.data?.message || 'Failed to delete expense';
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: msg, type: 'error' }
                }));
            }
        },

        {{-- Open Add Modal per category --}}
        async openAddModal(category, label) {
            this.addCategory = category;
            this.addCategoryLabel = label;
            this.addForm = { operational_name: '', payment_method: '', amount: '', notes: '' };
            this.addErrors = {};
            this.imagePreview = null;
            this.fileName = '';
            this.imagePreview2 = null;
            this.fileName2 = '';
            this.operationalListOptions = [];
            this.addBalanceMonth = this.currentMonth;
            this.addBalanceYear = this.currentYear;
            this.addBalanceId = null;
            this.addBalanceTransfer = 0;
            this.addBalanceCash = 0;
            this.addPeriodValidated = true;
            this.addPeriodError = '';
            this.addPaymentMethodDropdownOpen = false;
            this.addOperationalNameDropdownOpen = false;
            this.showAddModal = true;

            // Auto-fetch balance for the current navigation period
            await this.addFetchBalanceData();

            // For non-daily categories, fetch list options and filter out names already in this period
            if (category !== 'daily') {
                try {
                    const res = await axios.get(`{{ route('finance.report.operational.get-lists') }}?category=${category}`);
                    if (res.data.success) {
                        const dataMap = {
                            fix_cost_1: this.fixCost1Data,
                            fix_cost_2: this.fixCost2Data,
                            printing_supply: this.printingSupplyData,
                        };
                        const existingData = dataMap[category] || [];
                        const existingNames = new Set(existingData.map(item => item.operational_name));
                        this.operationalListOptions = res.data.lists.filter(opt => !existingNames.has(opt.list_name));
                    }
                } catch (e) {
                    console.error('Failed to fetch operational lists:', e);
                }
            }
        },

        {{-- Balance Period Selection for Add Modal --}}
        async addSelectMonth(month) {
            this.addBalanceMonth = month;
            this.addBalanceMonthDropdownOpen = false;
            if (this.addBalanceYear) {
                await this.addValidatePeriod();
            }
        },
        async addSelectYear(year) {
            this.addBalanceYear = year;
            this.addBalanceYearDropdownOpen = false;
            if (this.addBalanceMonth) {
                await this.addValidatePeriod();
            }
        },
        async addValidatePeriod() {
            if (!this.addBalanceMonth || !this.addBalanceYear) return;

            this.addPeriodValidated = false;
            this.addPeriodError = '';
            this.addBalanceId = null;
            this.addBalanceTransfer = 0;
            this.addBalanceCash = 0;
            this.operationalListOptions = [];

            try {
                const periodResponse = await fetch(`{{ route('finance.report.operational.check-period-status') }}?month=${this.addBalanceMonth}&year=${this.addBalanceYear}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const periodData = await periodResponse.json();

                if (!periodData.success) {
                    this.addPeriodError = periodData.message;
                    this.addPeriodValidated = false;
                    return;
                }

                this.addPeriodValidated = true;
                await this.addFetchBalanceData();

                // Fetch operational list options for non-daily categories
                if (this.addCategory !== 'daily') {
                    try {
                        const res = await axios.get(`{{ route('finance.report.operational.get-lists') }}?category=${this.addCategory}`);
                        if (res.data.success) {
                            this.operationalListOptions = res.data.lists;
                        }
                    } catch (e) {
                        console.error('Failed to fetch operational lists:', e);
                    }
                }
            } catch (error) {
                console.error('Error validating period:', error);
                this.addPeriodError = 'Failed to validate period. Please try again.';
                this.addPeriodValidated = false;
            }
        },
        async addFetchBalanceData() {
            if (!this.addBalanceMonth || !this.addBalanceYear) return;

            try {
                const response = await fetch(`/finance/balance/find-by-period?month=${this.addBalanceMonth}&year=${this.addBalanceYear}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (data.success && data.balance) {
                    this.addBalanceId = data.balance.id;
                    this.addBalanceTransfer = data.balance.transfer_balance;
                    this.addBalanceCash = data.balance.cash_balance;
                } else {
                    this.addBalanceId = null;
                    this.addBalanceTransfer = 0;
                    this.addBalanceCash = 0;
                }
            } catch (error) {
                console.error('Error fetching balance:', error);
                this.addBalanceId = null;
                this.addBalanceTransfer = 0;
                this.addBalanceCash = 0;
            }
        },
        get addSelectedMonthName() {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            return this.addBalanceMonth ? monthNames[this.addBalanceMonth - 1] : null;
        },
        get addHasBalancePeriod() {
            return this.addBalanceMonth !== null && this.addBalanceYear !== null;
        },
        get addSelectedPaymentMethod() {
            const options = [{ value: 'cash', name: 'Cash' }, { value: 'transfer', name: 'Transfer' }];
            return options.find(p => p.value === this.addForm.payment_method) || null;
        },
        addSelectPaymentMethod(method) {
            this.addForm.payment_method = method;
            this.addPaymentMethodDropdownOpen = false;
        },
        addSelectOperationalName(name) {
            this.addForm.operational_name = name;
            this.addOperationalNameDropdownOpen = false;
        },

        {{-- Group helpers for 2-level table --}}
        groupData(dataArray) {
            const map = {};
            dataArray.forEach(item => {
                const key = item.operational_name;
                if (!map[key]) {
                    map[key] = { name: key, total: 0, items: [], allFixed: true, latestDate: '', firstId: null };
                }
                map[key].total += parseFloat(item.amount || 0);
                map[key].items.push(item);
                if (item.report_status !== 'fixed') map[key].allFixed = false;
                const d = item.operational_date ? new Date(item.operational_date).toLocaleDateString('en-CA') : '';
                if (!map[key].latestDate || d > map[key].latestDate) map[key].latestDate = d;
                if (map[key].firstId === null || item.operational_type === 'first_expense') map[key].firstId = item.id;
            });
            return Object.values(map);
        },

        getFilteredGroups(dataArray, searchKey) {
            const q = (this[searchKey] || '').toLowerCase().trim();
            const groups = this.groupData(dataArray);
            if (!q) return groups;
            return groups.filter(g => g.name.toLowerCase().includes(q));
        },

        getGroupedPaginated(dataArray, searchKey, perPage, currentPage) {
            const filtered = this.getFilteredGroups(dataArray, searchKey);
            const start = (currentPage - 1) * perPage;
            return filtered.slice(start, start + perPage);
        },

        getTotalGroupPages(dataArray, searchKey, perPage) {
            return Math.max(1, Math.ceil(this.getFilteredGroups(dataArray, searchKey).length / perPage));
        },

        getTotalGroupFiltered(dataArray, searchKey) {
            return this.getFilteredGroups(dataArray, searchKey).length;
        },

        isGroupOpen(catKey, name) {
            return !!(this.openGroups[catKey] && this.openGroups[catKey][name]);
        },

        toggleGroup(catKey, name) {
            if (!this.openGroups[catKey]) this.openGroups[catKey] = {};
            const isCurrentlyOpen = this.openGroups[catKey][name];

            // Close all groups across all categories (accordion behavior)
            this.openGroups = { fixCost1: {}, fixCost2: {}, printingSupply: {}, daily: {} };

            // If it was closed, open it; if it was open, leave all closed
            if (!isCurrentlyOpen) {
                this.openGroups[catKey][name] = true;
            }
        },

        {{-- Webcam functions for Add Modal --}}
        async startAddWebcam() {
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
                this.showWebcam = true;
                this.$nextTick(() => {
                    const v = this.$refs.addVideo;
                    if (v) { v.srcObject = this.stream; v.play(); }
                });
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

        async toggleAddCamera() {
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
                this.$nextTick(() => {
                    const v = this.$refs.addVideo;
                    if (v) { v.srcObject = this.stream; v.play(); }
                });
            } catch (err) {
                alert('Gagal mengganti kamera. Error: ' + err.message);
            }
        },

        stopAddWebcam() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.showWebcam = false;
        },

        captureAddPhoto() {
            const video = this.$refs.addVideo;
            const canvas = this.$refs.addCanvas;
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
                const fileInput = document.querySelector('input[name=add_proof_image]');
                if (fileInput) {
                    fileInput.value = '';
                    fileInput.files = dataTransfer.files;
                }
                this.imagePreview = canvas.toDataURL('image/jpeg');
                this.fileName = file.name;
                this.stopAddWebcam();
            }, 'image/jpeg', 0.95);
        },

        {{-- Webcam2 functions for Proof 2 (Add / Extra modals) --}}
        async startAddWebcam2() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Webcam tidak didukung di browser ini.');
                return;
            }
            const isSecure = window.location.protocol === 'https:' ||
                window.location.hostname === 'localhost' ||
                window.location.hostname === '127.0.0.1';
            if (!isSecure) { alert('Webcam membutuhkan HTTPS!'); return; }
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$nextTick(() => {
                    const v2 = this.$refs.addVideo2;
                    if (v2) { v2.srcObject = this.stream2; v2.play(); }
                });
                this.showWebcam2 = true;
            } catch (err) { alert('Webcam error: ' + err.message); }
        },

        async toggleAddCamera2() {
            this.facingMode2 = this.facingMode2 === 'user' ? 'environment' : 'user';
            this.isMirrored2 = this.facingMode2 === 'user';
            if (this.stream2) this.stream2.getTracks().forEach(t => t.stop());
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$nextTick(() => {
                    const v2 = this.$refs.addVideo2;
                    if (v2) { v2.srcObject = this.stream2; v2.play(); }
                });
            } catch (err) { alert('Camera switch error: ' + err.message); }
        },

        stopAddWebcam2() {
            if (this.stream2) { this.stream2.getTracks().forEach(t => t.stop()); this.stream2 = null; }
            this.showWebcam2 = false;
        },

        captureAddPhoto2(inputName) {
            const video = this.$refs.addVideo2;
            const canvas = this.$refs.addCanvas2;
            if (!video || !canvas) return;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored2) { context.translate(canvas.width, 0); context.scale(-1, 1); }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam2_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                const fi = document.querySelector('input[name=' + (inputName || 'add_proof_image2') + ']');
                if (fi) { fi.value = ''; fi.files = dt.files; }
                this.imagePreview2 = canvas.toDataURL('image/jpeg');
                this.fileName2 = file.name;
                this.stopAddWebcam2();
            }, 'image/jpeg', 0.95);
        },

        {{-- Extra Expense Modal functions --}}
        async openAddExtraModal(parentId, parentName, category, label) {
            this.extraParentId = parentId;
            this.extraParentName = parentName;
            this.extraCategory = category;
            this.extraCategoryLabel = label;
            this.extraForm = { payment_method: '', amount: '', notes: '' };
            this.extraErrors = {};
            this.imagePreview = null;
            this.fileName = '';
            this.imagePreview2 = null;
            this.fileName2 = '';
            this.showWebcam = false;
            this.showWebcam2 = false;
            this.extraPaymentDropdownOpen = false;
            this.extraBalanceTransfer = 0;
            this.extraBalanceCash = 0;

            // Fetch balance for current period
            try {
                const r = await fetch(`{{ url('finance/balance/find-by-period') }}?month=${this.currentMonth}&year=${this.currentYear}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const d = await r.json();
                if (d.success && d.balance) {
                    this.extraBalanceTransfer = d.balance.transfer_balance;
                    this.extraBalanceCash = d.balance.cash_balance;
                }
            } catch (e) { /* silently ignore */ }

            this.showExtraModal = true;
        },

        async submitExtraForm() {
            this.extraErrors = {};
            this.extraLoading = true;
            let hasError = false;

            if (!this.extraForm.payment_method) {
                this.extraErrors.payment_method = 'Payment method is required';
                hasError = true;
            }
            const amtClean = String(this.extraForm.amount || '').replace(/\./g, '').replace(/[^0-9]/g, '');
            if (!amtClean || parseInt(amtClean) < 1) {
                this.extraErrors.amount = 'Amount must be at least 1';
                hasError = true;
            }
            const fileInput1 = document.querySelector('input[name=extra_proof_image]');
            if (!fileInput1 || !fileInput1.files[0]) {
                this.extraErrors.proof_image = 'Proof of payment is required';
                hasError = true;
            }

            if (hasError) { this.extraLoading = false; return; }

            const formData = new FormData();
            formData.append('parent_id', this.extraParentId);
            formData.append('payment_method', this.extraForm.payment_method);
            formData.append('amount', amtClean);
            formData.append('operational_date', new Date().toLocaleDateString('en-CA'));
            if (this.extraForm.notes) formData.append('notes', this.extraForm.notes);
            if (fileInput1 && fileInput1.files[0]) formData.append('proof_image', fileInput1.files[0]);
            const fileInput2 = document.querySelector('input[name=extra_proof_image2]');
            if (fileInput2 && fileInput2.files[0]) formData.append('proof_image2', fileInput2.files[0]);

            try {
                const res = await axios.post('{{ route('finance.report.operational.store-extra') }}', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                if (res.data.success) {
                    this.showExtraModal = false;
                    this.stopExtraWebcam1();
                    this.stopExtraWebcam2();
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: res.data.message, type: 'success' } }));
                    await this.fetchData();
                }
            } catch (err) {
                if (err.response?.status === 422) {
                    const e = err.response.data.errors || {};
                    this.extraErrors = {
                        payment_method: e.payment_method?.[0],
                        amount: e.amount?.[0],
                        proof_image: e.proof_image?.[0],
                        proof_image2: e.proof_image2?.[0],
                    };
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: err.response?.data?.message || 'Something went wrong', type: 'error' }
                    }));
                }
            } finally {
                this.extraLoading = false;
            }
        },

        {{-- Webcam functions for Extra Modal Proof 1 --}}
        async startExtraWebcam1() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Webcam tidak didukung di browser ini.');
                return;
            }
            const isSecure = window.location.protocol === 'https:' ||
                window.location.hostname === 'localhost' ||
                window.location.hostname === '127.0.0.1';
            if (!isSecure) { alert('Webcam membutuhkan HTTPS!'); return; }
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.showWebcam = true;
                this.$nextTick(() => {
                    const v = this.$refs.extraVideo1;
                    if (v) { v.srcObject = this.stream; v.play(); }
                });
            } catch (err) { alert('Webcam error: ' + err.message); }
        },

        async toggleExtraCamera1() {
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.isMirrored = this.facingMode === 'user';
            if (this.stream) this.stream.getTracks().forEach(t => t.stop());
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$nextTick(() => {
                    const v = this.$refs.extraVideo1;
                    if (v) { v.srcObject = this.stream; v.play(); }
                });
            } catch (err) { alert('Camera switch error: ' + err.message); }
        },

        stopExtraWebcam1() {
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
            this.showWebcam = false;
        },

        captureExtraPhoto1() {
            const video = this.$refs.extraVideo1;
            const canvas = this.$refs.extraCanvas1;
            if (!video || !canvas) return;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored) { context.translate(canvas.width, 0); context.scale(-1, 1); }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam_extra1_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                const fi = document.querySelector('input[name=extra_proof_image]');
                if (fi) { fi.value = ''; fi.files = dt.files; }
                this.imagePreview = canvas.toDataURL('image/jpeg');
                this.fileName = file.name;
                this.stopExtraWebcam1();
            }, 'image/jpeg', 0.95);
        },

        {{-- Webcam functions for Extra Modal Proof 2 --}}
        async startExtraWebcam2() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Webcam tidak didukung di browser ini.');
                return;
            }
            const isSecure = window.location.protocol === 'https:' ||
                window.location.hostname === 'localhost' ||
                window.location.hostname === '127.0.0.1';
            if (!isSecure) { alert('Webcam membutuhkan HTTPS!'); return; }
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.showWebcam2 = true;
                this.$nextTick(() => {
                    const v = this.$refs.extraVideo2;
                    if (v) { v.srcObject = this.stream2; v.play(); }
                });
            } catch (err) { alert('Webcam error: ' + err.message); }
        },

        async toggleExtraCamera2() {
            this.facingMode2 = this.facingMode2 === 'user' ? 'environment' : 'user';
            this.isMirrored2 = this.facingMode2 === 'user';
            if (this.stream2) this.stream2.getTracks().forEach(t => t.stop());
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$nextTick(() => {
                    const v = this.$refs.extraVideo2;
                    if (v) { v.srcObject = this.stream2; v.play(); }
                });
            } catch (err) { alert('Camera switch error: ' + err.message); }
        },

        stopExtraWebcam2() {
            if (this.stream2) { this.stream2.getTracks().forEach(t => t.stop()); this.stream2 = null; }
            this.showWebcam2 = false;
        },

        captureExtraPhoto2() {
            const video = this.$refs.extraVideo2;
            const canvas = this.$refs.extraCanvas2;
            if (!video || !canvas) return;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored2) { context.translate(canvas.width, 0); context.scale(-1, 1); }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam_extra2_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                const fi = document.querySelector('input[name=extra_proof_image2]');
                if (fi) { fi.value = ''; fi.files = dt.files; }
                this.imagePreview2 = canvas.toDataURL('image/jpeg');
                this.fileName2 = file.name;
                this.stopExtraWebcam2();
            }, 'image/jpeg', 0.95);
        },

        {{-- Webcam2 for Edit Modal (proof 2) --}}
        async startEditWebcam2() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Webcam tidak didukung di browser ini.');
                return;
            }
            const isSecure = window.location.protocol === 'https:' ||
                window.location.hostname === 'localhost' ||
                window.location.hostname === '127.0.0.1';
            if (!isSecure) { alert('Webcam membutuhkan HTTPS!'); return; }
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$nextTick(() => {
                    const v2 = this.$refs.editVideo2;
                    if (v2) { v2.srcObject = this.stream2; v2.play(); }
                });
                this.showWebcam2 = true;
            } catch (err) { alert('Webcam error: ' + err.message); }
        },

        async toggleEditCamera2() {
            this.facingMode2 = this.facingMode2 === 'user' ? 'environment' : 'user';
            this.isMirrored2 = this.facingMode2 === 'user';
            if (this.stream2) this.stream2.getTracks().forEach(t => t.stop());
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$nextTick(() => {
                    const v2 = this.$refs.editVideo2;
                    if (v2) { v2.srcObject = this.stream2; v2.play(); }
                });
            } catch (err) { alert('Camera switch error: ' + err.message); }
        },

        stopEditWebcam2() {
            if (this.stream2) { this.stream2.getTracks().forEach(t => t.stop()); this.stream2 = null; }
            this.showWebcam2 = false;
        },

        captureEditPhoto2() {
            const video = this.$refs.editVideo2;
            const canvas = this.$refs.editCanvas2;
            if (!video || !canvas) return;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored2) { context.translate(canvas.width, 0); context.scale(-1, 1); }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam2_edit_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                const fi = document.querySelector('input[name=edit_proof_image2]');
                if (fi) { fi.value = ''; fi.files = dt.files; }
                this.imagePreview2 = canvas.toDataURL('image/jpeg');
                this.fileName2 = file.name;
                this.editProofImage2 = '';
                this.stopEditWebcam2();
            }, 'image/jpeg', 0.95);
        },

        {{-- Submit Add Form --}}
        async submitAddForm() {
            this.addErrors = {};
            this.addLoading = true;

            // Client-side validation
            let hasError = false;
            if (!this.addForm.operational_name || this.addForm.operational_name.trim() === '') {
                this.addErrors.operational_name = 'Operational name is required';
                hasError = true;
            }
            if (!this.addForm.payment_method) {
                this.addErrors.payment_method = 'Payment method is required';
                hasError = true;
            }
            const amountClean = String(this.addForm.amount).replace(/[^0-9]/g, '');
            if (!amountClean || parseInt(amountClean) < 1) {
                this.addErrors.amount = 'Amount is required and must be at least Rp 1';
                hasError = true;
            }
            if (!this.imagePreview || !this.fileName) {
                this.addErrors.proof_image = 'Proof image is required';
                hasError = true;
            }

            if (hasError) {
                this.addLoading = false;
                return;
            }

            const formData = new FormData();
            formData.append('balance_month', this.addBalanceMonth);
            formData.append('balance_year', this.addBalanceYear);
            formData.append('category', this.addCategory);
            formData.append('operational_name', this.addForm.operational_name);
            formData.append('payment_method', this.addForm.payment_method);
            formData.append('amount', amountClean);
            formData.append('operational_date', new Date().toLocaleDateString('en-CA'));
            if (this.addForm.notes) formData.append('notes', this.addForm.notes);

            const fileInput = document.querySelector('input[name=add_proof_image]');
            if (fileInput && fileInput.files[0]) {
                formData.append('proof_image', fileInput.files[0]);
            }
            const fileInput2 = document.querySelector('input[name=add_proof_image2]');
            if (fileInput2 && fileInput2.files[0]) {
                formData.append('proof_image2', fileInput2.files[0]);
            }

            try {
                const res = await axios.post('{{ route('finance.report.operational.store') }}', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (res.data.success) {
                    this.showAddModal = false;
                    this.stopAddWebcam();
                    this.stopAddWebcam2();
                    this.imagePreview2 = null; this.fileName2 = '';
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: res.data.message, type: 'success' }
                    }));
                    await this.fetchData();
                }
            } catch (err) {
                if (err.response?.status === 422) {
                    const errors = err.response.data.errors;
                    this.addErrors = {
                        operational_name: errors.operational_name?.[0],
                        payment_method: errors.payment_method?.[0],
                        amount: errors.amount?.[0],
                        proof_image: errors.proof_image?.[0],
                        proof_image2: errors.proof_image2?.[0],
                    };
                } else {
                    const msg = err.response?.data?.message || 'Something went wrong';
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: msg, type: 'error' }
                    }));
                }
            } finally {
                this.addLoading = false;
            }
        },

        {{-- Open Edit Modal --}}
        async openEditModal(id, category, label, data) {
            this.editId = id;
            this.editCategory = category;
            this.editCategoryLabel = label;
            
            // Clean amount: remove decimal point (.00) and format with thousand separator
            let cleanAmount = data.amount ? data.amount.toString().split('.')[0] : ''; // Remove .00
            cleanAmount = cleanAmount.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); // Add thousand separator
            
            // Set date to now() (today)
            const today = new Date().toLocaleDateString('en-CA');
            
            this.editForm = {
                name: data.name,
                payment_method: (data.payment_method === 'null' || !data.payment_method) ? '' : data.payment_method,
                amount: cleanAmount,
                proof_image: null,
                date: today, // Always use today's date
                notes: data.notes || '',
            };
            this.editErrors = {};
            this.imagePreview = null;
            this.fileName = '';
            this.imagePreview2 = null;
            this.fileName2 = '';
            this.showWebcam2 = false;
            if (this.stream2) { this.stream2.getTracks().forEach(t => t.stop()); this.stream2 = null; }
            this.removeProof2 = false;
            this.editListOptions = [];
            
            // Load existing proof image
            if (data.proof_img && data.proof_img !== '-') {
                this.editProofImage = `{{ url('finance/report/operational') }}/${id}/image?t=${Date.now()}`;
            } else {
                this.editProofImage = '';
            }

            // Load existing proof image 2
            if (data.proof_img2 && data.proof_img2 !== '-') {
                this.editProofImage2 = `{{ url('finance/report/operational') }}/${id}/image2?t=${Date.now()}`;
            } else {
                this.editProofImage2 = '';
            }

            // Fetch balance data
            try {
                const response = await fetch(`/finance/balance/find-by-period?month=${this.currentMonth}&year=${this.currentYear}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const balanceData = await response.json();
                
                if (balanceData.success && balanceData.balance) {
                    this.editBalanceTransfer = balanceData.balance.transfer_balance;
                    this.editBalanceCash = balanceData.balance.cash_balance;
                } else {
                    this.editBalanceTransfer = 0;
                    this.editBalanceCash = 0;
                }
            } catch (error) {
                console.error('Error fetching balance:', error);
                this.editBalanceTransfer = 0;
                this.editBalanceCash = 0;
            }

            // Fetch list options for non-daily categories
            if (category !== 'daily') {
                try {
                    const res = await axios.get(`{{ route('finance.report.operational.get-lists') }}?category=${category}`);
                    if (res.data.success) {
                        this.editListOptions = res.data.lists;
                    }
                } catch (e) {
                    console.error('Failed to fetch operational lists:', e);
                }
            }

            this.showEditModal = true;
        },

        {{-- Submit Edit Form --}}
        async submitEditForm() {
            this.editErrors = {};
            this.editLoading = true;
            let hasError = false;

            if (!this.editForm.payment_method) {
                this.editErrors.payment_method = 'Payment method is required';
                hasError = true;
            }
            const amtClean = String(this.editForm.amount || '').replace(/\./g, '').replace(/[^0-9]/g, '');
            if (!amtClean || parseInt(amtClean) < 1) {
                this.editErrors.amount = 'Amount must be at least 1';
                hasError = true;
            }
            if (!this.editProofImage && !this.editForm.proof_image) {
                this.editErrors.proof_image = 'Proof of payment is required';
                hasError = true;
            }

            if (hasError) { this.editLoading = false; return; }

            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('operational_name', this.editForm.name);
            formData.append('payment_method', this.editForm.payment_method);
            // Remove separator from amount before submit
            formData.append('amount', amtClean);
            formData.append('operational_date', this.editForm.date);
            if (this.editForm.notes) formData.append('notes', this.editForm.notes);
            if (this.editForm.proof_image) formData.append('proof_image', this.editForm.proof_image);
            // Proof 2 handling
            const editP2Input = document.querySelector('input[name=edit_proof_image2]');
            if (editP2Input && editP2Input.files[0]) formData.append('proof_image2', editP2Input.files[0]);
            if (this.removeProof2) formData.append('remove_proof_image2', '1');

            try {
                const url = `{{ url('finance/report/operational') }}/${this.editId}`;
                const res = await axios.post(url, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (res.data.success) {
                    this.showEditModal = false;
                    this.stopEditWebcam2();
                    this.imagePreview2 = null; this.fileName2 = '';
                    this.editProofImage2 = ''; this.removeProof2 = false;
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: res.data.message, type: 'success' }
                    }));
                    await this.fetchData();
                }
            } catch (err) {
                if (err.response?.status === 422) {
                    const errors = err.response.data.errors;
                    this.editErrors = {
                        name: errors.operational_name?.[0],
                        payment_method: errors.payment_method?.[0],
                        amount: errors.amount?.[0],
                        date: errors.operational_date?.[0],
                        proof_image: errors.proof_image?.[0],
                    };
                } else {
                    const msg = err.response?.data?.message || 'Something went wrong';
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: msg, type: 'error' }
                    }));
                }
            } finally {
                this.editLoading = false;
            }
        },

        {{-- Format Currency --}}
        formatCurrency(value) {
            if (!value) return 'Rp 0';
            const num = parseFloat(value);
            return 'Rp ' + num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        {{-- Get Month Name --}}
        getMonthName(monthNumber) {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            return monthNames[monthNumber - 1] || '';
        },

        {{-- Edit Modal Webcam Functions --}}
        async startWebcam() {
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
                this.$refs.video.srcObject = this.stream;
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
                this.$refs.video.srcObject = this.stream;
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
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
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
                this.editForm.proof_image = file;
                this.imagePreview = canvas.toDataURL('image/jpeg');
                this.fileName = file.name;
                this.stopWebcam();
            }, 'image/jpeg', 0.95);
        },

        {{-- Client-side search matching --}}
        matchesSearch(text, searchKey) {
            const query = this[searchKey].toLowerCase().trim();
            if (!query) return true;
            return (text || '').toLowerCase().includes(query);
        },

        {{-- Pagination Helpers --}}
        getFilteredData(dataArray, searchKey) {
            return dataArray.filter(i => this.matchesSearch(i.operational_name, searchKey));
        },

        getPaginatedData(dataArray, searchKey, perPage, currentPage) {
            const filtered = this.getFilteredData(dataArray, searchKey);
            const start = (currentPage - 1) * perPage;
            const end = start + perPage;
            return filtered.slice(start, end);
        },

        getTotalPages(dataArray, searchKey, perPage) {
            const filtered = this.getFilteredData(dataArray, searchKey);
            return Math.max(1, Math.ceil(filtered.length / perPage));
        },

        getTotalFiltered(dataArray, searchKey) {
            return this.getFilteredData(dataArray, searchKey).length;
        },

        getStartIndex(currentPage, perPage) {
            return ((currentPage - 1) * perPage) + 1;
        },

        getEndIndex(dataArray, searchKey, currentPage, perPage) {
            const filtered = this.getFilteredData(dataArray, searchKey);
            const end = currentPage * perPage;
            return Math.min(end, filtered.length);
        },

        changePage(category, direction) {
            const pageKey = 'currentPage' + category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, '');
            const perPageKey = 'perPage' + category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, '');
            const searchKey = 'search' + category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, '');
            
            let dataArray;
            if (category === 'fixCost1') dataArray = this.fixCost1Data;
            else if (category === 'fixCost2') dataArray = this.fixCost2Data;
            else if (category === 'printingSupply') dataArray = this.printingSupplyData;
            else if (category === 'daily') dataArray = this.dailyData;

            const totalPages = this.getTotalPages(dataArray, searchKey, this[perPageKey]);
            
            if (direction === 'prev' && this[pageKey] > 1) {
                this[pageKey]--;
            } else if (direction === 'next' && this[pageKey] < totalPages) {
                this[pageKey]++;
            }
        },

        goToPage(category, page) {
            const pageKey = 'currentPage' + category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, '');
            this[pageKey] = page;
        },

        getPageNumbers(dataArray, searchKey, perPage, currentPage) {
            const totalPages = this.getTotalPages(dataArray, searchKey, perPage);
            const start = Math.max(currentPage - 2, 1);
            const end = Math.min(start + 4, totalPages);
            const adjustedStart = Math.max(end - 4, 1);
            
            const pages = [];
            for (let i = adjustedStart; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },

        showFirstPage(dataArray, searchKey, perPage, currentPage) {
            const start = Math.max(currentPage - 2, 1);
            return start > 1;
        },

        showLastPage(dataArray, searchKey, perPage, currentPage) {
            const totalPages = this.getTotalPages(dataArray, searchKey, perPage);
            const start = Math.max(currentPage - 2, 1);
            const end = Math.min(start + 4, totalPages);
            return end < totalPages;
        },

        showFirstDots(dataArray, searchKey, perPage, currentPage) {
            const start = Math.max(currentPage - 2, 1);
            return start > 2;
        },

        showLastDots(dataArray, searchKey, perPage, currentPage) {
            const totalPages = this.getTotalPages(dataArray, searchKey, perPage);
            const start = Math.max(currentPage - 2, 1);
            const end = Math.min(start + 4, totalPages);
            return end < totalPages - 1;
        },
    }">

        {{-- ==================== HEADER: Date Navigation ==================== --}}
        <div class="flex flex-col sm:flex-row items-center sm:justify-between gap-3 mb-6">
            {{-- Left group: Lock Badge + Extract Button --}}
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                    </template>
                    <template x-if="currentPeriodLocked">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </template>
                </div>

                {{-- Extract Data Button --}}
                <template x-if="!currentPeriodLocked">
                    <button type="button" @click="extractData()" :disabled="extractLoading"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg border font-semibold text-sm flex-shrink-0 bg-violet-100 border-violet-300 text-violet-800 hover:bg-violet-200 transition-colors disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                        <template x-if="!extractLoading">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
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

            {{-- Date Navigation --}}
            <div class="flex items-center gap-2 flex-shrink-0">
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

            {{-- Fix Cost Total --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Fix Cost Expense</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatCurrency(stats.fix_cost_total)"></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Printing Supply Total --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Printing Expense</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatCurrency(stats.printing_supply)"></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Daily Total --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Daily Expense</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatCurrency(stats.daily_expense)"></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Operational --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Expense</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatCurrency(stats.total_operational)"></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== 4 SECTION GRID ==================== --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            {{-- ===================== FIX COST #1 ===================== --}}
            <section id="fix-cost-1" class="bg-white border border-gray-200 rounded-lg p-5">
                {{-- Header --}}
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">Fix Cost #1</h2>

                    <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                        {{-- Search --}}
                        <div class="relative flex-1 min-w-[100px]">
                            <x-icons.search />
                            <input type="text" x-model="searchFixCost1" placeholder="Search..."
                                class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>

                        {{-- Show Per Page --}}
                        <div x-data="{ open: false }" class="relative flex-shrink-0">
                            <button type="button" @click="open = !open"
                                class="w-14 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                    focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors cursor-pointer">
                                <span x-text="perPageFixCost1"></span>
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
                                class="absolute z-20 mt-1 w-14 bg-white border border-gray-200 rounded-md shadow-lg">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="opt in [5, 10, 15, 20, 25]" :key="opt">
                                        <li @click="perPageFixCost1 = opt; open = false"
                                            class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                            :class="{ 'bg-primary/10 font-medium text-primary': perPageFixCost1 === opt }">
                                            <span x-text="opt"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        {{-- Add Button --}}
                        <button @click="openAddModal('fix_cost_1', 'Fix Cost #1')"
                            :disabled="currentPeriodLocked"
                            class="cursor-pointer flex-shrink-0 whitespace-nowrap px-3 py-2 rounded-md
                            bg-primary text-white hover:bg-primary-dark text-sm text-center disabled:opacity-50 disabled:cursor-not-allowed">
                            + Add
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-light text-gray-600">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold rounded-l-lg">Operational Name</th>
                                <th class="py-3 px-4 text-left font-bold">Total Expense</th>
                                <th class="py-3 px-4 text-left font-bold">Status</th>
                                <th class="py-3 px-4 text-left font-bold">Date</th>
                                <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                            </tr>
                        </thead>

                        {{-- Each group: one <tbody> containing primary row + collapsible detail row --}}
                        <template x-for="(group, gIdx) in getGroupedPaginated(fixCost1Data, 'searchFixCost1', perPageFixCost1, currentPageFixCost1)" :key="group.name + '_' + gIdx">
                            <tbody>
                                {{-- PRIMARY ROW --}}
                                <tr class="hover:bg-gray-50 cursor-pointer"
                                    :class="!isGroupOpen('fixCost1', group.name) && 'border-b border-gray-200'"
                                    @click="toggleGroup('fixCost1', group.name)">
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-900" x-text="group.name"></td>
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-800" x-text="formatCurrency(group.total)"></td>
                                    <td class="py-3 px-4 text-[12px]">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                            :class="group.allFixed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                            x-text="group.allFixed ? 'FIXED' : 'DRAFT'"></span>
                                    </td>
                                    <td class="py-3 px-4 text-[12px] text-gray-700"
                                        x-text="group.latestDate ? new Date(group.latestDate + 'T00:00:00').toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) : '-'"></td>
                                    <td class="py-3 px-4 text-center relative" @click.stop>
                                        <template x-if="currentPeriodLocked"><div class="inline-flex items-center justify-center w-8 h-8 text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div></template>
                                        {{-- Dropdown --}}
                                        <div class="relative inline-block text-left" x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const rect = this.$refs.primaryBtn.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                this.dropdownStyle = spaceBelow < 100
                                                    ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                    : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                            }
                                        }" @scroll.window="open = false" @close-primary-menus.window="open = false" x-show="!currentPeriodLocked">
                                            <button x-ref="primaryBtn" @click.stop="checkPosition(); if(!open){ $dispatch('close-secondary-menus'); } open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="min-w-[160px] bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                <button @click.stop="openAddExtraModal(group.firstId, group.name, 'fix_cost_1', 'Fix Cost #1'); open = false"
                                                    :disabled="currentPeriodLocked"
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Extra Expense
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Expand Arrow --}}
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2 pr-2">
                                            <button type="button"
                                                @click.stop="toggleGroup('fixCost1', group.name)"
                                                class="p-1 hover:bg-gray-100 rounded transition-colors">
                                                <svg class="w-5 h-5 text-gray-400 transition-transform"
                                                    :class="isGroupOpen('fixCost1', group.name) && 'rotate-180'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- DETAIL EXPAND ROW --}}
                                <tr class="border-b border-gray-200" x-show="isGroupOpen('fixCost1', group.name)" x-cloak>
                                    <td colspan="5" class="p-0">
                                        <div x-show="isGroupOpen('fixCost1', group.name)"
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
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Payment</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 1</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 2</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Status</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                            <th class="py-1.5 px-4 text-center text-[10px] font-semibold text-gray-600 rounded-r-md">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="(item, sIdx) in group.items" :key="item.id">
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-600" x-text="sIdx + 1"></td>
                                                                <td class="py-1.5 px-4 text-[10px]">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.payment_method === 'transfer' ? 'bg-blue-100 text-blue-700' : item.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                                        x-text="item.payment_method === 'transfer' ? 'Transfer' : item.payment_method === 'cash' ? 'Cash' : '-'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-900 font-semibold" x-text="formatCurrency(item.amount)"></td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img && item.proof_img !== '-'">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img || item.proof_img === '-'"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img2">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image2`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img2"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.report_status === 'fixed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                                        x-text="item.report_status === 'fixed' ? 'FIXED' : 'DRAFT'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-700"
                                                                    x-text="item.updated_at ? new Date(item.updated_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) + ' (' + new Date(item.updated_at).toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}) + ')' : '-'"></td>
                                                                <td class="py-1.5 px-4 text-center">
                                                                    <div class="relative inline-block text-left" x-data="{
                                                                        open: false,
                                                                        dropdownStyle: {},
                                                                        checkPosition() {
                                                                            const rect = this.$refs.secBtn.getBoundingClientRect();
                                                                            const spaceBelow = window.innerHeight - rect.bottom;
                                                                            this.dropdownStyle = spaceBelow < 120
                                                                                ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                                                : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                                                        }
                                                                    }"
                                                                    @scroll.window="open = false" @close-secondary-menus.window="open = false" x-show="!currentPeriodLocked">
                                                                        <button x-ref="secBtn" @click="checkPosition(); if(!open){ $dispatch('close-primary-menus'); } open = !open" type="button"
                                                                            class="inline-flex items-center justify-center w-6 h-6 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                            </svg>
                                                                        </button>
                                                                        <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle"
                                                                            class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                                            <button @click="openEditModal(item.id, item.category, 'Fix Cost #1', {
                                                                                name: item.operational_name,
                                                                                payment_method: item.payment_method,
                                                                                amount: item.amount,
                                                                                date: item.operational_date ? new Date(item.operational_date).toLocaleDateString('en-CA') : new Date().toLocaleDateString('en-CA'),
                                                                                notes: item.notes,
                                                                                proof_img: item.proof_img,
                                                                                proof_img2: item.proof_img2
                                                                            }); open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                                Edit
                                                                            </button>
                                                                            <button @click="showDeleteConfirm = item.id; open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-red-600 hover:bg-red-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                                Delete
                                                                            </button>
                                                                        </div>
                                                                    </div>
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

                        {{-- Empty state --}}
                        <tbody>
                            <tr x-show="fixCost1Data.length === 0">
                                <td colspan="5" class="py-8 text-center text-gray-400 text-sm">No data available for this period</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination (Grouped) --}}
                <div class="mt-4 flex flex-col items-center gap-3">
                    {{-- Info Text --}}
                    <div class="text-sm text-gray-600">
                        Showing <span x-text="getTotalGroupFiltered(fixCost1Data, 'searchFixCost1') === 0 ? 0 : getStartIndex(currentPageFixCost1, perPageFixCost1)"></span>
                        to <span x-text="Math.min(currentPageFixCost1 * perPageFixCost1, getTotalGroupFiltered(fixCost1Data, 'searchFixCost1'))"></span>
                        of <span x-text="getTotalGroupFiltered(fixCost1Data, 'searchFixCost1')"></span> groups
                    </div>

                    {{-- Pagination Navigation --}}
                    <div class="flex items-center gap-1">
                        {{-- Previous --}}
                        <button @click="changePage('fixCost1', 'prev')" :disabled="currentPageFixCost1 === 1"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPageFixCost1 === 1 ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        {{-- Page numbers (simple: show up to 5) --}}
                        <template x-for="page in Array.from({length: getTotalGroupPages(fixCost1Data, 'searchFixCost1', perPageFixCost1)}, (_,i) => i+1)" :key="page">
                            <button @click="goToPage('fixCost1', page)"
                                class="w-9 h-9 flex items-center justify-center rounded-md transition text-sm"
                                :class="page === currentPageFixCost1 ? 'bg-primary text-white font-medium' : 'bg-white text-gray-600 hover:bg-gray-100'"
                                x-text="page"></button>
                        </template>

                        {{-- Next --}}
                        <button @click="changePage('fixCost1', 'next')"
                            :disabled="currentPageFixCost1 >= getTotalGroupPages(fixCost1Data, 'searchFixCost1', perPageFixCost1)"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPageFixCost1 >= getTotalGroupPages(fixCost1Data, 'searchFixCost1', perPageFixCost1) ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

            {{-- ===================== FIX COST #2 ===================== --}}
            <section id="fix-cost-2" class="bg-white border border-gray-200 rounded-lg p-5">
                {{-- Header --}}
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">Fix Cost #2</h2>

                    <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                        <div class="relative flex-1 min-w-[100px]">
                            <x-icons.search />
                            <input type="text" x-model="searchFixCost2" placeholder="Search..."
                                class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>

                        <div x-data="{ open: false }" class="relative flex-shrink-0">
                            <button type="button" @click="open = !open"
                                class="w-14 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                    focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors cursor-pointer">
                                <span x-text="perPageFixCost2"></span>
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
                                class="absolute z-20 mt-1 w-14 bg-white border border-gray-200 rounded-md shadow-lg">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="opt in [5, 10, 15, 20, 25]" :key="opt">
                                        <li @click="perPageFixCost2 = opt; open = false"
                                            class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                            :class="{ 'bg-primary/10 font-medium text-primary': perPageFixCost2 === opt }">
                                            <span x-text="opt"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <button @click="openAddModal('fix_cost_2', 'Fix Cost #2')"
                            :disabled="currentPeriodLocked"
                            class="cursor-pointer flex-shrink-0 whitespace-nowrap px-3 py-2 rounded-md
                            bg-primary text-white hover:bg-primary-dark text-sm text-center disabled:opacity-50 disabled:cursor-not-allowed">
                            + Add
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-light text-gray-600">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold rounded-l-lg">Operational Name</th>
                                <th class="py-3 px-4 text-left font-bold">Total Expense</th>
                                <th class="py-3 px-4 text-left font-bold">Status</th>
                                <th class="py-3 px-4 text-left font-bold">Date</th>
                                <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                            </tr>
                        </thead>

                        {{-- Each group: one <tbody> containing primary row + collapsible detail row --}}
                        <template x-for="(group, gIdx) in getGroupedPaginated(fixCost2Data, 'searchFixCost2', perPageFixCost2, currentPageFixCost2)" :key="group.name + '_' + gIdx">
                            <tbody>
                                {{-- PRIMARY ROW --}}
                                <tr class="hover:bg-gray-50 cursor-pointer"
                                    :class="!isGroupOpen('fixCost2', group.name) && 'border-b border-gray-200'"
                                    @click="toggleGroup('fixCost2', group.name)">
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-900" x-text="group.name"></td>
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-800" x-text="formatCurrency(group.total)"></td>
                                    <td class="py-3 px-4 text-[12px]">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                            :class="group.allFixed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                            x-text="group.allFixed ? 'FIXED' : 'DRAFT'"></span>
                                    </td>
                                    <td class="py-3 px-4 text-[12px] text-gray-700"
                                        x-text="group.latestDate ? new Date(group.latestDate + 'T00:00:00').toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) : '-'"></td>
                                    <td class="py-3 px-4 text-center relative" @click.stop>
                                        <template x-if="currentPeriodLocked"><div class="inline-flex items-center justify-center w-8 h-8 text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div></template>
                                        {{-- Dropdown --}}
                                        <div class="relative inline-block text-left" x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const rect = this.$refs.primaryBtn.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                this.dropdownStyle = spaceBelow < 100
                                                    ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                    : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                            }
                                        }" @scroll.window="open = false" @close-primary-menus.window="open = false" x-show="!currentPeriodLocked">
                                            <button x-ref="primaryBtn" @click.stop="checkPosition(); if(!open){ $dispatch('close-secondary-menus'); } open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="min-w-[160px] bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                <button @click.stop="openAddExtraModal(group.firstId, group.name, 'fix_cost_2', 'Fix Cost #2'); open = false"
                                                    :disabled="currentPeriodLocked"
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Extra Expense
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Expand Arrow --}}
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2 pr-2">
                                            <button type="button"
                                                @click.stop="toggleGroup('fixCost2', group.name)"
                                                class="p-1 hover:bg-gray-100 rounded transition-colors">
                                                <svg class="w-5 h-5 text-gray-400 transition-transform"
                                                    :class="isGroupOpen('fixCost2', group.name) && 'rotate-180'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- DETAIL EXPAND ROW --}}
                                <tr class="border-b border-gray-200" x-show="isGroupOpen('fixCost2', group.name)" x-cloak>
                                    <td colspan="5" class="p-0">
                                        <div x-show="isGroupOpen('fixCost2', group.name)"
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
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Payment</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 1</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 2</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Status</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                            <th class="py-1.5 px-4 text-center text-[10px] font-semibold text-gray-600 rounded-r-md">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="(item, sIdx) in group.items" :key="item.id">
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-600" x-text="sIdx + 1"></td>
                                                                <td class="py-1.5 px-4 text-[10px]">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.payment_method === 'transfer' ? 'bg-blue-100 text-blue-700' : item.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                                        x-text="item.payment_method === 'transfer' ? 'Transfer' : item.payment_method === 'cash' ? 'Cash' : '-'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-900 font-semibold" x-text="formatCurrency(item.amount)"></td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img && item.proof_img !== '-'">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img || item.proof_img === '-'"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img2">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image2`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img2"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.report_status === 'fixed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                                        x-text="item.report_status === 'fixed' ? 'FIXED' : 'DRAFT'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-700"
                                                                    x-text="item.updated_at ? new Date(item.updated_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) + ' (' + new Date(item.updated_at).toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}) + ')' : '-'"></td>
                                                                <td class="py-1.5 px-4 text-center">
                                                                    <div class="relative inline-block text-left" x-data="{
                                                                        open: false,
                                                                        dropdownStyle: {},
                                                                        checkPosition() {
                                                                            const rect = this.$refs.secBtn.getBoundingClientRect();
                                                                            const spaceBelow = window.innerHeight - rect.bottom;
                                                                            this.dropdownStyle = spaceBelow < 120
                                                                                ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                                                : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                                                        }
                                                                    }"
                                                                    @scroll.window="open = false" @close-secondary-menus.window="open = false" x-show="!currentPeriodLocked">
                                                                        <button x-ref="secBtn" @click="checkPosition(); if(!open){ $dispatch('close-primary-menus'); } open = !open" type="button"
                                                                            class="inline-flex items-center justify-center w-6 h-6 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                            </svg>
                                                                        </button>
                                                                        <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle"
                                                                            class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                                            <button @click="openEditModal(item.id, item.category, 'Fix Cost #2', {
                                                                                name: item.operational_name,
                                                                                payment_method: item.payment_method,
                                                                                amount: item.amount,
                                                                                date: item.operational_date ? new Date(item.operational_date).toLocaleDateString('en-CA') : new Date().toLocaleDateString('en-CA'),
                                                                                notes: item.notes,
                                                                                proof_img: item.proof_img,
                                                                                proof_img2: item.proof_img2
                                                                            }); open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                                Edit
                                                                            </button>
                                                                            <button @click="showDeleteConfirm = item.id; open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-red-600 hover:bg-red-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                                Delete
                                                                            </button>
                                                                        </div>
                                                                    </div>
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

                        {{-- Empty state --}}
                        <tbody>
                            <tr x-show="fixCost2Data.length === 0">
                                <td colspan="5" class="py-8 text-center text-gray-400 text-sm">No data available for this period</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination (Grouped) --}}
                <div class="mt-4 flex flex-col items-center gap-3">
                    {{-- Info Text --}}
                    <div class="text-sm text-gray-600">
                        Showing <span x-text="getTotalGroupFiltered(fixCost2Data, 'searchFixCost2') === 0 ? 0 : getStartIndex(currentPageFixCost2, perPageFixCost2)"></span>
                        to <span x-text="Math.min(currentPageFixCost2 * perPageFixCost2, getTotalGroupFiltered(fixCost2Data, 'searchFixCost2'))"></span>
                        of <span x-text="getTotalGroupFiltered(fixCost2Data, 'searchFixCost2')"></span> groups
                    </div>

                    {{-- Pagination Navigation --}}
                    <div class="flex items-center gap-1">
                        {{-- Previous --}}
                        <button @click="changePage('fixCost2', 'prev')" :disabled="currentPageFixCost2 === 1"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPageFixCost2 === 1 ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        {{-- Page numbers --}}
                        <template x-for="page in Array.from({length: getTotalGroupPages(fixCost2Data, 'searchFixCost2', perPageFixCost2)}, (_,i) => i+1)" :key="page">
                            <button @click="goToPage('fixCost2', page)"
                                class="w-9 h-9 flex items-center justify-center rounded-md transition text-sm"
                                :class="page === currentPageFixCost2 ? 'bg-primary text-white font-medium' : 'bg-white text-gray-600 hover:bg-gray-100'"
                                x-text="page"></button>
                        </template>

                        {{-- Next --}}
                        <button @click="changePage('fixCost2', 'next')"
                            :disabled="currentPageFixCost2 >= getTotalGroupPages(fixCost2Data, 'searchFixCost2', perPageFixCost2)"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPageFixCost2 >= getTotalGroupPages(fixCost2Data, 'searchFixCost2', perPageFixCost2) ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

            {{-- ===================== PRINTING SUPPLY ===================== --}}
            <section id="printing-supply" class="bg-white border border-gray-200 rounded-lg p-5">
                {{-- Header --}}
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">Printing Supply</h2>

                    <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                        <div class="relative flex-1 min-w-[100px]">
                            <x-icons.search />
                            <input type="text" x-model="searchPrintingSupply" placeholder="Search..."
                                class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>

                        <div x-data="{ open: false }" class="relative flex-shrink-0">
                            <button type="button" @click="open = !open"
                                class="w-14 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                    focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors cursor-pointer">
                                <span x-text="perPagePrintingSupply"></span>
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
                                class="absolute z-20 mt-1 w-14 bg-white border border-gray-200 rounded-md shadow-lg">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="opt in [5, 10, 15, 20, 25]" :key="opt">
                                        <li @click="perPagePrintingSupply = opt; open = false"
                                            class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                            :class="{ 'bg-primary/10 font-medium text-primary': perPagePrintingSupply === opt }">
                                            <span x-text="opt"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <button @click="openAddModal('printing_supply', 'Printing Supply')"
                            :disabled="currentPeriodLocked"
                            class="cursor-pointer flex-shrink-0 whitespace-nowrap px-3 py-2 rounded-md
                            bg-primary text-white hover:bg-primary-dark text-sm text-center disabled:opacity-50 disabled:cursor-not-allowed">
                            + Add
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-light text-gray-600">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold rounded-l-lg">Operational Name</th>
                                <th class="py-3 px-4 text-left font-bold">Total Expense</th>
                                <th class="py-3 px-4 text-left font-bold">Status</th>
                                <th class="py-3 px-4 text-left font-bold">Date</th>
                                <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                            </tr>
                        </thead>

                        {{-- Each group: one <tbody> containing primary row + collapsible detail row --}}
                        <template x-for="(group, gIdx) in getGroupedPaginated(printingSupplyData, 'searchPrintingSupply', perPagePrintingSupply, currentPagePrintingSupply)" :key="group.name + '_' + gIdx">
                            <tbody>
                                {{-- PRIMARY ROW --}}
                                <tr class="hover:bg-gray-50 cursor-pointer"
                                    :class="!isGroupOpen('printingSupply', group.name) && 'border-b border-gray-200'"
                                    @click="toggleGroup('printingSupply', group.name)">
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-900" x-text="group.name"></td>
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-800" x-text="formatCurrency(group.total)"></td>
                                    <td class="py-3 px-4 text-[12px]">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                            :class="group.allFixed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                            x-text="group.allFixed ? 'FIXED' : 'DRAFT'"></span>
                                    </td>
                                    <td class="py-3 px-4 text-[12px] text-gray-700"
                                        x-text="group.latestDate ? new Date(group.latestDate + 'T00:00:00').toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) : '-'"></td>
                                    <td class="py-3 px-4 text-center relative" @click.stop>
                                        <template x-if="currentPeriodLocked"><div class="inline-flex items-center justify-center w-8 h-8 text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div></template>
                                        {{-- Dropdown --}}
                                        <div class="relative inline-block text-left" x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const rect = this.$refs.primaryBtn.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                this.dropdownStyle = spaceBelow < 100
                                                    ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                    : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                            }
                                        }" @scroll.window="open = false" @close-primary-menus.window="open = false" x-show="!currentPeriodLocked">
                                            <button x-ref="primaryBtn" @click.stop="checkPosition(); if(!open){ $dispatch('close-secondary-menus'); } open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="min-w-[160px] bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                <button @click.stop="openAddExtraModal(group.firstId, group.name, 'printing_supply', 'Printing Supply'); open = false"
                                                    :disabled="currentPeriodLocked"
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Extra Expense
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Expand Arrow --}}
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2 pr-2">
                                            <button type="button"
                                                @click.stop="toggleGroup('printingSupply', group.name)"
                                                class="p-1 hover:bg-gray-100 rounded transition-colors">
                                                <svg class="w-5 h-5 text-gray-400 transition-transform"
                                                    :class="isGroupOpen('printingSupply', group.name) && 'rotate-180'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- DETAIL EXPAND ROW --}}
                                <tr class="border-b border-gray-200" x-show="isGroupOpen('printingSupply', group.name)" x-cloak>
                                    <td colspan="5" class="p-0">
                                        <div x-show="isGroupOpen('printingSupply', group.name)"
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
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Payment</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 1</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 2</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Status</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                            <th class="py-1.5 px-4 text-center text-[10px] font-semibold text-gray-600 rounded-r-md">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="(item, sIdx) in group.items" :key="item.id">
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-600" x-text="sIdx + 1"></td>
                                                                <td class="py-1.5 px-4 text-[10px]">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.payment_method === 'transfer' ? 'bg-blue-100 text-blue-700' : item.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                                        x-text="item.payment_method === 'transfer' ? 'Transfer' : item.payment_method === 'cash' ? 'Cash' : '-'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-900 font-semibold" x-text="formatCurrency(item.amount)"></td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img && item.proof_img !== '-'">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img || item.proof_img === '-'"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img2">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image2`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img2"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.report_status === 'fixed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                                        x-text="item.report_status === 'fixed' ? 'FIXED' : 'DRAFT'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-700"
                                                                    x-text="item.updated_at ? new Date(item.updated_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) + ' (' + new Date(item.updated_at).toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}) + ')' : '-'"></td>
                                                                <td class="py-1.5 px-4 text-center">
                                                                    <div class="relative inline-block text-left" x-data="{
                                                                        open: false,
                                                                        dropdownStyle: {},
                                                                        checkPosition() {
                                                                            const rect = this.$refs.secBtn.getBoundingClientRect();
                                                                            const spaceBelow = window.innerHeight - rect.bottom;
                                                                            this.dropdownStyle = spaceBelow < 120
                                                                                ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                                                : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                                                        }
                                                                    }"
                                                                    @scroll.window="open = false" @close-secondary-menus.window="open = false" x-show="!currentPeriodLocked">
                                                                        <button x-ref="secBtn" @click="checkPosition(); if(!open){ $dispatch('close-primary-menus'); } open = !open" type="button"
                                                                            class="inline-flex items-center justify-center w-6 h-6 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                            </svg>
                                                                        </button>
                                                                        <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle"
                                                                            class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                                            <button @click="openEditModal(item.id, item.category, 'Printing Supply', {
                                                                                name: item.operational_name,
                                                                                payment_method: item.payment_method,
                                                                                amount: item.amount,
                                                                                date: item.operational_date ? new Date(item.operational_date).toLocaleDateString('en-CA') : new Date().toLocaleDateString('en-CA'),
                                                                                notes: item.notes,
                                                                                proof_img: item.proof_img,
                                                                                proof_img2: item.proof_img2
                                                                            }); open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                                Edit
                                                                            </button>
                                                                            <button @click="showDeleteConfirm = item.id; open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-red-600 hover:bg-red-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                                Delete
                                                                            </button>
                                                                        </div>
                                                                    </div>
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

                        {{-- Empty state --}}
                        <tbody>
                            <tr x-show="printingSupplyData.length === 0">
                                <td colspan="5" class="py-8 text-center text-gray-400 text-sm">No data available for this period</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination (Grouped) --}}
                <div class="mt-4 flex flex-col items-center gap-3">
                    {{-- Info Text --}}
                    <div class="text-sm text-gray-600">
                        Showing <span x-text="getTotalGroupFiltered(printingSupplyData, 'searchPrintingSupply') === 0 ? 0 : getStartIndex(currentPagePrintingSupply, perPagePrintingSupply)"></span>
                        to <span x-text="Math.min(currentPagePrintingSupply * perPagePrintingSupply, getTotalGroupFiltered(printingSupplyData, 'searchPrintingSupply'))"></span>
                        of <span x-text="getTotalGroupFiltered(printingSupplyData, 'searchPrintingSupply')"></span> groups
                    </div>

                    {{-- Pagination Navigation --}}
                    <div class="flex items-center gap-1">
                        {{-- Previous --}}
                        <button @click="changePage('printingSupply', 'prev')" :disabled="currentPagePrintingSupply === 1"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPagePrintingSupply === 1 ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        {{-- Page numbers --}}
                        <template x-for="page in Array.from({length: getTotalGroupPages(printingSupplyData, 'searchPrintingSupply', perPagePrintingSupply)}, (_,i) => i+1)" :key="page">
                            <button @click="goToPage('printingSupply', page)"
                                class="w-9 h-9 flex items-center justify-center rounded-md transition text-sm"
                                :class="page === currentPagePrintingSupply ? 'bg-primary text-white font-medium' : 'bg-white text-gray-600 hover:bg-gray-100'"
                                x-text="page"></button>
                        </template>

                        {{-- Next --}}
                        <button @click="changePage('printingSupply', 'next')"
                            :disabled="currentPagePrintingSupply >= getTotalGroupPages(printingSupplyData, 'searchPrintingSupply', perPagePrintingSupply)"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPagePrintingSupply >= getTotalGroupPages(printingSupplyData, 'searchPrintingSupply', perPagePrintingSupply) ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

            {{-- ===================== DAILY ===================== --}}
            <section id="daily" class="bg-white border border-gray-200 rounded-lg p-5">
                {{-- Header --}}
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">Daily</h2>

                    <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                        <div class="relative flex-1 min-w-[100px]">
                            <x-icons.search />
                            <input type="text" x-model="searchDaily" placeholder="Search..."
                                class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>

                        <div x-data="{ open: false }" class="relative flex-shrink-0">
                            <button type="button" @click="open = !open"
                                class="w-14 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                                    focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors cursor-pointer">
                                <span x-text="perPageDaily"></span>
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
                                class="absolute z-20 mt-1 w-14 bg-white border border-gray-200 rounded-md shadow-lg">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="opt in [5, 10, 15, 20, 25]" :key="opt">
                                        <li @click="perPageDaily = opt; open = false"
                                            class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                            :class="{ 'bg-primary/10 font-medium text-primary': perPageDaily === opt }">
                                            <span x-text="opt"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <button @click="openAddModal('daily', 'Daily')"
                            :disabled="currentPeriodLocked"
                            class="cursor-pointer flex-shrink-0 whitespace-nowrap px-3 py-2 rounded-md
                            bg-primary text-white hover:bg-primary-dark text-sm text-center disabled:opacity-50 disabled:cursor-not-allowed">
                            + Add
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-light text-gray-600">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold rounded-l-lg">Operational Name</th>
                                <th class="py-3 px-4 text-left font-bold">Total Expense</th>
                                <th class="py-3 px-4 text-left font-bold">Status</th>
                                <th class="py-3 px-4 text-left font-bold">Date</th>
                                <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                            </tr>
                        </thead>

                        {{-- Each group: one <tbody> containing primary row + collapsible detail row --}}
                        <template x-for="(group, gIdx) in getGroupedPaginated(dailyData, 'searchDaily', perPageDaily, currentPageDaily)" :key="group.name + '_' + gIdx">
                            <tbody>
                                {{-- PRIMARY ROW --}}
                                <tr class="hover:bg-gray-50 cursor-pointer"
                                    :class="!isGroupOpen('daily', group.name) && 'border-b border-gray-200'"
                                    @click="toggleGroup('daily', group.name)">
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-900" x-text="group.name"></td>
                                    <td class="py-3 px-4 text-[12px] font-semibold text-gray-800" x-text="formatCurrency(group.total)"></td>
                                    <td class="py-3 px-4 text-[12px]">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                            :class="group.allFixed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                            x-text="group.allFixed ? 'FIXED' : 'DRAFT'"></span>
                                    </td>
                                    <td class="py-3 px-4 text-[12px] text-gray-700"
                                        x-text="group.latestDate ? new Date(group.latestDate + 'T00:00:00').toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) : '-'"></td>
                                    <td class="py-3 px-4 text-center relative" @click.stop>
                                        <template x-if="currentPeriodLocked"><div class="inline-flex items-center justify-center w-8 h-8 text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div></template>
                                        {{-- Dropdown --}}
                                        <div class="relative inline-block text-left" x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const rect = this.$refs.primaryBtn.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                this.dropdownStyle = spaceBelow < 100
                                                    ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                    : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                            }
                                        }" @scroll.window="open = false" @close-primary-menus.window="open = false" x-show="!currentPeriodLocked">
                                            <button x-ref="primaryBtn" @click.stop="checkPosition(); if(!open){ $dispatch('close-secondary-menus'); } open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="min-w-[160px] bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                <button @click.stop="openAddExtraModal(group.firstId, group.name, 'daily', 'Daily'); open = false"
                                                    :disabled="currentPeriodLocked"
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Extra Expense
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Expand Arrow --}}
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2 pr-2">
                                            <button type="button"
                                                @click.stop="toggleGroup('daily', group.name)"
                                                class="p-1 hover:bg-gray-100 rounded transition-colors">
                                                <svg class="w-5 h-5 text-gray-400 transition-transform"
                                                    :class="isGroupOpen('daily', group.name) && 'rotate-180'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- DETAIL EXPAND ROW --}}
                                <tr class="border-b border-gray-200" x-show="isGroupOpen('daily', group.name)" x-cloak>
                                    <td colspan="5" class="p-0">
                                        <div x-show="isGroupOpen('daily', group.name)"
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
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Payment</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 1</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 2</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Status</th>
                                                            <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                            <th class="py-1.5 px-4 text-center text-[10px] font-semibold text-gray-600 rounded-r-md">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="(item, sIdx) in group.items" :key="item.id">
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-600" x-text="sIdx + 1"></td>
                                                                <td class="py-1.5 px-4 text-[10px]">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.payment_method === 'transfer' ? 'bg-blue-100 text-blue-700' : item.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                                        x-text="item.payment_method === 'transfer' ? 'Transfer' : item.payment_method === 'cash' ? 'Cash' : '-'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-900 font-semibold" x-text="formatCurrency(item.amount)"></td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img && item.proof_img !== '-'">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img || item.proof_img === '-'"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-left">
                                                                    <template x-if="item.proof_img2">
                                                                        <button @click="showImagePreview = true; imagePreviewSrc = `{{ url('finance/report/operational') }}/${item.id}/image2`"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 text-[10px] font-medium cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                            View
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!item.proof_img2"><span class="text-[10px] text-gray-400">-</span></template>
                                                                </td>
                                                                <td class="py-1.5 px-4">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                                                        :class="item.report_status === 'fixed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                                        x-text="item.report_status === 'fixed' ? 'FIXED' : 'DRAFT'"></span>
                                                                </td>
                                                                <td class="py-1.5 px-4 text-[10px] text-gray-700"
                                                                    x-text="item.updated_at ? new Date(item.updated_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'}) + ' (' + new Date(item.updated_at).toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'}) + ')' : '-'"></td>
                                                                <td class="py-1.5 px-4 text-center">
                                                                    <div class="relative inline-block text-left" x-data="{
                                                                        open: false,
                                                                        dropdownStyle: {},
                                                                        checkPosition() {
                                                                            const rect = this.$refs.secBtn.getBoundingClientRect();
                                                                            const spaceBelow = window.innerHeight - rect.bottom;
                                                                            this.dropdownStyle = spaceBelow < 120
                                                                                ? { position: 'fixed', bottom: (window.innerHeight - rect.top + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' }
                                                                                : { position: 'fixed', top: (rect.bottom + 4) + 'px', right: (window.innerWidth - rect.right) + 'px' };
                                                                        }
                                                                    }"
                                                                    @scroll.window="open = false" @close-secondary-menus.window="open = false" x-show="!currentPeriodLocked">
                                                                        <button x-ref="secBtn" @click="checkPosition(); if(!open){ $dispatch('close-primary-menus'); } open = !open" type="button"
                                                                            class="inline-flex items-center justify-center w-6 h-6 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                            </svg>
                                                                        </button>
                                                                        <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle"
                                                                            class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                                                            <button @click="openEditModal(item.id, item.category, 'Daily', {
                                                                                name: item.operational_name,
                                                                                payment_method: item.payment_method,
                                                                                amount: item.amount,
                                                                                date: item.operational_date ? new Date(item.operational_date).toLocaleDateString('en-CA') : new Date().toLocaleDateString('en-CA'),
                                                                                notes: item.notes,
                                                                                proof_img: item.proof_img,
                                                                                proof_img2: item.proof_img2
                                                                            }); open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                                Edit
                                                                            </button>
                                                                            <button @click="showDeleteConfirm = item.id; open = false"
                                                                                class="px-3 py-1.5 text-[11px] text-red-600 hover:bg-red-50 flex items-center gap-1.5 w-full text-left">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                                Delete
                                                                            </button>
                                                                        </div>
                                                                    </div>
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

                        {{-- Empty state --}}
                        <tbody>
                            <tr x-show="dailyData.length === 0">
                                <td colspan="5" class="py-8 text-center text-gray-400 text-sm">No data available for this period</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination (Grouped) --}}
                <div class="mt-4 flex flex-col items-center gap-3">
                    {{-- Info Text --}}
                    <div class="text-sm text-gray-600">
                        Showing <span x-text="getTotalGroupFiltered(dailyData, 'searchDaily') === 0 ? 0 : getStartIndex(currentPageDaily, perPageDaily)"></span>
                        to <span x-text="Math.min(currentPageDaily * perPageDaily, getTotalGroupFiltered(dailyData, 'searchDaily'))"></span>
                        of <span x-text="getTotalGroupFiltered(dailyData, 'searchDaily')"></span> groups
                    </div>

                    {{-- Pagination Navigation --}}
                    <div class="flex items-center gap-1">
                        {{-- Previous --}}
                        <button @click="changePage('daily', 'prev')" :disabled="currentPageDaily === 1"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPageDaily === 1 ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        {{-- Page numbers --}}
                        <template x-for="page in Array.from({length: getTotalGroupPages(dailyData, 'searchDaily', perPageDaily)}, (_,i) => i+1)" :key="page">
                            <button @click="goToPage('daily', page)"
                                class="w-9 h-9 flex items-center justify-center rounded-md transition text-sm"
                                :class="page === currentPageDaily ? 'bg-primary text-white font-medium' : 'bg-white text-gray-600 hover:bg-gray-100'"
                                x-text="page"></button>
                        </template>

                        {{-- Next --}}
                        <button @click="changePage('daily', 'next')"
                            :disabled="currentPageDaily >= getTotalGroupPages(dailyData, 'searchDaily', perPageDaily)"
                            class="w-9 h-9 flex items-center justify-center rounded-md transition"
                            :class="currentPageDaily >= getTotalGroupPages(dailyData, 'searchDaily', perPageDaily) ? 'text-gray-400 cursor-not-allowed' : 'bg-white text-gray-600 hover:bg-gray-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

        </div>

        {{-- ==================== ADD MODAL ==================== --}}
        <div x-show="showAddModal" x-cloak
            @keydown.escape.window="showAddModal = false; stopAddWebcam()"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">

            {{-- Background Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showAddModal = false; stopAddWebcam()"
                     class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">

                    {{-- Modal Header - Sticky --}}
                    <div class="sticky top-0 z-10 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Add <span x-text="addCategoryLabel"></span> Expense
                        </h3>
                        <button @click="showAddModal = false; stopAddWebcam()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <form id="addOperationalForm" @submit.prevent="submitAddForm()">
                            <div class="space-y-5">

                                {{-- Balance Period Display (Auto-set from navigation) --}}
                                <div class="p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                                        Balance Period
                                    </label>
                                    <input type="text"
                                        :value="addSelectedMonthName + ' ' + addBalanceYear"
                                        readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    <p class="mt-2 text-xs text-primary font-medium">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Auto-selected from current navigation period
                                    </p>
                                </div>

                                <div class="space-y-4">
                                        {{-- Balance Cards --}}
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                                <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                                <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(addBalanceTransfer).toLocaleString('id-ID')"></p>
                                            </div>
                                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                                <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                                <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(addBalanceCash).toLocaleString('id-ID')"></p>
                                            </div>
                                        </div>

                                        {{-- Operational Date & Category (2 cols on desktop, locked) --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Operational Date <span class="text-red-600">*</span>
                                                </label>
                                                <input type="date" :value="new Date().toLocaleDateString('en-CA')" readonly
                                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Category <span class="text-red-600">*</span>
                                                </label>
                                                <input type="text" :value="addCategoryLabel" readonly
                                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                            </div>
                                        </div>

                                        {{-- Operational Name --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Operational Name <span class="text-red-600">*</span>
                                            </label>
                                            {{-- Select dropdown for non-daily --}}
                                            <template x-if="addCategory !== 'daily'">
                                                <div class="relative">
                                                    <button type="button" @click="addOperationalNameDropdownOpen = !addOperationalNameDropdownOpen"
                                                        :class="addErrors.operational_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                        <span x-text="addForm.operational_name || 'Select Operational Name'"
                                                            :class="!addForm.operational_name ? 'text-gray-400' : 'text-gray-900'"></span>
                                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="addOperationalNameDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                    <div x-show="addOperationalNameDropdownOpen" @click.away="addOperationalNameDropdownOpen = false" x-cloak
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95"
                                                        class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                                        <div class="py-1">
                                                            <template x-if="operationalListOptions.length === 0">
                                                                <div class="px-4 py-3 text-sm text-gray-500 text-center">No items available</div>
                                                            </template>
                                                            <template x-for="opt in operationalListOptions" :key="opt.id">
                                                                <button type="button" @click="addSelectOperationalName(opt.list_name)"
                                                                    :class="addForm.operational_name === opt.list_name ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                    class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                                    x-text="opt.list_name">
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            {{-- Text input for daily --}}
                                            <template x-if="addCategory === 'daily'">
                                                <input type="text" x-model="addForm.operational_name" placeholder="e.g. Makan Siang"
                                                    :class="addErrors.operational_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors" />
                                            </template>
                                            <template x-if="addErrors.operational_name">
                                                <p class="mt-1 text-xs text-red-600" x-text="addErrors.operational_name"></p>
                                            </template>
                                        </div>

                                        {{-- Payment Method & Amount (2 cols on desktop) --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {{-- Payment Method --}}
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Payment Method <span class="text-red-600">*</span>
                                                </label>
                                                <div class="relative">
                                                    <button type="button" @click="addPaymentMethodDropdownOpen = !addPaymentMethodDropdownOpen"
                                                        :class="addErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                        <span x-text="addSelectedPaymentMethod ? addSelectedPaymentMethod.name : 'Select Payment Method'"
                                                            :class="!addSelectedPaymentMethod ? 'text-gray-400' : 'text-gray-900'"></span>
                                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="addPaymentMethodDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                    <div x-show="addPaymentMethodDropdownOpen" @click.away="addPaymentMethodDropdownOpen = false" x-cloak
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95"
                                                        class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                                        <div class="py-1">
                                                            <button type="button" @click="addSelectPaymentMethod('cash')"
                                                                :class="addForm.payment_method === 'cash' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                class="w-full text-left px-4 py-2 text-sm transition-colors">Cash</button>
                                                            <button type="button" @click="addSelectPaymentMethod('transfer')"
                                                                :class="addForm.payment_method === 'transfer' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                class="w-full text-left px-4 py-2 text-sm transition-colors">Transfer</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <template x-if="addErrors.payment_method">
                                                    <p class="mt-1 text-xs text-red-600" x-text="addErrors.payment_method"></p>
                                                </template>
                                            </div>

                                            {{-- Amount --}}
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Amount <span class="text-red-600">*</span>
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                                    <input type="text" x-model="addForm.amount"
                                                        @input="addForm.amount = addForm.amount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                        :class="addErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                        class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                        placeholder="0">
                                                </div>
                                                <template x-if="addErrors.amount">
                                                    <p class="mt-1 text-xs text-red-600" x-text="addErrors.amount"></p>
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
                                                    <video x-ref="addVideo" autoplay playsinline
                                                        :class="{ 'scale-x-[-1]': isMirrored }"
                                                        class="w-full h-full object-cover"></video>
                                                    <canvas x-ref="addCanvas" class="hidden"></canvas>
                                                </div>
                                                <div class="flex gap-2 mt-3">
                                                    <button type="button" @click="captureAddPhoto()"
                                                        class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        Capture
                                                    </button>
                                                    <button type="button" @click="toggleAddCamera()"
                                                        class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="stopAddWebcam()"
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
                                                    <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=add_proof_image]').value = ''; startAddWebcam()"
                                                        class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=add_proof_image]').value = ''"
                                                        class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Open Camera Button --}}
                                            <div x-show="!imagePreview && !showWebcam">
                                                <button type="button" @click="startAddWebcam()"
                                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Open Camera
                                                </button>
                                            </div>
                                            <input type="file" name="add_proof_image" accept="image/*" class="hidden">
                                            <template x-if="addErrors.proof_image">
                                                <p class="mt-1 text-xs text-red-600" x-text="addErrors.proof_image"></p>
                                            </template>
                                        </div>

                                        {{-- Proof Image 2 (Optional) - Webcam --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Proof of Payment 2 <span class="text-gray-400 text-xs">(Optional — makes status Fixed)</span>
                                            </label>

                                            {{-- Webcam2 Section --}}
                                            <div x-show="showWebcam2" class="mb-3">
                                                <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 280px;">
                                                    <video x-ref="addVideo2" autoplay playsinline
                                                        :class="{ 'scale-x-[-1]': isMirrored2 }"
                                                        class="w-full h-full object-cover"></video>
                                                    <canvas x-ref="addCanvas2" class="hidden"></canvas>
                                                </div>
                                                <div class="flex gap-2 mt-3">
                                                    <button type="button" @click="captureAddPhoto2('add_proof_image2')"
                                                        class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        Capture
                                                    </button>
                                                    <button type="button" @click="toggleAddCamera2()"
                                                        class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="stopAddWebcam2()"
                                                        class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Close
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Image Preview 2 --}}
                                            <div x-show="imagePreview2 && !showWebcam2" class="mb-3 border-2 border-dashed border-blue-400 rounded-lg p-3 bg-blue-50">
                                                <div class="flex items-center gap-3">
                                                    <img :src="imagePreview2" class="w-24 h-24 object-cover rounded-md border-2 border-blue-500">
                                                    <div class="flex-1">
                                                        <p class="text-sm font-medium text-gray-900" x-text="fileName2"></p>
                                                        <p class="text-xs text-blue-600 mt-1">✓ Proof 2 ready — status will be Fixed</p>
                                                    </div>
                                                    <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=add_proof_image2]').value = ''; startAddWebcam2()"
                                                        class="text-blue-600 hover:text-blue-700 p-1" title="Retake">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=add_proof_image2]').value = ''"
                                                        class="text-red-600 hover:text-red-700 p-1" title="Remove">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Open Camera2 Button --}}
                                            <div x-show="!imagePreview2 && !showWebcam2">
                                                <button type="button" @click="startAddWebcam2()"
                                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-blue-400 hover:bg-blue-50 transition-all flex items-center justify-center gap-2 text-gray-500">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Open Camera for Proof 2
                                                </button>
                                            </div>
                                            <input type="file" name="add_proof_image2" accept="image/*" class="hidden">
                                            <template x-if="addErrors.proof_image2">
                                                <p class="mt-1 text-xs text-red-600" x-text="addErrors.proof_image2"></p>
                                            </template>
                                        </div>

                                        {{-- Notes --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Notes
                                            </label>
                                            <textarea x-model="addForm.notes" rows="3"
                                                class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                                placeholder="Optional notes..."></textarea>
                                        </div>
                                </div>
                                {{-- End space-y-4 wrapper --}}

                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer - Sticky --}}
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                        <button type="button" @click="showAddModal = false; stopAddWebcam()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" form="addOperationalForm" :disabled="addLoading"
                            :class="addLoading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors cursor-pointer flex items-center justify-center gap-2">
                            <template x-if="addLoading">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="addLoading ? 'Processing...' : 'Create Expense'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== EDIT MODAL ==================== --}}
        <div x-show="showEditModal" x-cloak
            @keydown.escape.window="showEditModal = false; stopWebcam(); editProofImage = null"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
            <div @click.away="showEditModal = false; stopWebcam(); editProofImage = null"
                class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                {{-- Modal Header - Sticky --}}
                <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Edit <span x-text="editCategoryLabel"></span> Expense</h3>
                    <button @click="showEditModal = false; stopWebcam(); editProofImage = null" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <form @submit.prevent="submitEditForm()">
                        <div class="space-y-4">
                            {{-- Balance Period Display (Readonly) --}}
                            <div class="p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                <label class="block text-sm font-semibold text-gray-900 mb-3">
                                    Balance Period <span class="text-red-600">*</span>
                                </label>
                                <input type="text" 
                                    :value="getMonthName(currentMonth) + ' ' + currentYear"
                                    readonly
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                <p class="mt-2 text-xs text-primary font-medium">
                                    <span class="font-semibold">Current Period:</span> <span x-text="getMonthName(currentMonth) + ' ' + currentYear"></span>
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

                            {{-- Operation Date & Category --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Operational Date <span class="text-red-600">*</span>
                                    </label>
                                    <input type="date" x-model="editForm.date"
                                        :class="editErrors.date ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors">
                                    <template x-if="editErrors.date">
                                        <p class="mt-1 text-xs text-red-600" x-text="editErrors.date"></p>
                                    </template>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Category <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="editCategoryLabel" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>
                            </div>

                            {{-- Operational Name (Locked / Readonly) --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Operational Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" :value="editForm.name" readonly
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                            </div>

                            {{-- Payment Method & Amount --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Payment Method --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Method <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative" x-data="{ editPaymentDropdownOpen: false }">
                                        <button type="button" @click="editPaymentDropdownOpen = !editPaymentDropdownOpen"
                                            :class="editErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="editForm.payment_method ? (editForm.payment_method === 'cash' ? 'Cash' : 'Transfer') : 'Select Payment'" 
                                                :class="!editForm.payment_method && 'text-gray-400'"></span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="editPaymentDropdownOpen" @click.away="editPaymentDropdownOpen = false" x-cloak
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                            <button type="button" @click="editForm.payment_method = 'cash'; editPaymentDropdownOpen = false"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors"
                                                :class="editForm.payment_method === 'cash' && 'bg-primary/10 font-medium text-primary'">
                                                Cash
                                            </button>
                                            <button type="button" @click="editForm.payment_method = 'transfer'; editPaymentDropdownOpen = false"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors"
                                                :class="editForm.payment_method === 'transfer' && 'bg-primary/10 font-medium text-primary'">
                                                Transfer
                                            </button>
                                        </div>
                                    </div>
                                    <template x-if="editErrors.payment_method">
                                        <p class="mt-1 text-xs text-red-600" x-text="editErrors.payment_method"></p>
                                    </template>
                                </div>

                                {{-- Amount --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Amount <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                                        <input type="text" x-model="editForm.amount"
                                            @input="editForm.amount = editForm.amount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                            :class="editErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md border pl-12 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                            placeholder="0">
                                    </div>
                                    <template x-if="editErrors.amount">
                                        <p class="mt-1 text-xs text-red-600" x-text="editErrors.amount"></p>
                                    </template>
                                </div>
                            </div>

                            {{-- Proof of Payment --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Proof of Payment
                                </label>
                                
                                {{-- Webcam Section --}}
                                <div x-show="showWebcam" class="mb-3">
                                    <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                        <video x-ref="video" autoplay playsinline 
                                            :class="{ 'scale-x-[-1]': isMirrored }"
                                            class="w-full h-full object-cover"></video>
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

                                {{-- Image Preview (Existing or Captured) --}}
                                <div x-show="(imagePreview || editProofImage) && !showWebcam" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="imagePreview || editProofImage" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="fileName || (editProofImage ? 'webcam_' + Date.now() + '.jpg' : '')"></p>
                                            <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                        </div>
                                        <button type="button" @click="editProofImage = null; imagePreview = null; fileName = ''; startWebcam()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="editProofImage = null; imagePreview = null; fileName = ''; editForm.proof_image = null"
                                            class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera Button --}}
                                <div x-show="!imagePreview && !showWebcam && !editProofImage">
                                    <button type="button" @click="startWebcam()"
                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera
                                    </button>
                                </div>
                                <template x-if="editErrors.proof_image">
                                    <p class="mt-1 text-xs text-red-600" x-text="editErrors.proof_image"></p>
                                </template>
                            </div>

                            {{-- Proof Image 2 (Edit) --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Proof 2 <span class="text-gray-400 text-xs">(Optional — makes status Fixed)</span>
                                </label>

                                {{-- Remove proof2 toggle --}}
                                <div x-show="editProofImage2 && !imagePreview2 && !showWebcam2" class="mb-3 border-2 border-dashed border-blue-400 rounded-lg p-3 bg-blue-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="editProofImage2" class="w-24 h-24 object-cover rounded-md border-2 border-blue-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">Foto Bukti 2 (existing)</p>
                                            <p class="text-xs text-blue-600 mt-1">✓ Status: Fixed</p>
                                        </div>
                                        <button type="button" @click="editProofImage2 = null; startEditWebcam2()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Ganti">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="editProofImage2 = null; removeProof2 = true"
                                            class="text-red-600 hover:text-red-700 p-1" title="Hapus Proof 2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Remove Proof2 Confirmation --}}
                                <div x-show="removeProof2 && !editProofImage2 && !imagePreview2 && !showWebcam2" class="mb-3 bg-red-50 border border-red-200 rounded-lg p-3">
                                    <p class="text-sm text-red-700">Proof 2 akan dihapus saat disimpan.</p>
                                    <button type="button" @click="removeProof2 = false"
                                        class="mt-2 text-xs text-red-600 underline">Batal hapus</button>
                                </div>

                                {{-- Webcam2 Edit Section --}}
                                <div x-show="showWebcam2" class="mb-3">
                                    <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 280px;">
                                        <video x-ref="editVideo2" autoplay playsinline
                                            :class="{ 'scale-x-[-1]': isMirrored2 }"
                                            class="w-full h-full object-cover"></video>
                                        <canvas x-ref="editCanvas2" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="captureEditPhoto2()"
                                            class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Capture
                                        </button>
                                        <button type="button" @click="toggleEditCamera2()"
                                            class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="stopEditWebcam2()"
                                            class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Close
                                        </button>
                                    </div>
                                </div>

                                {{-- Captured Proof2 Preview (Edit) --}}
                                <div x-show="imagePreview2 && !showWebcam2" class="mb-3 border-2 border-dashed border-blue-400 rounded-lg p-3 bg-blue-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="imagePreview2" class="w-24 h-24 object-cover rounded-md border-2 border-blue-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="fileName2"></p>
                                            <p class="text-xs text-blue-600 mt-1">✓ Proof 2 baru siap diupload</p>
                                        </div>
                                        <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=edit_proof_image2]').value = ''; startEditWebcam2()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=edit_proof_image2]').value = ''"
                                            class="text-red-600 hover:text-red-700 p-1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera2 Button (Edit) --}}
                                <div x-show="!editProofImage2 && !imagePreview2 && !showWebcam2 && !removeProof2">
                                    <button type="button" @click="startEditWebcam2()"
                                        class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-blue-400 hover:bg-blue-50 transition-all flex items-center justify-center gap-2 text-gray-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera for Proof 2
                                    </button>
                                </div>
                                <input type="file" name="edit_proof_image2" accept="image/*" class="hidden">
                                <template x-if="editErrors.proof_image2">
                                    <p class="mt-1 text-xs text-red-600" x-text="editErrors.proof_image2"></p>
                                </template>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea x-model="editForm.notes" rows="3"
                                    class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                    placeholder="Optional notes..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                
                {{-- Modal Footer - Sticky --}}
                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                    <button type="button" @click="showEditModal = false; stopWebcam(); imagePreview = null; editProofImage = null"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="$el.closest('div').previousElementSibling.querySelector('form').requestSubmit()" :disabled="editLoading"
                        :class="editLoading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <template x-if="editLoading">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="editLoading ? 'Updating...' : 'Update Expense'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ==================== EXTRA EXPENSE MODAL ==================== --}}
        <div x-show="showExtraModal" x-cloak
            @keydown.escape.window="showExtraModal = false; stopExtraWebcam1(); stopExtraWebcam2()"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">

            {{-- Background Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showExtraModal = false; stopExtraWebcam1(); stopExtraWebcam2()"
                    class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">

                    {{-- Modal Header - Sticky --}}
                    <div class="sticky top-0 z-10 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Extra <span x-text="extraCategoryLabel"></span> Expense
                        </h3>
                        <button @click="showExtraModal = false; stopExtraWebcam1(); stopExtraWebcam2()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <form @submit.prevent="submitExtraForm()" id="extraExpenseForm">
                            <div class="space-y-4">

                                {{-- Balance Period (Auto from navigation) --}}
                                <div class="p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Balance Period</label>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-primary/40 text-primary font-semibold text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span x-text="getMonthName(currentMonth) + ' ' + currentYear"></span>
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs text-primary font-medium">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Auto-selected from current navigation period
                                    </p>
                                </div>

                                {{-- Balance Cards --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                        <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                        <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(extraBalanceTransfer || 0).toLocaleString('id-ID')"></p>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                        <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                        <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(extraBalanceCash || 0).toLocaleString('id-ID')"></p>
                                    </div>
                                </div>

                                {{-- Parent Operational Name (readonly) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Operational Name <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="extraParentName" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>

                                {{-- Date + Expense Type (2 cols, readonly) --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Expense Date <span class="text-red-600">*</span>
                                        </label>
                                        <input type="date" :value="new Date().toLocaleDateString('en-CA')" readonly
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Expense Type <span class="text-red-600">*</span>
                                        </label>
                                        <input type="text" value="Extra Expense" readonly
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    </div>
                                </div>

                                {{-- Payment Method + Amount (2 cols) --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Payment Method --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Payment Method <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <button type="button" @click="extraPaymentDropdownOpen = !extraPaymentDropdownOpen"
                                                :class="extraErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                <span x-text="extraForm.payment_method ? (extraForm.payment_method === 'cash' ? 'Cash' : 'Transfer') : 'Select Payment Method'"
                                                    :class="!extraForm.payment_method ? 'text-gray-400' : 'text-gray-900'"></span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="extraPaymentDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div x-show="extraPaymentDropdownOpen" @click.away="extraPaymentDropdownOpen = false" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                                <div class="py-1">
                                                    <button type="button" @click="extraForm.payment_method = 'cash'; extraPaymentDropdownOpen = false"
                                                        :class="extraForm.payment_method === 'cash' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                        class="w-full text-left px-4 py-2 text-sm transition-colors">Cash</button>
                                                    <button type="button" @click="extraForm.payment_method = 'transfer'; extraPaymentDropdownOpen = false"
                                                        :class="extraForm.payment_method === 'transfer' ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                        class="w-full text-left px-4 py-2 text-sm transition-colors">Transfer</button>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="extraErrors.payment_method">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraErrors.payment_method"></p>
                                        </template>
                                    </div>

                                    {{-- Amount --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Amount <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                            <input type="text" x-model="extraForm.amount"
                                                @input="extraForm.amount = $event.target.value.replace(/[^0-9]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.')"
                                                :class="extraErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                placeholder="0">
                                        </div>
                                        <template x-if="extraErrors.amount">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraErrors.amount"></p>
                                        </template>
                                    </div>
                                </div>

                                {{-- Proof of Payment 1 (required) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Proof of Payment <span class="text-red-600">*</span>
                                    </label>
                                    {{-- Webcam --}}
                                    <div x-show="showWebcam" class="mb-3">
                                        <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                            <video x-ref="extraVideo1" autoplay playsinline
                                                :class="{ 'scale-x-[-1]': isMirrored }"
                                                class="w-full h-full object-cover"></video>
                                            <canvas x-ref="extraCanvas1" class="hidden"></canvas>
                                        </div>
                                        <div class="flex gap-2 mt-3">
                                            <button type="button" @click="captureExtraPhoto1()"
                                                class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Capture
                                            </button>
                                            <button type="button" @click="toggleExtraCamera1()"
                                                class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="stopExtraWebcam1()"
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
                                            <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=extra_proof_image]').value = ''; startExtraWebcam1()"
                                                class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=extra_proof_image]').value = ''"
                                                class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Open Camera Button --}}
                                    <div x-show="!imagePreview && !showWebcam">
                                        <button type="button" @click="startExtraWebcam1()"
                                            class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Open Camera
                                        </button>
                                    </div>
                                    <input type="file" name="extra_proof_image" accept="image/*" class="hidden">
                                    <template x-if="extraErrors.proof_image">
                                        <p class="mt-1 text-xs text-red-600" x-text="extraErrors.proof_image"></p>
                                    </template>
                                </div>

                                {{-- Proof of Payment 2 (optional — status → Fixed) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Proof of Payment 2 <span class="text-xs text-gray-400">(optional — status becomes Fixed)</span>
                                    </label>
                                    {{-- Webcam --}}
                                    <div x-show="showWebcam2" class="mb-3">
                                        <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                            <video x-ref="extraVideo2" autoplay playsinline
                                                :class="{ 'scale-x-[-1]': isMirrored2 }"
                                                class="w-full h-full object-cover"></video>
                                            <canvas x-ref="extraCanvas2" class="hidden"></canvas>
                                        </div>
                                        <div class="flex gap-2 mt-3">
                                            <button type="button" @click="captureExtraPhoto2()"
                                                class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Capture
                                            </button>
                                            <button type="button" @click="toggleExtraCamera2()"
                                                class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="stopExtraWebcam2()"
                                                class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Image Preview --}}
                                    <div x-show="imagePreview2 && !showWebcam2" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                        <div class="flex items-center gap-3">
                                            <img :src="imagePreview2" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900" x-text="fileName2"></p>
                                                <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                            </div>
                                            <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=extra_proof_image2]').value = ''; startExtraWebcam2()"
                                                class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=extra_proof_image2]').value = ''"
                                                class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Open Camera Button --}}
                                    <div x-show="!imagePreview2 && !showWebcam2">
                                        <button type="button" @click="startExtraWebcam2()"
                                            class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Open Camera for Proof 2
                                        </button>
                                    </div>
                                    <input type="file" name="extra_proof_image2" accept="image/*" class="hidden">
                                    <template x-if="extraErrors.proof_image2">
                                        <p class="mt-1 text-xs text-red-600" x-text="extraErrors.proof_image2"></p>
                                    </template>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea x-model="extraForm.notes" rows="3"
                                        class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                        placeholder="Optional notes..."></textarea>
                                </div>

                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer - Sticky --}}
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                        <button type="button" @click="showExtraModal = false; stopExtraWebcam1(); stopExtraWebcam2()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" form="extraExpenseForm" :disabled="extraLoading"
                            :class="extraLoading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors cursor-pointer flex items-center justify-center gap-2">
                            <template x-if="extraLoading">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="extraLoading ? 'Processing...' : 'Create Extra Expense'"></span>
                        </button>
                    </div>
                </div>
            </div>
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
                        Delete Operational Expense?
                    </h3>

                    {{-- Message --}}
                    <p class="text-sm text-gray-600 text-center mb-6">
                        Are you sure you want to delete this operational expense? The balance will be restored to the original amount.
                    </p>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <button type="button" @click="showDeleteConfirm = null"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <form :action="`{{ url('finance/report/operational') }}/${showDeleteConfirm}`"
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

        {{-- ==================== IMAGE PREVIEW MODAL ==================== --}}
        <div x-show="showImagePreview" x-cloak class="fixed inset-0 z-50">
            <div x-show="showImagePreview" @click="showImagePreview = false"
                class="fixed inset-0 bg-black/70 backdrop-blur-xs transition-opacity"></div>

            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showImagePreview = false"
                    class="relative max-w-3xl w-full z-10">
                    <button @click="showImagePreview = false"
                        class="absolute -top-10 right-0 text-white hover:text-gray-300 cursor-pointer">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img :src="imagePreviewSrc" class="w-full rounded-lg shadow-2xl" />
                </div>
            </div>
        </div>

    </div>

@endsection
