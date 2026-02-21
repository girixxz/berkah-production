@extends('layouts.app')

@section('title', 'Support Partner Report')

@section('content')
    <x-nav-locate :items="['Finance', 'Report', 'Support Partner']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        currentMonth: {{ $month }},
        currentYear: {{ $year }},
        currentPeriodLocked: {{ $reportPeriod && $reportPeriod->lock_status === 'locked' ? 'true' : 'false' }},
        displayText: '{{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }}',
        searchQuery: '{{ $search }}',
        showServiceModal: false,
        showExtraServiceModal: false,
        extraServiceOrderReportId: null,
        extraServiceBalanceId: null,
        extraServiceBalanceMonth: null,
        extraServiceBalanceYear: null,
        extraServiceBalanceTransfer: 0,
        extraServiceBalanceCash: 0,
        extraServiceOrderData: null,
        extraServiceServiceName: '',
        extraServiceServicePartnerId: null,
        extraServiceServicePartners: [],
        extraServicePartnerDropdownOpen: false,
        extraServicePaymentMethod: '',
        extraServicePaymentMethodDropdownOpen: false,
        extraServiceServiceAmount: '',
        extraServiceServiceNotes: '',
        extraServiceErrors: {},
        isSubmittingExtraService: false,
        showEditServiceModal: false,
        editServiceId: null,
        editServiceData: null,
        editBalanceId: null,
        editBalanceMonth: null,
        editBalanceYear: null,
        editBalanceTransfer: 0,
        editBalanceCash: 0,
        editOrderReportData: null,
        editServiceDate: null,
        editServiceType: null,
        editServiceName: '',
        editServicePartnerId: null,
        editServicePartners: [],
        editPartnerDropdownOpen: false,
        editPaymentMethod: '',
        editPaymentMethodDropdownOpen: false,
        editServiceAmount: '',
        editServiceNotes: '',
        editProofImage: null,
        editProofImage2: null,
        removeProof2: false,
        editServiceErrors: {},
        isSubmittingEditService: false,
        serviceErrors: {},
        isSubmittingService: false,
        stream: null,
        showWebcam: false,
        imagePreview: null,
        fileName: '',
        isMirrored: false,
        facingMode: 'environment',
        stream2: null,
        showWebcam2: false,
        imagePreview2: null,
        fileName2: '',
        isMirrored2: false,
        facingMode2: 'environment',
        showDeleteService: null,
        
        init() {
            // Check for Laravel session flash messages first
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

            // Then check sessionStorage (for AJAX operations)
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
            
            const url = '{{ route('finance.report.support-partner') }}?' + params.toString();
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
                const newServices = doc.getElementById('services-section');
                
                if (newStats) document.getElementById('stats-section').innerHTML = newStats.innerHTML;
                if (newServices) document.getElementById('services-section').innerHTML = newServices.innerHTML;
                
                // Update lock status from returned HTML
                const newStatsEl = doc.getElementById('stats-section');
                if (newStatsEl) {
                    this.currentPeriodLocked = newStatsEl.getAttribute('data-period-locked') === 'true';
                }
                
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
            
            // Only check primary rows (has data-invoice attribute)
            const invoice = (row.getAttribute('data-invoice') || '').toLowerCase();
            const customer = (row.getAttribute('data-customer') || '').toLowerCase();
            const product = (row.getAttribute('data-product') || '').toLowerCase();
            const services = (row.getAttribute('data-services') || '').toLowerCase();
            
            return invoice.includes(query) || customer.includes(query) || product.includes(query) || services.includes(query);
        },
        
        // Computed property untuk Extra Service Month Name
        get extraServiceMonthName() {
            const months = [
                { value: 1, name: 'January' }, { value: 2, name: 'February' }, { value: 3, name: 'March' },
                { value: 4, name: 'April' }, { value: 5, name: 'May' }, { value: 6, name: 'June' },
                { value: 7, name: 'July' }, { value: 8, name: 'August' }, { value: 9, name: 'September' },
                { value: 10, name: 'October' }, { value: 11, name: 'November' }, { value: 12, name: 'December' }
            ];
            const month = months.find(m => m.value === this.extraServiceBalanceMonth);
            return month ? month.name : null;
        },
        
        // Computed property untuk Edit Service Month Name
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
        async startServiceWebcam() {
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
                this.$refs.serviceVideo.srcObject = this.stream;
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
        
        async toggleServiceCamera() {
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
                this.$refs.serviceVideo.srcObject = this.stream;
            } catch (err) {
                alert('Gagal mengganti kamera. Error: ' + err.message);
            }
        },
        
        stopServiceWebcam() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.showWebcam = false;
        },
        
        captureServicePhoto(inputName = 'service_proof_image') {
            const video = this.$refs.serviceVideo;
            const canvas = this.$refs.serviceCanvas;
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
                this.stopServiceWebcam();
            }, 'image/jpeg', 0.95);
        },

        // Webcam2 functions (Proof 2 - + Service modal)
        async startServiceWebcam2() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
            const isSecure = window.location.protocol === 'https:' ||
                           window.location.hostname === 'localhost' ||
                           window.location.hostname === '127.0.0.1';
            if (!isSecure) { alert('WEBCAM HARUS PAKAI HTTPS!'); return; }
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$refs.service2Video.srcObject = this.stream2;
                this.showWebcam2 = true;
            } catch (err) {
                alert('Tidak dapat mengakses webcam: ' + err.message);
            }
        },

        async toggleServiceCamera2() {
            this.facingMode2 = this.facingMode2 === 'user' ? 'environment' : 'user';
            this.isMirrored2 = this.facingMode2 === 'user';
            if (this.stream2) this.stream2.getTracks().forEach(track => track.stop());
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$refs.service2Video.srcObject = this.stream2;
            } catch (err) {
                alert('Gagal mengganti kamera: ' + err.message);
            }
        },

        stopServiceWebcam2() {
            if (this.stream2) {
                this.stream2.getTracks().forEach(track => track.stop());
                this.stream2 = null;
            }
            this.showWebcam2 = false;
        },

        captureServicePhoto2(inputName = 'service_proof_image2') {
            const video = this.$refs.service2Video;
            const canvas = this.$refs.service2Canvas;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored2) {
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
            }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam2_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                const fileInput = document.querySelector(`input[name=${inputName}]`);
                if (fileInput) fileInput.files = dataTransfer.files;
                this.imagePreview2 = canvas.toDataURL('image/jpeg');
                this.fileName2 = file.name;
                this.stopServiceWebcam2();
            }, 'image/jpeg', 0.95);
        },

        // Webcam2 functions (Proof 2 - Extra Service modal)
        async startExtraWebcam2() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
            const isSecure = window.location.protocol === 'https:' ||
                           window.location.hostname === 'localhost' ||
                           window.location.hostname === '127.0.0.1';
            if (!isSecure) { alert('WEBCAM HARUS PAKAI HTTPS!'); return; }
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$refs.extra2Video.srcObject = this.stream2;
                this.showWebcam2 = true;
            } catch (err) {
                alert('Tidak dapat mengakses webcam: ' + err.message);
            }
        },

        async toggleExtraCamera2() {
            this.facingMode2 = this.facingMode2 === 'user' ? 'environment' : 'user';
            this.isMirrored2 = this.facingMode2 === 'user';
            if (this.stream2) this.stream2.getTracks().forEach(track => track.stop());
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$refs.extra2Video.srcObject = this.stream2;
            } catch (err) {
                alert('Gagal mengganti kamera: ' + err.message);
            }
        },

        stopExtraWebcam2() {
            if (this.stream2) {
                this.stream2.getTracks().forEach(track => track.stop());
                this.stream2 = null;
            }
            this.showWebcam2 = false;
        },

        captureExtraPhoto2() {
            const video = this.$refs.extra2Video;
            const canvas = this.$refs.extra2Canvas;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored2) {
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
            }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam2_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                const fileInput = document.querySelector('input[name=extra_service_proof_image2]');
                if (fileInput) fileInput.files = dataTransfer.files;
                this.imagePreview2 = canvas.toDataURL('image/jpeg');
                this.fileName2 = file.name;
                this.stopExtraWebcam2();
            }, 'image/jpeg', 0.95);
        },

        // Webcam2 functions (Proof 2 - Edit Service modal)
        async startEditWebcam2() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
            const isSecure = window.location.protocol === 'https:' ||
                           window.location.hostname === 'localhost' ||
                           window.location.hostname === '127.0.0.1';
            if (!isSecure) { alert('WEBCAM HARUS PAKAI HTTPS!'); return; }
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$refs.editProof2Video.srcObject = this.stream2;
                this.showWebcam2 = true;
            } catch (err) {
                alert('Tidak dapat mengakses webcam: ' + err.message);
            }
        },

        async toggleEditCamera2() {
            this.facingMode2 = this.facingMode2 === 'user' ? 'environment' : 'user';
            this.isMirrored2 = this.facingMode2 === 'user';
            if (this.stream2) this.stream2.getTracks().forEach(track => track.stop());
            try {
                this.stream2 = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode2, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.$refs.editProof2Video.srcObject = this.stream2;
            } catch (err) {
                alert('Gagal mengganti kamera: ' + err.message);
            }
        },

        stopEditWebcam2() {
            if (this.stream2) {
                this.stream2.getTracks().forEach(track => track.stop());
                this.stream2 = null;
            }
            this.showWebcam2 = false;
        },

        captureEditPhoto2() {
            const video = this.$refs.editProof2Video;
            const canvas = this.$refs.editProof2Canvas;
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (this.isMirrored2) {
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
            }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam2_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                const fileInput = document.querySelector('input[name=edit_service_proof_image2]');
                if (fileInput) fileInput.files = dataTransfer.files;
                this.imagePreview2 = canvas.toDataURL('image/jpeg');
                this.fileName2 = file.name;
                this.stopEditWebcam2();
            }, 'image/jpeg', 0.95);
        }
    }">

        {{-- Date Navigation - Mobile: Center Stack, Desktop: Space Between --}}
        <div class="flex flex-col sm:flex-row items-center sm:items-center sm:justify-between gap-3 mb-6 max-w-full">
            {{-- Lock Status Badge (Left) --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg border font-semibold text-sm"
                    :class="currentPeriodLocked
                        ? 'bg-red-100 border-red-300 text-red-800'
                        : 'bg-green-100 border-green-300 text-green-800'">
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
            </div>

            {{-- Date Navigation - Mobile: Top Center, Desktop: Right --}}
            <div class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto justify-center sm:justify-end">
                <button type="button" @click="navigateMonth('prev')" 
                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div class="px-3 py-2 text-center min-w-[140px]">
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
        </div>

        {{-- Statistics Cards --}}
        <div id="stats-section" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6"
            data-period-locked="{{ $reportPeriod && $reportPeriod->lock_status === 'locked' ? 'true' : 'false' }}">
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

        {{-- Service Orders Table Section --}}
        <div id="services-section" class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
            {{-- Header: Title Left, Search + Show Per Page + Button Service Right --}}
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Service Orders</h2>

                {{-- Search + Show Per Page + Button Service --}}
                <div class="flex gap-2 items-center xl:min-w-0">
                    {{-- Search Box --}}
                    <div class="flex-1 xl:min-w-[240px]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="searchQuery" placeholder="Search by Invoice, Customer, or Service..."
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
                            
                            const url = '{{ route('finance.report.support-partner') }}?' + params.toString();
                            window.history.pushState({}, '', url);
                            
                            NProgress.start();
                            fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.getElementById('services-section');
                                
                                if (newSection) {
                                    document.getElementById('services-section').innerHTML = newSection.innerHTML;
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

                    {{-- Button + Service --}}
                    <button type="button" @click="if (!currentPeriodLocked) { showServiceModal = true; serviceErrors = {}; }"
                        :disabled="currentPeriodLocked"
                        class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Service
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
                            <th class="py-3 px-4 text-left font-bold">Price / Pcs</th>
                            <th class="py-3 px-4 text-left font-bold">Status</th>
                            <th class="py-3 px-4 text-left font-bold">Date</th>
                            <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody x-data="{ expandedRows: [], openPrimaryMenu: null }">
                        @forelse($services as $orderReport)
                            @php
                                $order = $orderReport->order ?? null;
                                $invoice = $orderReport->invoice ?? null;
                                $customer = $order->customer ?? null;
                                $productCategory = $order->productCategory->product_name ?? '-';
                                $totalQty = $order ? $order->orderItems->sum('qty') : 0;
                                
                                // Get all partner services for this order
                                $partnerServices = $orderReport->partnerReports;
                                $totalExpense = $partnerServices->sum('amount');
                                
                                // Get first service date (from first_service type)
                                $firstServicePurchase = $partnerServices->where('service_type', 'first_service')->first();
                                $firstServiceDate = $firstServicePurchase->service_date ?? null;
                                
                                // Get balance data dari first purchase
                                $firstServiceBalance = $firstServicePurchase ? $firstServicePurchase->balance : null;
                                // Extract month and year from period_start
                                $balanceMonth = $firstServiceBalance ? \Carbon\Carbon::parse($firstServiceBalance->period_start)->month : null;
                                $balanceYear = $firstServiceBalance ? \Carbon\Carbon::parse($firstServiceBalance->period_start)->year : null;
                                
                                // Get status from order and order_reports
                                $productionStatus = $order->production_status ?? '-';
                                $lockStatus = ($reportPeriod && $reportPeriod->lock_status === 'locked') ? 'locked' : 'unlocked';
                                
                                // Unique row ID
                                $rowId = $orderReport->id;
                            @endphp
                            <tr data-invoice="{{ $invoice->invoice_no ?? '' }}"
                                data-customer="{{ $customer->customer_name ?? '' }}"
                                data-product="{{ $productCategory }}"
                                data-services="{{ $partnerServices->pluck('service_name')->implode(' ') }}"
                                x-show="matchesSearch($el)"
                                @click="
                                    if (expandedRows.includes({{ $rowId }})) {
                                        expandedRows = expandedRows.filter(id => id !== {{ $rowId }});
                                    } else {
                                        expandedRows = [{{ $rowId }}];
                                    }
                                "
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

                                {{-- Price / Pcs --}}
                                <td class="py-3 px-4 text-[12px] text-gray-700">
                                    Rp {{ number_format($totalQty > 0 ? $totalExpense / $totalQty : 0, 0, ',', '.') }}
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
                                        $allFixed = $partnerServices->isNotEmpty() && $partnerServices->every(fn($s) => $s->report_status === 'fixed');
                                    @endphp
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-1 rounded-full text-[12px] font-medium {{ $statusClass }}">
                                            {{ strtoupper($productionStatus) }}
                                        </span>
                                        @if($partnerServices->isNotEmpty())
                                            <span class="px-2 py-1 rounded-full text-[10px] font-semibold {{ $allFixed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $allFixed ? 'FIXED' : 'DRAFT' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Date (First Service) --}}
                                <td class="py-3 px-4 text-[12px] text-gray-700">
                                    {{ $firstServiceDate ? $firstServiceDate->format('d M Y') : '-' }}
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
                                            @if($lockStatus !== 'locked')
                                                <button type="button" 
                                                    @click="
                                                        extraServiceOrderReportId = {{ $orderReport->id }}; 
                                                        extraServiceBalanceId = {{ $firstServiceBalance->id ?? 'null' }};
                                                        extraServiceBalanceMonth = {{ $balanceMonth ?? 'null' }};
                                                        extraServiceBalanceYear = {{ $balanceYear ?? 'null' }};
                                                        extraServiceBalanceTransfer = {{ $firstServiceBalance->transfer_balance ?? 0 }};
                                                        extraServiceBalanceCash = {{ $firstServiceBalance->cash_balance ?? 0 }};
                                                        extraServiceOrderData = {
                                                            id: {{ $orderReport->id }},
                                                            invoice: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }}',
                                                            customer: '{{ $customer->customer_name ?? '' }}',
                                                            product: '{{ $productCategory }}',
                                                            display_name: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }} - {{ $customer->customer_name ?? '' }} ({{ $productCategory }})'
                                                        };
                                                        showExtraServiceModal = true; 
                                                        open = false; 
                                                        extraServiceErrors = {};
                                                    " 
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Extra Service
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Expand Arrow (Absolute positioned at right edge of table) --}}
                                    <div class="absolute right-0 top-1/2 -translate-y-1/2 pr-2">
                                        <button type="button"
                                            @click="
                                                if (expandedRows.includes({{ $rowId }})) {
                                                    expandedRows = expandedRows.filter(id => id !== {{ $rowId }});
                                                } else {
                                                    expandedRows = [{{ $rowId }}];
                                                }
                                            "
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
                            <tr class="border-b border-gray-200" x-show="matchesSearch($el.previousElementSibling)">
                                <td colspan="9" class="p-0">
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
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Service</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Partner</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Payment Method</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Amount</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 1</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Proof 2</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Status</th>
                                                    <th class="py-1.5 px-4 text-left text-[10px] font-semibold text-gray-600">Date</th>
                                                    <th class="py-1.5 px-4 text-center text-[10px] font-semibold text-gray-600">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($partnerServices as $index => $service)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-600">{{ $index + 1 }}</td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-900">{{ $service->service_name }}</td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-700">{{ $service->supportPartner->partner_name ?? '-' }}</td>
                                                        <td class="py-1.5 px-4 text-[10px]">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold 
                                                                {{ $service->payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                                                {{ strtoupper($service->payment_method) }}
                                                            </span>
                                                        </td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-900 font-semibold">Rp {{ number_format($service->amount, 0, ',', '.') }}</td>
                                                        <td class="py-1.5 px-4 text-left">
                                                            @if ($service->proof_img)
                                                                <button @click="$dispatch('open-image-modal', { url: '{{ route('finance.report.support-partner.serve-image', $service->id) }}' })" 
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
                                                        <td class="py-1.5 px-4 text-left">
                                                            @if ($service->proof_img2)
                                                                <button @click="$dispatch('open-image-modal', { url: '{{ route('finance.report.support-partner.serve-image2', $service->id) }}' })" 
                                                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 text-[10px] font-medium cursor-pointer">
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
                                                        <td class="py-1.5 px-4">
                                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold {{ $service->report_status === 'fixed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                                {{ strtoupper($service->report_status ?? 'draft') }}
                                                            </span>
                                                        </td>
                                                        <td class="py-1.5 px-4 text-[10px] text-gray-700">{{ \Carbon\Carbon::parse($service->service_date)->format('d M Y') }}</td>
                                                        <td class="py-1.5 px-4 text-center">
                                                            @if($lockStatus === 'locked')
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
                                                                            this.dropdownStyle = { position: 'fixed', top: (rect.top - 215) + 'px', left: (rect.right - 160) + 'px', width: '155px' }; 
                                                                        } else { 
                                                                            this.dropdownStyle = { position: 'fixed', top: (rect.bottom + 4) + 'px', left: (rect.right - 160) + 'px', width: '155px' }; 
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
                                                                                editServiceId = {{ $service->id }};
                                                                                editServiceData = {
                                                                                    id: {{ $service->id }},
                                                                                    service_name: '{{ $service->service_name }}',
                                                                                    partner_id: {{ $service->support_partner_id }},
                                                                                    partner_name: '{{ $service->supportPartner->partner_name ?? '' }}',
                                                                                    payment_method: '{{ $service->payment_method }}',
                                                                                    amount: {{ $service->amount }},
                                                                                    notes: '{{ addslashes($service->notes ?? '') }}',
                                                                                    proof_img: '{{ $service->proof_img }}',
                                                                                    service_date: '{{ \Carbon\Carbon::parse($service->service_date)->format('Y-m-d') }}',
                                                                                    service_type: '{{ ucfirst(str_replace('_', ' ', $service->service_type)) }}'
                                                                                };
                                                                                // Set balance data
                                                                                editBalanceId = {{ $firstServiceBalance->id ?? 'null' }};
                                                                                editBalanceMonth = {{ $balanceMonth ?? 'null' }};
                                                                                editBalanceYear = {{ $balanceYear ?? 'null' }};
                                                                                editBalanceTransfer = {{ $firstServiceBalance->transfer_balance ?? 0 }};
                                                                                editBalanceCash = {{ $firstServiceBalance->cash_balance ?? 0 }};
                                                                                // Set order report data
                                                                                editOrderReportData = {
                                                                                    id: {{ $orderReport->id }},
                                                                                    invoice: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }}',
                                                                                    customer: '{{ $customer->customer_name ?? '' }}',
                                                                                    product: '{{ $productCategory }}',
                                                                                    display_name: '{{ str_replace('INV-', '', $invoice->invoice_no ?? '') }} - {{ $customer->customer_name ?? '' }} ({{ $productCategory }})'
                                                                                };
                                                                                // Set edit data
                                                                                editServiceDate = '{{ \Carbon\Carbon::parse($service->service_date)->format('Y-m-d') }}';
                                                                                editServiceType = '{{ ucfirst(str_replace('_', ' ', $service->service_type)) }}';
                                                                                editServiceName = '{{ $service->service_name }}';
                                                                                editServicePartnerId = {{ $service->support_partner_id }};
                                                                                editPaymentMethod = '{{ $service->payment_method }}';
                                                                                editServiceAmount = '{{ $service->amount }}';
                                                                                editServiceNotes = '{{ addslashes($service->notes ?? '') }}';
                                                                                editProofImage = '{{ $service->proof_img ? route('finance.report.support-partner.serve-image', $service->id) : '' }}';
                                                                                editProofImage2 = '{{ $service->proof_img2 ? route('finance.report.support-partner.serve-image2', $service->id) : '' }}';
                                                                                imagePreview2 = null;
                                                                                fileName2 = '';
                                                                                showWebcam2 = false;
                                                                                removeProof2 = false;
                                                                                if (stream2) { stream2.getTracks().forEach(t => t.stop()); stream2 = null; }
                                                                                const ep2 = document.querySelector('input[name=edit_service_proof_image2]'); if (ep2) ep2.value = '';
                                                                                const rp2 = document.querySelector('input[name=remove_proof_image2]'); if (rp2) rp2.value = '';
                                                                                showEditServiceModal = true;
                                                                                open = false;
                                                                                editServiceErrors = {};
                                                                            ">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                            </svg>
                                                                            Edit
                                                                        </a>
                                                                        <button type="button" 
                                                                            @click="showDeleteService = {{ $service->id }}; open = false"
                                                                            class="w-full text-left px-3 py-1.5 text-[11px] text-red-600 hover:bg-red-50 flex items-center gap-1.5">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                            </svg>
                                                                            Delete
                                                                        </button>
                                                                        <button type="button"
                                                                            @click="
                                                                                open = false;
                                                                                fetch('{{ route('finance.report.support-partner.toggle-status', $service->id) }}', {
                                                                                    method: 'PATCH',
                                                                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                                                                }).then(r => r.json()).then(data => {
                                                                                    if (data.success) {
                                                                                        sessionStorage.setItem('toast_message', data.message);
                                                                                        sessionStorage.setItem('toast_type', 'success');
                                                                                        window.location.reload();
                                                                                    }
                                                                                });
                                                                            "
                                                                            class="w-full text-left px-3 py-1.5 text-[11px] {{ $service->report_status === 'fixed' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} flex items-center gap-1.5">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                                            </svg>
                                                                            {{ $service->report_status === 'fixed' ? 'Move to Draft' : 'Move to Fixed' }}
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
                                <td colspan="9" class="py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm">No service orders found for this period.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Component (always visible like Material Report) --}}
            <div id="services-pagination-container" class="mt-4">
                <x-custom-pagination :paginator="$services" />
            </div>
        </div>

        {{-- Add Service Modal --}}
        <div x-show="showServiceModal" x-cloak
            @keydown.escape.window="showServiceModal = false; stopServiceWebcam()"
            x-data="{
                balanceMonth: null,
                balanceYear: null,
                balanceId: null,
                balanceTransfer: 0,
                balanceCash: 0,
                periodValidated: false,
                periodError: '',
                orderReportId: null,
                orderReports: [],
                orderReportDropdownOpen: false,
                serviceName: '',
                servicePartnerId: null,
                servicePartners: [],
                partnerDropdownOpen: false,
                paymentMethod: '',
                paymentMethodDropdownOpen: false,
                serviceAmount: '',
                serviceNotes: '',
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
                paymentMethodOptions: [
                    { value: 'cash', name: 'Cash' },
                    { value: 'transfer', name: 'Transfer' }
                ],
                init() {
                    this.fetchPartners();
                },
                get selectedMonthName() {
                    const month = this.balanceMonthOptions.find(m => m.value === this.balanceMonth);
                    return month ? month.name : null;
                },
                get selectedOrderReport() {
                    return this.orderReports.find(o => o.id === this.orderReportId) || null;
                },
                get selectedPartner() {
                    return this.servicePartners.find(s => s.id === this.servicePartnerId) || null;
                },
                get selectedPaymentMethod() {
                    return this.paymentMethodOptions.find(p => p.value === this.paymentMethod) || null;
                },
                async validatePeriod() {
                    if (!this.balanceMonth || !this.balanceYear) return;
                    
                    // Reset state
                    this.periodValidated = false;
                    this.periodError = '';
                    this.balanceId = null;
                    this.balanceTransfer = 0;
                    this.balanceCash = 0;
                    this.orderReports = [];
                    this.orderReportId = null;
                    
                    try {
                        // Validate period first
                        const periodResponse = await fetch(`{{ route('finance.report.support-partner.check-period-status') }}?month=${this.balanceMonth}&year=${this.balanceYear}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const periodData = await periodResponse.json();
                        
                        if (!periodData.success) {
                            this.periodError = periodData.message;
                            this.periodValidated = false;
                            return;
                        }
                        
                        this.periodValidated = true;
                        await this.fetchBalanceData();
                        await this.fetchAvailableOrders();
                        
                    } catch (error) {
                        console.error('Error validating period:', error);
                        this.periodError = 'Failed to validate period. Please try again.';
                        this.periodValidated = false;
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
                        const response = await fetch(`{{ route('finance.report.support-partner.get-available-orders') }}?month=${this.balanceMonth}&year=${this.balanceYear}`, {
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
                async fetchPartners() {
                    try {
                        const response = await fetch('{{ route('finance.report.support-partner.get-partners') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            this.servicePartners = data.partners;
                        }
                    } catch (error) {
                        console.error('Error fetching partners:', error);
                    }
                },
                selectOrderReport(order) {
                    this.orderReportId = order.id;
                    this.orderReportDropdownOpen = false;
                },
                selectPartner(partner) {
                    this.servicePartnerId = partner.id;
                    this.partnerDropdownOpen = false;
                },
                selectPaymentMethod(method) {
                    this.paymentMethod = method.value;
                    this.paymentMethodDropdownOpen = false;
                }
            }"
            x-init="
                $watch('showServiceModal', value => {
                    if (value) {
                        balanceMonth = currentMonth;
                        balanceYear = currentYear;
                        balanceId = null;
                        balanceTransfer = 0;
                        balanceCash = 0;
                        periodValidated = false;
                        periodError = '';
                        orderReportId = null;
                        orderReports = [];
                        serviceName = '';
                        servicePartnerId = null;
                        paymentMethod = '';
                        serviceAmount = '';
                        serviceNotes = '';
                        serviceErrors = {};
                        imagePreview = null;
                        fileName = '';
                        imagePreview2 = null;
                        fileName2 = '';
                        showWebcam2 = false;
                        if (stream2) { stream2.getTracks().forEach(t => t.stop()); stream2 = null; }
                        // Auto-validate period from navigation month/year
                        validatePeriod();
                    }
                })
            "
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">
            
            {{-- Background Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showServiceModal = false; stopServiceWebcam()" 
                     class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                    
                    {{-- Modal Header - Sticky --}}
                    <div class="sticky top-0 z-10 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">New Service Service</h3>
                        <button @click="showServiceModal = false; stopServiceWebcam()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <form id="addServiceForm" x-ref="addServiceForm" @submit.prevent="
                            serviceErrors = {};
                            let hasValidationError = false;

                            if (!orderReportId) {
                                serviceErrors.order_report_id = ['Order selection is required'];
                                hasValidationError = true;
                            }

                            if (!serviceName || serviceName.trim() === '') {
                                serviceErrors.service_name = ['Service name is required'];
                                hasValidationError = true;
                            }

                            if (!servicePartnerId) {
                                serviceErrors.support_partner_id = ['Partner is required'];
                                hasValidationError = true;
                            }

                            if (!paymentMethod) {
                                serviceErrors.payment_method = ['Payment method is required'];
                                hasValidationError = true;
                            }

                            const amountValue = serviceAmount.replace(/[^0-9]/g, '');
                            if (!amountValue || parseInt(amountValue) < 1) {
                                serviceErrors.amount = ['Amount is required and must be at least Rp 1'];
                                hasValidationError = true;
                            }

                            if (!imagePreview || !fileName) {
                                serviceErrors.proof_image = ['Proof image is required'];
                                hasValidationError = true;
                            }

                            if (hasValidationError) {
                                return;
                            }

                            isSubmittingService = true;
                            const formData = new FormData();
                            formData.append('balance_month', balanceMonth);
                            formData.append('balance_year', balanceYear);
                            formData.append('order_report_id', orderReportId);
                            formData.append('service_name', serviceName);
                            formData.append('support_partner_id', servicePartnerId);
                            formData.append('payment_method', paymentMethod);
                            formData.append('amount', amountValue);
                            if (serviceNotes) formData.append('notes', serviceNotes);
                            
                            if (imagePreview && fileName) {
                                const fileInput = document.querySelector('input[name=service_proof_image]');
                                if (fileInput && fileInput.files[0]) {
                                    formData.append('proof_image', fileInput.files[0]);
                                }
                            }
                            
                            if (imagePreview2 && fileName2) {
                                const fileInput2 = document.querySelector('input[name=service_proof_image2]');
                                if (fileInput2 && fileInput2.files[0]) {
                                    formData.append('proof_image2', fileInput2.files[0]);
                                }
                            }
                            
                            fetch('{{ route('finance.report.support-partner.store') }}', {
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
                                    sessionStorage.setItem('toast_message', data.message || 'Service service created successfully!');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else if (status === 422) {
                                    isSubmittingService = false;
                                    serviceErrors = data.errors || {};
                                } else {
                                    isSubmittingService = false;
                                    serviceErrors = data.errors || {};
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingService = false;
                                console.error('Service error:', err);
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: 'Failed to create service. Please try again.', type: 'error' }
                                }));
                            });
                        ">
                            <div class="space-y-5">
                                {{-- Balance Period Info (auto-follows navigation month) --}}
                                <div class="p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Balance Period</label>
                                    <div class="flex items-center gap-2">
                                        <template x-if="selectedMonthName && balanceYear">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-primary/40 text-primary font-semibold text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span x-text="selectedMonthName + ' ' + balanceYear"></span>
                                            </span>
                                        </template>
                                        <template x-if="!selectedMonthName || !balanceYear">
                                            <span class="text-sm text-gray-400 italic">Loading period...</span>
                                        </template>
                                    </div>

                                    {{-- Loading indicator --}}
                                    <template x-if="(selectedMonthName && balanceYear) && !periodValidated && !periodError">
                                        <div class="mt-3 flex items-center gap-2 text-sm text-primary">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Validating period...
                                        </div>
                                    </template>

                                    {{-- Period Error Message --}}
                                    <template x-if="periodError">
                                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <div class="flex items-start gap-2">
                                                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="text-sm text-red-700 font-medium" x-text="periodError"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Content shown only after Balance Period is validated --}}
                                <div x-show="periodValidated && !periodError" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100">
                                    
                                    <div class="space-y-4">
                                        {{-- Balance Cards --}}
                                        <div class="grid grid-cols-2 gap-3">
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
                                                :class="serviceErrors.order_report_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
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
                                        <template x-if="serviceErrors.order_report_id">
                                            <p class="mt-1 text-xs text-red-600" x-text="serviceErrors.order_report_id[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Service Date & Service Type (Locked) --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Service Date <span class="text-red-600">*</span>
                                            </label>
                                            <input type="date" value="{{ now()->toDateString() }}" readonly
                                                class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Service Type <span class="text-red-600">*</span>
                                            </label>
                                            <input type="text" value="First Service" readonly
                                                class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                        </div>
                                    </div>

                                    {{-- Service Name & Partner (2 columns on desktop) --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {{-- Service Name --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Service Name <span class="text-red-600">*</span>
                                            </label>
                                            <input type="text" x-model="serviceName"
                                                :class="serviceErrors.service_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                placeholder="Enter service name">
                                            <template x-if="serviceErrors.service_name">
                                                <p class="mt-1 text-xs text-red-600" x-text="serviceErrors.service_name[0]"></p>
                                            </template>
                                        </div>

                                        {{-- Partner --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Partner <span class="text-red-600">*</span>
                                            </label>
                                            <div class="relative">
                                                <button type="button" @click="partnerDropdownOpen = !partnerDropdownOpen"
                                                    :class="serviceErrors.support_partner_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                    <span x-text="selectedPartner ? selectedPartner.partner_name : 'Select Partner'"
                                                        :class="!selectedPartner ? 'text-gray-400' : 'text-gray-900'"></span>
                                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="partnerDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                
                                                <div x-show="partnerDropdownOpen" @click.away="partnerDropdownOpen = false" x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                                    <div class="py-1">
                                                        <template x-for="partner in servicePartners" :key="partner.id">
                                                            <button type="button" @click="selectPartner(partner)"
                                                                :class="servicePartnerId === partner.id ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                                class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                                x-text="partner.partner_name">
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <template x-if="serviceErrors.support_partner_id">
                                                <p class="mt-1 text-xs text-red-600" x-text="serviceErrors.support_partner_id[0]"></p>
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
                                                    :class="serviceErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
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
                                            <template x-if="serviceErrors.payment_method">
                                                <p class="mt-1 text-xs text-red-600" x-text="serviceErrors.payment_method[0]"></p>
                                            </template>
                                        </div>

                                        {{-- Amount --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Amount <span class="text-red-600">*</span>
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                                <input type="text" x-model="serviceAmount"
                                                    @input="serviceAmount = serviceAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                    :class="serviceErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                    class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                    placeholder="0">
                                            </div>
                                            <template x-if="serviceErrors.amount">
                                                <p class="mt-1 text-xs text-red-600" x-text="serviceErrors.amount[0]"></p>
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
                                                <video x-ref="serviceVideo" autoplay playsinline 
                                                    :class="{ 'scale-x-[-1]': isMirrored }"
                                                    class="w-full h-full object-cover"></video>
                                                <canvas x-ref="serviceCanvas" class="hidden"></canvas>
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <button type="button" @click="captureServicePhoto()"
                                                class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Capture
                                                </button>
                                                <button type="button" @click="toggleServiceCamera()"
                                                class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="stopServiceWebcam()"
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
                                                <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=service_proof_image]').value = ''; startServiceWebcam()"
                                                    class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=service_proof_image]').value = ''"
                                                    class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Open Camera Button --}}
                                        <div x-show="!imagePreview && !showWebcam">
                                            <button type="button" @click="startServiceWebcam()"
                                            class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Open Camera
                                            </button>
                                        </div>
                                        <input type="file" name="service_proof_image" accept="image/*" class="hidden">
                                        <template x-if="serviceErrors.proof_image">
                                            <p class="mt-1 text-xs text-red-600" x-text="serviceErrors.proof_image[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Proof of Payment 2 (Optional) --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Proof of Payment 2 <span class="text-gray-400 text-xs font-normal">(Optional)</span>
                                        </label>

                                        {{-- Webcam2 Section --}}
                                        <div x-show="showWebcam2" class="mb-3">
                                            <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                                <video x-ref="service2Video" autoplay playsinline
                                                    :class="{ 'scale-x-[-1]': isMirrored2 }"
                                                    class="w-full h-full object-cover"></video>
                                                <canvas x-ref="service2Canvas" class="hidden"></canvas>
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <button type="button" @click="captureServicePhoto2('service_proof_image2')"
                                                    class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Capture
                                                </button>
                                                <button type="button" @click="toggleServiceCamera2()"
                                                    class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="stopServiceWebcam2()"
                                                    class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Close
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Image Preview 2 --}}
                                        <div x-show="imagePreview2 && !showWebcam2" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                            <div class="flex items-center gap-3">
                                                <img :src="imagePreview2" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900" x-text="fileName2"></p>
                                                    <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                                </div>
                                                <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=service_proof_image2]').value = ''; startServiceWebcam2()"
                                                    class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=service_proof_image2]').value = ''"
                                                    class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Open Camera Button 2 --}}
                                        <div x-show="!imagePreview2 && !showWebcam2">
                                            <button type="button" @click="startServiceWebcam2()"
                                                class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Open Camera (Optional)
                                            </button>
                                        </div>
                                        <input type="file" name="service_proof_image2" accept="image/*" class="hidden">
                                    </div>

                                    {{-- Notes --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea x-model="serviceNotes" rows="3"
                                            class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                            placeholder="Optional notes..."></textarea>
                                    </div>
                                    </div>
                                    {{-- End space-y-4 wrapper --}}

                                </div>
                                {{-- End: Content shown only after Balance Period is selected --}}
                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer - Sticky --}}
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                        <button type="button" @click="showServiceModal = false; stopServiceWebcam()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" form="addServiceForm" :disabled="isSubmittingService"
                            :class="isSubmittingService ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <template x-if="isSubmittingService">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="isSubmittingService ? 'Processing...' : 'Create Service'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Add Service Modal --}}

        {{-- Extra Service Modal --}}
        <div x-show="showExtraServiceModal" x-cloak
            @keydown.escape.window="showExtraServiceModal = false; stopServiceWebcam()"
            x-data="{
                balanceMonth: null,
                balanceYear: null,
                balanceId: null,
                balanceTransfer: 0,
                balanceCash: 0,
                orderReportData: null,
                serviceName: '',
                servicePartnerId: null,
                servicePartners: [],
                partnerDropdownOpen: false,
                paymentMethod: '',
                paymentMethodDropdownOpen: false,
                serviceAmount: '',
                serviceNotes: '',
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
                    this.fetchPartners();
                    // Watch for modal open and load data langsung dari root scope
                    this.$watch('$root.showExtraServiceModal', (value) => {
                        if (value) {
                            // Set data langsung dari root scope
                            this.balanceId = $root.extraServiceBalanceId;
                            this.balanceMonth = $root.extraServiceBalanceMonth;
                            this.balanceYear = $root.extraServiceBalanceYear;
                            this.balanceTransfer = $root.extraServiceBalanceTransfer;
                            this.balanceCash = $root.extraServiceBalanceCash;
                            this.orderReportData = $root.extraServiceOrderData;
                            
                            console.log('Extra Service Data Loaded from ROOT:');
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
                get selectedPartner() {
                    return this.servicePartners.find(s => s.id === this.servicePartnerId) || null;
                },
                get selectedPaymentMethod() {
                    return this.paymentMethodOptions.find(p => p.value === this.paymentMethod) || null;
                },
                async fetchOrderReportData() {
                    if (!extraServiceOrderReportId) return;
                    
                    try {
                        const response = await fetch(`{{ url('finance/report/support-partner/get-order-report') }}/${extraServiceOrderReportId}`, {
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
                            
                            // Set balance data langsung dari first_service
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
                async fetchPartners() {
                    try {
                        const response = await fetch('{{ route('finance.report.support-partner.get-partners') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            this.servicePartners = data.partners;
                        }
                    } catch (error) {
                        console.error('Error fetching partners:', error);
                    }
                },
                selectPartner(partner) {
                    this.servicePartnerId = partner.id;
                    this.partnerDropdownOpen = false;
                },
                selectPaymentMethod(method) {
                    this.paymentMethod = method.value;
                    this.paymentMethodDropdownOpen = false;
                }
            }"
            x-init="
                $watch('showExtraServiceModal', value => {
                    if (value) {
                        serviceName = '';
                        servicePartnerId = null;
                        paymentMethod = '';
                        serviceAmount = '';
                        serviceNotes = '';
                        extraServiceErrors = {};
                        imagePreview = null;
                        fileName = '';
                        imagePreview2 = null;
                        fileName2 = '';
                        showWebcam2 = false;
                        if (stream2) { stream2.getTracks().forEach(t => t.stop()); stream2 = null; }
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
                <div @click.away="showExtraServiceModal = false; stopServiceWebcam()" 
                     class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                    
                    {{-- Modal Header - Sticky --}}
                    <div class="sticky top-0 z-10 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Extra Service Service</h3>
                        <button @click="showExtraServiceModal = false; stopServiceWebcam()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer text-2xl leading-none">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <form id="addExtraServiceForm" @submit.prevent="
                            extraServiceErrors = {};
                            let hasValidationError = false;

                            if (!serviceName || serviceName.trim() === '') {
                                extraServiceErrors.service_name = ['Service name is required'];
                                hasValidationError = true;
                            }

                            if (!servicePartnerId) {
                                extraServiceErrors.support_partner_id = ['Partner is required'];
                                hasValidationError = true;
                            }

                            if (!paymentMethod) {
                                extraServiceErrors.payment_method = ['Payment method is required'];
                                hasValidationError = true;
                            }

                            const amountValue = serviceAmount.replace(/[^0-9]/g, '');
                            if (!amountValue || parseInt(amountValue) < 1) {
                                extraServiceErrors.amount = ['Amount is required and must be at least Rp 1'];
                                hasValidationError = true;
                            }

                            if (!imagePreview || !fileName) {
                                extraServiceErrors.proof_image = ['Proof image is required'];
                                hasValidationError = true;
                            }

                            if (hasValidationError) {
                                return;
                            }

                            isSubmittingExtraService = true;
                            const formData = new FormData();
                            formData.append('order_report_id', extraServiceOrderReportId);
                            formData.append('service_name', serviceName);
                            if (servicePartnerId) formData.append('support_partner_id', servicePartnerId);
                            formData.append('payment_method', paymentMethod);
                            formData.append('amount', amountValue);
                            if (serviceNotes) formData.append('notes', serviceNotes);
                            
                            if (imagePreview && fileName) {
                                const fileInput = document.querySelector('input[name=extra_service_proof_image]');
                                if (fileInput && fileInput.files[0]) {
                                    formData.append('proof_image', fileInput.files[0]);
                                }
                            }
                            
                            if (imagePreview2 && fileName2) {
                                const fileInput2 = document.querySelector('input[name=extra_service_proof_image2]');
                                if (fileInput2 && fileInput2.files[0]) {
                                    formData.append('proof_image2', fileInput2.files[0]);
                                }
                            }
                            
                            fetch('{{ route('finance.report.support-partner.store-extra') }}', {
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
                                    sessionStorage.setItem('toast_message', data.message || 'Extra service created successfully!');
                                    sessionStorage.setItem('toast_type', 'success');
                                    window.location.reload();
                                } else if (status === 422) {
                                    isSubmittingExtraService = false;
                                    extraServiceErrors = data.errors || {};
                                } else {
                                    isSubmittingExtraService = false;
                                    extraServiceErrors = data.errors || {};
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: data.message, type: 'error' }
                                        }));
                                    }
                                }
                            })
                            .catch(err => {
                                isSubmittingExtraService = false;
                                console.error('Extra service error:', err);
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: { message: 'Failed to create extra service. Please try again.', type: 'error' }
                                }));
                            });
                        ">
                            <div class="space-y-4">
                                {{-- Balance Period Info --}}
                                <div class="mb-4 p-4 bg-gradient-to-br from-primary/10 to-primary/20 rounded-xl border-2 border-primary/30">
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Balance Period</label>
                                    <div class="flex items-center gap-2">
                                        <template x-if="extraServiceMonthName && extraServiceBalanceYear">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-primary/40 text-primary font-semibold text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span x-text="extraServiceMonthName + ' ' + extraServiceBalanceYear"></span>
                                            </span>
                                        </template>
                                        <template x-if="!extraServiceMonthName || !extraServiceBalanceYear">
                                            <span class="text-sm text-gray-400 italic">Loading period...</span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Balance Cards --}}
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 border border-blue-200">
                                        <p class="text-xs text-blue-600 font-medium mb-1">Transfer Balance</p>
                                        <p class="text-base font-bold text-blue-900" x-text="'Rp ' + parseInt(extraServiceBalanceTransfer || 0).toLocaleString('id-ID')"></p>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-3 border border-green-200">
                                        <p class="text-xs text-green-600 font-medium mb-1">Cash Balance</p>
                                        <p class="text-base font-bold text-green-900" x-text="'Rp ' + parseInt(extraServiceBalanceCash || 0).toLocaleString('id-ID')"></p>
                                    </div>
                                </div>

                                {{-- Data Orders (Locked/Readonly) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Data Orders <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" 
                                        :value="extraServiceOrderData ? extraServiceOrderData.display_name : 'Loading...'"
                                        readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>

                                {{-- Service Date & Service Type (Locked) --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Service Date <span class="text-red-600">*</span>
                                        </label>
                                        <input type="date" value="{{ now()->toDateString() }}" readonly
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Service Type <span class="text-red-600">*</span>
                                        </label>
                                        <input type="text" value="Extra Service" readonly
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                    </div>
                                </div>

                                {{-- Service Name & Partner --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Service Name --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Service Name <span class="text-red-600">*</span>
                                        </label>
                                        <input type="text" x-model="serviceName"
                                            :class="extraServiceErrors.service_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                            placeholder="Enter service name">
                                        <template x-if="extraServiceErrors.service_name">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraServiceErrors.service_name[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Partner --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Partner <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <button type="button" @click="partnerDropdownOpen = !partnerDropdownOpen"
                                                :class="extraServiceErrors.support_partner_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                                <span x-text="selectedPartner ? selectedPartner.partner_name : 'Select Partner'"
                                                    :class="!selectedPartner ? 'text-gray-400' : 'text-gray-900'"></span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="partnerDropdownOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            
                                            <div x-show="partnerDropdownOpen" @click.away="partnerDropdownOpen = false" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                                <div class="py-1">
                                                    <template x-for="partner in servicePartners" :key="partner.id">
                                                        <button type="button" @click="selectPartner(partner)"
                                                            :class="servicePartnerId === partner.id ? 'bg-primary/10 text-primary font-medium' : 'text-gray-700 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2 text-sm transition-colors"
                                                            x-text="partner.partner_name">
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="extraServiceErrors.support_partner_id">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraServiceErrors.support_partner_id[0]"></p>
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
                                                :class="extraServiceErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
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
                                        <template x-if="extraServiceErrors.payment_method">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraServiceErrors.payment_method[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Amount --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Amount <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                            <input type="text" x-model="serviceAmount"
                                                @input="serviceAmount = serviceAmount.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                :class="extraServiceErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                                class="w-full rounded-md border pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                                placeholder="0">
                                        </div>
                                        <template x-if="extraServiceErrors.amount">
                                            <p class="mt-1 text-xs text-red-600" x-text="extraServiceErrors.amount[0]"></p>
                                        </template>
                                    </div>
                                </div>

                                {{-- Proof Image - Webcam (Same as Service modal) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Proof of Payment <span class="text-red-600">*</span>
                                    </label>
                                    
                                    {{-- Webcam Section --}}
                                    <div x-show="showWebcam" class="mb-3">
                                        <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                            <video x-ref="serviceVideo" autoplay playsinline 
                                                :class="{ 'scale-x-[-1]': isMirrored }"
                                                class="w-full h-full object-cover"></video>
                                            <canvas x-ref="serviceCanvas" class="hidden"></canvas>
                                        </div>
                                        <div class="flex gap-2 mt-3">
                                            <button type="button" @click="captureServicePhoto('extra_service_proof_image')"
                                            class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Capture
                                            </button>
                                            <button type="button" @click="toggleServiceCamera()"
                                            class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="stopServiceWebcam()"
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
                                            <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=extra_service_proof_image]').value = ''; startServiceWebcam()"
                                                class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="imagePreview = null; fileName = ''; document.querySelector('input[name=extra_service_proof_image]').value = ''"
                                                class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Open Camera Button --}}
                                    <div x-show="!imagePreview && !showWebcam">
                                        <button type="button" @click="startServiceWebcam()"
                                        class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Open Camera
                                        </button>
                                    </div>
                                    <input type="file" name="extra_service_proof_image" accept="image/*" class="hidden">
                                    <template x-if="extraServiceErrors.proof_image">
                                        <p class="mt-1 text-xs text-red-600" x-text="extraServiceErrors.proof_image[0]"></p>
                                    </template>
                                </div>

                                {{-- Proof of Payment 2 (Optional) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Proof of Payment 2 <span class="text-gray-400 text-xs font-normal">(Optional)</span>
                                    </label>

                                    {{-- Webcam2 Section --}}
                                    <div x-show="showWebcam2" class="mb-3">
                                        <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                            <video x-ref="extra2Video" autoplay playsinline
                                                :class="{ 'scale-x-[-1]': isMirrored2 }"
                                                class="w-full h-full object-cover"></video>
                                            <canvas x-ref="extra2Canvas" class="hidden"></canvas>
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

                                    {{-- Image Preview 2 --}}
                                    <div x-show="imagePreview2 && !showWebcam2" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                        <div class="flex items-center gap-3">
                                            <img :src="imagePreview2" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900" x-text="fileName2"></p>
                                                <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                            </div>
                                            <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=extra_service_proof_image2]').value = ''; startExtraWebcam2()"
                                                class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=extra_service_proof_image2]').value = ''"
                                                class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Open Camera Button 2 --}}
                                    <div x-show="!imagePreview2 && !showWebcam2">
                                        <button type="button" @click="startExtraWebcam2()"
                                            class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Open Camera (Optional)
                                        </button>
                                    </div>
                                    <input type="file" name="extra_service_proof_image2" accept="image/*" class="hidden">
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Notes
                                    </label>
                                    <textarea x-model="serviceNotes" rows="3"
                                        class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                        placeholder="Optional notes..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer - Sticky --}}
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                        <button type="button" @click="showExtraServiceModal = false; stopServiceWebcam()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" form="addExtraServiceForm" :disabled="isSubmittingExtraService"
                            :class="isSubmittingExtraService ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <template x-if="isSubmittingExtraService">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="isSubmittingExtraService ? 'Processing...' : 'Create Extra Service'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Extra Service Modal --}}

        {{-- Edit Service Modal --}}
        <div x-show="showEditServiceModal" x-cloak
            @keydown.escape.window="showEditServiceModal = false; stopServiceWebcam()"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
            <div @click.away="showEditServiceModal = false; stopServiceWebcam()"
                class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                {{-- Modal Header - Sticky --}}
                <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Service Service</h3>
                    <button @click="showEditServiceModal = false; stopServiceWebcam()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <form @submit.prevent="
                        if (isSubmittingEditService) return;
                        isSubmittingEditService = true;
                        editServiceErrors = {};
                        
                        const formData = new FormData();
                        formData.append('_method', 'PUT');
                        formData.append('service_name', editServiceName || '');
                        formData.append('support_partner_id', editServicePartnerId || '');
                        formData.append('payment_method', editPaymentMethod || '');
                        formData.append('amount', editServiceAmount || '');
                        formData.append('notes', editServiceNotes || '');
                        
                        // Handle proof image upload (jika ada foto baru)
                        const fileInput = document.querySelector('input[name=edit_service_proof_image]');
                        if (fileInput && fileInput.files[0]) {
                            formData.append('proof_image', fileInput.files[0]);
                        }
                        
                        // Handle proof image 2
                        const fileInput2 = document.querySelector('input[name=edit_service_proof_image2]');
                        if (fileInput2 && fileInput2.files[0]) {
                            formData.append('proof_image2', fileInput2.files[0]);
                        } else if (removeProof2) {
                            formData.append('remove_proof_image2', '1');
                        }
                        
                        fetch(`{{ url('finance/report/support-partner') }}/${editServiceId}`, {
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
                                sessionStorage.setItem('toast_message', data.message || 'Service service updated successfully!');
                                sessionStorage.setItem('toast_type', 'success');
                                window.location.reload();
                            } else if (status === 422) {
                                isSubmittingEditService = false;
                                editServiceErrors = data.errors || {};
                            } else {
                                isSubmittingEditService = false;
                                editServiceErrors = data.errors || {};
                                if (data.message) {
                                    window.dispatchEvent(new CustomEvent('show-toast', {
                                        detail: { message: data.message, type: 'error' }
                                    }));
                                }
                            }
                        })
                        .catch(err => {
                            isSubmittingEditService = false;
                            console.error('Edit service error:', err);
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: 'Failed to update service service. Please try again.', type: 'error' }
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

                            {{-- Service Date & Service Type (Locked) --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Service Date <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="editServiceDate" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Service Type <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" :value="editServiceType" readonly
                                        class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 bg-gray-50 text-gray-600 cursor-not-allowed pointer-events-none">
                                </div>
                            </div>

                            {{-- Service Name & Partner --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Service Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Service Name <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" x-model="editServiceName"
                                        :class="editServiceErrors.service_name ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full rounded-md border px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                        placeholder="Enter service name">
                                    <template x-if="editServiceErrors.service_name">
                                        <p class="mt-1 text-xs text-red-600" x-text="editServiceErrors.service_name[0]"></p>
                                    </template>
                                </div>

                                {{-- Partner --}}
                                <div x-data="{ 
                                    fetchPartners: async function() {
                                        try {
                                            const response = await fetch('{{ route('finance.report.support-partner.get-partners') }}', {
                                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                                            });
                                            const data = await response.json();
                                            if (data.success) {
                                                editServicePartners = data.partners;
                                            }
                                        } catch (error) {
                                            console.error('Error fetching partners:', error);
                                        }
                                    }
                                }" x-init="if (editServicePartners.length === 0) fetchPartners()">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Partner <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <button type="button" @click="editPartnerDropdownOpen = !editPartnerDropdownOpen"
                                            :class="editServiceErrors.support_partner_id ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="editServicePartners.find(s => s.id === editServicePartnerId)?.partner_name || 'Select Partner'" 
                                                :class="!editServicePartnerId && 'text-gray-400'"></span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="editPartnerDropdownOpen" @click.away="editPartnerDropdownOpen = false" x-cloak
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            <template x-for="partner in editServicePartners" :key="partner.id">
                                                <button type="button" @click="editServicePartnerId = partner.id; editPartnerDropdownOpen = false"
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-primary/5 transition-colors"
                                                    :class="editServicePartnerId === partner.id && 'bg-primary/10 font-medium text-primary'">
                                                    <span x-text="partner.partner_name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <template x-if="editServiceErrors.support_partner_id">
                                        <p class="mt-1 text-xs text-red-600" x-text="editServiceErrors.support_partner_id[0]"></p>
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
                                            :class="editServiceErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
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
                                    <template x-if="editServiceErrors.payment_method">
                                        <p class="mt-1 text-xs text-red-600" x-text="editServiceErrors.payment_method[0]"></p>
                                    </template>
                                </div>

                                {{-- Amount --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Amount <span class="text-red-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                                        <input type="number" x-model="editServiceAmount"
                                            :class="editServiceErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md border pl-12 pr-4 py-2 text-sm focus:outline-none focus:ring-2 transition-colors"
                                            placeholder="0" min="1">
                                    </div>
                                    <template x-if="editServiceErrors.amount">
                                        <p class="mt-1 text-xs text-red-600" x-text="editServiceErrors.amount[0]"></p>
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
                                        <video x-ref="serviceVideo" autoplay playsinline 
                                            :class="{ 'scale-x-[-1]': isMirrored }"
                                            class="w-full h-full object-cover"></video>
                                        <canvas x-ref="serviceCanvas" class="hidden"></canvas>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="captureServicePhoto('edit_service_proof_image')"
                                        class="flex-1 px-3 py-2 text-sm bg-primary text-white rounded-md hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Capture
                                        </button>
                                        <button type="button" @click="toggleServiceCamera()"
                                        class="px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4-4m-4 4l4 4" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="stopServiceWebcam()"
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
                                        <button type="button" @click="editProofImage = null; imagePreview = null; fileName = ''; document.querySelector('input[name=edit_service_proof_image]').value = ''; startServiceWebcam()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="editProofImage = null; imagePreview = null; fileName = ''; document.querySelector('input[name=edit_service_proof_image]').value = ''"
                                            class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera Button --}}
                                <div x-show="!imagePreview && !showWebcam && !editProofImage">
                                    <button type="button" @click="startServiceWebcam()"
                                    class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera
                                    </button>
                                </div>
                                <input type="file" name="edit_service_proof_image" accept="image/*" class="hidden">
                                <template x-if="editServiceErrors.proof_image">
                                    <p class="mt-1 text-xs text-red-600" x-text="editServiceErrors.proof_image[0]"></p>
                                </template>
                            </div>

                            {{-- Proof of Payment 2 (Optional) --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Proof of Payment 2 <span class="text-gray-400 text-xs font-normal">(Optional)</span>
                                </label>

                                {{-- Webcam2 Section --}}
                                <div x-show="showWebcam2" class="mb-3">
                                    <div class="relative bg-black rounded-xl overflow-hidden shadow-xl" style="height: 320px;">
                                        <video x-ref="editProof2Video" autoplay playsinline
                                            :class="{ 'scale-x-[-1]': isMirrored2 }"
                                            class="w-full h-full object-cover"></video>
                                        <canvas x-ref="editProof2Canvas" class="hidden"></canvas>
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

                                {{-- Existing Proof 2 Thumbnail (or new capture) --}}
                                <div x-show="(imagePreview2 || editProofImage2) && !showWebcam2" class="mb-3 border-2 border-dashed border-green-400 rounded-lg p-3 bg-green-50">
                                    <div class="flex items-center gap-3">
                                        <img :src="imagePreview2 || editProofImage2" class="w-24 h-24 object-cover rounded-md border-2 border-green-500">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="fileName2 || (editProofImage2 ? 'existing_proof2.jpg' : '')"></p>
                                            <p class="text-xs text-green-600 mt-1">✓ Image ready to upload</p>
                                        </div>
                                        <button type="button" @click="editProofImage2 = null; imagePreview2 = null; fileName2 = ''; document.querySelector('input[name=edit_service_proof_image2]').value = ''; startEditWebcam2()"
                                            class="text-blue-600 hover:text-blue-700 p-1" title="Retake photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="editProofImage2 = null; imagePreview2 = null; fileName2 = ''; removeProof2 = true; document.querySelector('input[name=edit_service_proof_image2]').value = ''"
                                            class="text-red-600 hover:text-red-700 p-1" title="Delete photo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Open Camera 2 Button --}}
                                <div x-show="!imagePreview2 && !showWebcam2 && !editProofImage2">
                                    <button type="button" @click="startEditWebcam2()"
                                        class="w-full px-4 py-3 text-sm border-2 border-dashed border-gray-300 rounded-md hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center gap-2 text-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Open Camera (Optional)
                                    </button>
                                </div>
                                <input type="file" name="edit_service_proof_image2" accept="image/*" class="hidden">
                                <input type="hidden" name="remove_proof_image2" :value="removeProof2 ? '1' : ''">
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea x-model="editServiceNotes" rows="3"
                                    class="w-full rounded-md border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors resize-none"
                                    placeholder="Optional notes..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                
                {{-- Modal Footer - Sticky --}}
                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex gap-3">
                    <button type="button" @click="showEditServiceModal = false; stopServiceWebcam(); stopEditWebcam2(); editProofImage = null; imagePreview = null; editProofImage2 = null; imagePreview2 = null; removeProof2 = false;"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="$el.closest('div').previousElementSibling.querySelector('form').requestSubmit()" :disabled="isSubmittingEditService"
                        :class="isSubmittingEditService ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-dark'"
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <template x-if="isSubmittingEditService">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="isSubmittingEditService ? 'Updating...' : 'Update Service'"></span>
                    </button>
                </div>
            </div>
        </div>
        {{-- End Edit Service Modal --}}

        {{-- Delete Confirmation Modal --}}
        <div x-show="showDeleteService !== null" x-cloak class="fixed inset-0 z-50">
            {{-- Background Overlay --}}
            <div x-show="showDeleteService !== null" @click="showDeleteService = null"
                class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Container --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showDeleteService = null"
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
                        Delete Service Service?
                    </h3>

                    {{-- Message --}}
                    <p class="text-sm text-gray-600 text-center mb-6">
                        Are you sure you want to delete this service service? The balance will be restored to the original amount.
                    </p>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <button type="button" @click="showDeleteService = null"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <form :action="`{{ url('finance/report/support-partner') }}/${showDeleteService}`"
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
                <img :src="selectedImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" style="max-height: calc(100vh - 10rem);" alt="Service proof">
            </div>
        </div>
    </div>

    {{-- Pagination AJAX Script --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupServicePagination('services-pagination-container', 'services-section');
        });

        function setupServicePagination(containerId, sectionId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            container.addEventListener('click', function(e) {
                const link = e.target.closest('a[href*="page="]');
                if (!link) return;

                e.preventDefault();
                const url = link.getAttribute('href');

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
                        const newSection = doc.getElementById(sectionId);

                        if (newSection) {
                            document.getElementById(sectionId).innerHTML = newSection.innerHTML;

                            // Re-setup pagination after content update
                            setupServicePagination(containerId, sectionId);
                            
                            // Scroll to pagination area (bottom)
                            setTimeout(() => {
                                const paginationContainer = document.getElementById(containerId);
                                if (paginationContainer) {
                                    paginationContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }, 100);
                        }
                        NProgress.done();
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
