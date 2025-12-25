@extends('layouts.app')

@section('title', 'Manage Work Orders')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Work Orders', 'Manage']" />

    {{-- Alpine Root State --}}
    <div x-data="{
        openModal: false,
        showModal: false,
        selectedDesign: null,
        showData: null,
        mockupPreview: null,
        isDragging: false,
        errors: {},
        isSubmitting: false,
        zoomLevel: 100,
        formData: {
            design_variant_id: null,
            mockup_img_url: null,
            // Cutting
            cutting_pattern_id: null,
            chain_cloth_id: null,
            rib_size_id: null,
            custom_size_chart_img_url: null,
            cutting_notes: '',
            // Printing
            print_ink_id: null,
            finishing_id: null,
            printing_detail_img_url: null,
            printing_notes: '',
            // Printing Placement
            placement_detail_img_url: null,
            placement_notes: '',
            // Sewing
            neck_overdeck_id: null,
            underarm_overdeck_id: null,
            side_split_id: null,
            sewing_label_id: null,
            sewing_detail_img_url: null,
            sewing_notes: '',
            // Packing
            plastic_packing_id: null,
            sticker_id: null,
            hangtag_img_url: null,
            packing_notes: ''
        },
        deletedImages: {
            mockup_img: false,
            custom_size_chart_img: false,
            printing_detail_img: false,
            placement_detail_img: false,
            sewing_detail_img: false,
            hangtag_img: false
        },
        init() {
            // Handle Laravel session flash messages
            @if (session('success')) setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: '{{ session('success') }}', type: 'success' }
                    }));
                }, 300); @endif
    
            @if (session('error')) setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: '{{ session('error') }}', type: 'error' }
                    }));
                }, 300); @endif
    
            // Toast handling from sessionStorage
            const toastMessage = sessionStorage.getItem('toast_message');
            const toastType = sessionStorage.getItem('toast_type');
            if (toastMessage) {
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: toastMessage, type: toastType || 'success' }
                    }));
                    sessionStorage.removeItem('toast_message');
                    sessionStorage.removeItem('toast_type');
                }, 300);
            }
        },
        validateForm() {
            this.errors = {};
            let isValid = true;
    
            // ✅ VALIDASI MOCKUP - WAJIB!
            // Cek apakah ada mockup baru di-upload atau mockup lama sudah ada
            const mockupInput = this.$refs.mockupInput;
            const hasMockupFile = mockupInput && mockupInput.files && mockupInput.files.length > 0;
            const hasExistingMockup = this.formData.mockup_img_url && !this.deletedImages.mockup_img;
            
            if (!hasMockupFile && !hasExistingMockup) {
                this.errors.mockup_img = 'Mockup wajib di-upload!';
                isValid = false;
            }
    
            // Validasi Cutting - SEMUA REQUIRED!
            if (!this.formData.cutting_pattern_id) {
                this.errors.cutting_pattern_id = 'Pola Potong harus dipilih!';
                isValid = false;
            }
            if (!this.formData.chain_cloth_id) {
                this.errors.chain_cloth_id = 'Rantai Kain harus dipilih!';
                isValid = false;
            }
            if (!this.formData.rib_size_id) {
                this.errors.rib_size_id = 'Ukuran Rib harus dipilih!';
                isValid = false;
            }
    
            // Validasi Printing - SEMUA REQUIRED!
            if (!this.formData.print_ink_id) {
                this.errors.print_ink_id = 'Tinta Sablon harus dipilih!';
                isValid = false;
            }
            if (!this.formData.finishing_id) {
                this.errors.finishing_id = 'Finishing harus dipilih!';
                isValid = false;
            }
    
            // Validasi Sewing - SEMUA REQUIRED!
            if (!this.formData.neck_overdeck_id) {
                this.errors.neck_overdeck_id = 'Overdeck Leher harus dipilih!';
                isValid = false;
            }
            if (!this.formData.underarm_overdeck_id) {
                this.errors.underarm_overdeck_id = 'Overdeck Ketiak harus dipilih!';
                isValid = false;
            }
            if (!this.formData.side_split_id) {
                this.errors.side_split_id = 'Belahan Samping harus dipilih!';
                isValid = false;
            }
            if (!this.formData.sewing_label_id) {
                this.errors.sewing_label_id = 'Label Jahit harus dipilih!';
                isValid = false;
            }
    
            // Validasi Packing - SEMUA REQUIRED!
            if (!this.formData.plastic_packing_id) {
                this.errors.plastic_packing_id = 'Plastik Packing harus dipilih!';
                isValid = false;
            }
            if (!this.formData.sticker_id) {
                this.errors.sticker_id = 'Stiker harus dipilih!';
                isValid = false;
            }
    
            return isValid;
        },
        handleSubmit() {
            // Reset errors
            this.errors = {};
    
            // Validasi form
            if (!this.validateForm()) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        message: 'Mohon lengkapi semua field yang wajib diisi!',
                        type: 'error'
                    }
                }));
                return;
            }
    
            // Set submitting state
            this.isSubmitting = true;
    
            // Submit form
            this.$refs.workOrderForm.submit();
        },
        handleMockupUpload(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.mockupPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        handleImageUpload(event, targetField) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.formData[targetField] = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        handleDrop(event) {
            event.preventDefault();
            this.isDragging = false;
    
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                // Create new DataTransfer to assign file to input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.mockupInput.files = dataTransfer.files;
    
                // Trigger upload handler
                this.handleMockupUpload({ target: { files: [file] } });
            }
        },
        handleDragOver(event) {
            event.preventDefault();
            this.isDragging = true;
        },
        handleDragLeave() {
            this.isDragging = false;
        },
        openDesignModal(design) {
            console.log('Opening modal for design:', design);
            console.log('Work Order data:', design.work_order);
    
            this.selectedDesign = design;
            this.formData.design_variant_id = design.id;
            this.errors = {};
            this.mockupPreview = null;
            this.isSubmitting = false;
    
            // Load existing data if work order exists and is created
            if (design.work_order && design.work_order.status === 'created') {
                console.log('Loading existing data...');
                this.loadExistingData(design.work_order);
            } else {
                console.log('Resetting form data (new work order)');
                this.resetFormData();
            }
    
            this.openModal = true;
        },
        loadExistingData(workOrder) {
            console.log('Work Order to load:', workOrder);
    
            // ✅ UPDATED: Using Model Binding Routes (same pattern as Payment)
            // Load work order mockup image
            if (workOrder.mockup_img_url) {
                const mockupUrl = `{{ route('work-orders.serve-mockup-image', ['workOrder' => '__ID__']) }}`.replace('__ID__', workOrder.id);
                this.formData.mockup_img_url = mockupUrl;
                this.mockupPreview = mockupUrl;
            }
    
            // Load cutting data
            if (workOrder.cutting) {
                this.formData.cutting_pattern_id = workOrder.cutting.cutting_pattern_id;
                this.formData.chain_cloth_id = workOrder.cutting.chain_cloth_id;
                this.formData.rib_size_id = workOrder.cutting.rib_size_id;
                
                // Cutting image with model binding
                if (workOrder.cutting.custom_size_chart_img_url) {
                    this.formData.custom_size_chart_img_url = `{{ route('work-orders.serve-cutting-image', ['cutting' => '__ID__']) }}`.replace('__ID__', workOrder.cutting.id);
                }
                this.formData.cutting_notes = workOrder.cutting.notes || '';
            }
    
            // Load printing data
            if (workOrder.printing) {
                this.formData.print_ink_id = workOrder.printing.print_ink_id;
                this.formData.finishing_id = workOrder.printing.finishing_id;
                
                // Printing image with model binding
                if (workOrder.printing.detail_img_url) {
                    this.formData.printing_detail_img_url = `{{ route('work-orders.serve-printing-image', ['printing' => '__ID__']) }}`.replace('__ID__', workOrder.printing.id);
                }
                this.formData.printing_notes = workOrder.printing.notes || '';
            }
    
            // Load printing placement data
            if (workOrder.printing_placement) {
                // Placement image with model binding
                if (workOrder.printing_placement.detail_img_url) {
                    this.formData.placement_detail_img_url = `{{ route('work-orders.serve-placement-image', ['placement' => '__ID__']) }}`.replace('__ID__', workOrder.printing_placement.id);
                }
                this.formData.placement_notes = workOrder.printing_placement.notes || '';
            }
    
            // Load sewing data
            if (workOrder.sewing) {
                this.formData.neck_overdeck_id = workOrder.sewing.neck_overdeck_id;
                this.formData.underarm_overdeck_id = workOrder.sewing.underarm_overdeck_id;
                this.formData.side_split_id = workOrder.sewing.side_split_id;
                this.formData.sewing_label_id = workOrder.sewing.sewing_label_id;
                
                // Sewing image with model binding
                if (workOrder.sewing.detail_img_url) {
                    this.formData.sewing_detail_img_url = `{{ route('work-orders.serve-sewing-image', ['sewing' => '__ID__']) }}`.replace('__ID__', workOrder.sewing.id);
                }
                this.formData.sewing_notes = workOrder.sewing.notes || '';
            }
    
            // Load packing data
            if (workOrder.packing) {
                this.formData.plastic_packing_id = workOrder.packing.plastic_packing_id;
                this.formData.sticker_id = workOrder.packing.sticker_id;
                
                // Packing image with model binding
                if (workOrder.packing.hangtag_img_url) {
                    this.formData.hangtag_img_url = `{{ route('work-orders.serve-packing-image', ['packing' => '__ID__']) }}`.replace('__ID__', workOrder.packing.id);
                }
                this.formData.packing_notes = workOrder.packing.notes || '';
            }
        },
        resetFormData() {
            // Reset all form fields except design_variant_id
            Object.keys(this.formData).forEach(key => {
                if (key !== 'design_variant_id') {
                    this.formData[key] = ['cutting_notes', 'printing_notes', 'placement_notes', 'sewing_notes',
                        'packing_notes'
                    ].includes(key) ? '' : null;
                }
            });
            this.mockupPreview = null;
            // Reset deleted images flags
            Object.keys(this.deletedImages).forEach(key => {
                this.deletedImages[key] = false;
            });
        },
        closeModal() {
            this.openModal = false;
            this.selectedDesign = null;
            this.errors = {};
            this.mockupPreview = null;
            this.isSubmitting = false;
            this.resetFormData();
        },
        openShowModal(design) {
            console.log('Opening show modal for design:', design);
            console.log('Product Category:', design.product_category);
            console.log('Order items:', design.order_items);
            console.log('Sleeves (should be sorted):', design.sleeves);
            console.log('Sizes (should be sorted):', design.sizes);
            this.showData = design;
            this.showModal = true;
            this.zoomLevel = 100;
        },
        closeShowModal() {
            this.showModal = false;
            this.showData = null;
            this.zoomLevel = 100;
        },
        zoomIn() {
            if (this.zoomLevel < 200) {
                this.zoomLevel += 10;
            }
        },
        zoomOut() {
            if (this.zoomLevel > 50) {
                this.zoomLevel -= 10;
            }
        },
        resetZoom() {
            this.zoomLevel = 100;
        }
    }" class="space-y-6">

        {{-- ================= SECTION 1: HEADER ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            {{-- Invoice & Dates --}}
            <div>
                <div class="flex items-center justify-between md:justify-start gap-2 md:gap-4">
                    <h1 class="text-md md:text-2xl font-bold text-gray-900">{{ $order->invoice->invoice_no }}</h1>
                    @php
                        $statusClasses = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'created' => 'bg-green-100 text-green-800',
                        ];
                        $statusClass = $statusClasses[$order->work_order_status] ?? 'bg-gray-100 text-gray-800';

                        // Real-time check
                        $totalDesigns = $order->designVariants->count();
                        $completedDesigns = $order->designVariants
                            ->filter(fn($d) => $d->workOrder && $d->workOrder->status === 'created')
                            ->count();
                        $allCompleted = $totalDesigns > 0 && $completedDesigns === $totalDesigns;
                    @endphp
                    <div class="px-3 py-2 rounded-full text-xs md:text-sm md:px-4 font-bold {{ $statusClass }}"
                        x-data="{ completed: {{ $allCompleted ? 'true' : 'false' }} }">
                        <span x-show="!completed">⏳
                            {{ strtoupper(str_replace('_', ' ', $order->work_order_status)) }}</span>
                        <span x-show="completed">✅ CREATED</span>
                    </div>
                </div>
                {{-- Order Date & Deadline --}}
                <div class="flex items-center justify-between sm:justify-start sm:gap-6 text-sm text-gray-600 mt-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="hidden sm:inline text-gray-600">Order Date:</span>
                        <span
                            class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline text-gray-600">Deadline:</span>
                        <span
                            class="font-medium text-gray-900">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d M Y') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 2: DESIGN VARIANTS ================= --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($order->designVariants as $index => $design)
                <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
                    <div class="flex items-center gap-3 md:gap-4">
                        {{-- 1. Image (Kiri) - dari work_orders.mockup_img_url --}}
                        <div class="flex-shrink-0">
                            @if ($design->workOrder && $design->workOrder->mockup_img_url)
                                {{-- ✅ UPDATED: Use model binding route for mockup image --}}
                                <img src="{{ route('work-orders.serve-mockup-image', ['workOrder' => $design->workOrder->id]) }}" 
                                    alt="{{ $design->design_name }}"
                                    class="w-16 h-16 md:w-20 md:h-20 object-cover rounded-lg border-2 border-gray-200">
                            @elseif($design->orderItem->mockup_img_url ?? null)
                                <img src="{{ $design->orderItem->mockup_img_url }}" alt="{{ $design->design_name }}"
                                    class="w-16 h-16 md:w-20 md:h-20 object-cover rounded-lg border-2 border-gray-200">
                            @else
                                <div
                                    class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-lg border-2 border-gray-200 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- 2. Nama Design & Status (Kiri-Tengah) --}}
                        <div class="flex-1 min-w-0 flex flex-col justify-center gap-1">
                            <h3 class="text-base md:text-lg font-semibold text-gray-900">
                                Variant {{ $index + 1 }}
                                @if ($design->design_name && $design->color)
                                    <span class="text-gray-600 italic">( {{ $design->design_name }} - {{ $design->color }} )</span>
                                @elseif ($design->design_name)
                                    <span class="text-gray-600 italic">( {{ $design->design_name }} )</span>
                                @endif
                            </h3>
                            @if ($design->workOrder && $design->workOrder->status === 'created')
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 w-fit">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Created
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 w-fit">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Pending
                                </span>
                            @endif
                        </div>

                        {{-- 3. Action Buttons (Kanan) - 3 Dots Dropdown --}}
                        <div class="flex-shrink-0 flex items-center">
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
                            }" x-init="$watch('open', value => {
                                if (value) {
                                    const closeOnScroll = () => { open = false; };
                                    window.addEventListener('scroll', closeOnScroll, { once: true });
                                    window.addEventListener('resize', closeOnScroll, { once: true });
                                }
                            })">
                                {{-- Three Dot Button HORIZONTAL --}}
                                <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                    class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                    title="Actions">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>

                                {{-- Dropdown Menu with Fixed Position --}}
                                <div x-show="open" @click.away="open = false" x-cloak x-ref="dropdown"
                                    :style="dropdownStyle"
                                    class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">

                                    @if ($design->workOrder && $design->workOrder->status === 'created')
                                        {{-- Create (Disabled) --}}
                                        <button disabled
                                            class="w-full px-4 py-2 text-sm text-gray-400 bg-gray-50 flex items-center gap-2 text-left cursor-not-allowed"
                                            title="Already created">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Create
                                        </button>

                                        {{-- Edit (Aktif) --}}
                                        <button
                                            @click="openDesignModal({
                                                id: {{ $design->id }},
                                                design_name: {{ Js::from($design->design_name) }},
                                                work_order: {{ Js::from([
                                                    'id' => $design->workOrder->id ?? null,
                                                    'mockup_img_url' => $design->workOrder->mockup_img_url ?? null,
                                                    'status' => $design->workOrder->status ?? null,
                                                    'cutting' => $design->workOrder->cutting
                                                        ? [
                                                            'id' => $design->workOrder->cutting->id,
                                                            'cutting_pattern_id' => $design->workOrder->cutting->cutting_pattern_id,
                                                            'chain_cloth_id' => $design->workOrder->cutting->chain_cloth_id,
                                                            'rib_size_id' => $design->workOrder->cutting->rib_size_id,
                                                            'custom_size_chart_img_url' => $design->workOrder->cutting->custom_size_chart_img_url,
                                                            'notes' => $design->workOrder->cutting->notes,
                                                        ]
                                                        : null,
                                                    'printing' => $design->workOrder->printing
                                                        ? [
                                                            'id' => $design->workOrder->printing->id,
                                                            'print_ink_id' => $design->workOrder->printing->print_ink_id,
                                                            'finishing_id' => $design->workOrder->printing->finishing_id,
                                                            'detail_img_url' => $design->workOrder->printing->detail_img_url,
                                                            'notes' => $design->workOrder->printing->notes,
                                                        ]
                                                        : null,
                                                    'printing_placement' => $design->workOrder->printingPlacement
                                                        ? [
                                                            'id' => $design->workOrder->printingPlacement->id,
                                                            'detail_img_url' => $design->workOrder->printingPlacement->detail_img_url,
                                                            'notes' => $design->workOrder->printingPlacement->notes,
                                                        ]
                                                        : null,
                                                    'sewing' => $design->workOrder->sewing
                                                        ? [
                                                            'id' => $design->workOrder->sewing->id,
                                                            'neck_overdeck_id' => $design->workOrder->sewing->neck_overdeck_id,
                                                            'underarm_overdeck_id' => $design->workOrder->sewing->underarm_overdeck_id,
                                                            'side_split_id' => $design->workOrder->sewing->side_split_id,
                                                            'sewing_label_id' => $design->workOrder->sewing->sewing_label_id,
                                                            'detail_img_url' => $design->workOrder->sewing->detail_img_url,
                                                            'notes' => $design->workOrder->sewing->notes,
                                                        ]
                                                        : null,
                                                    'packing' => $design->workOrder->packing
                                                        ? [
                                                            'id' => $design->workOrder->packing->id,
                                                            'plastic_packing_id' => $design->workOrder->packing->plastic_packing_id,
                                                            'sticker_id' => $design->workOrder->packing->sticker_id,
                                                            'hangtag_img_url' => $design->workOrder->packing->hangtag_img_url,
                                                            'notes' => $design->workOrder->packing->notes,
                                                        ]
                                                        : null,
                                                ]) }}
                                            }); open = false"
                                            class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 text-left">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>

                                        {{-- Show (Aktif) --}}
                                        @php
                                            // Get order items for this design
                                            $designOrderItems = $order->orderItems->where(
                                                'design_variant_id',
                                                $design->id,
                                            );

                                            // Get unique sleeve and size IDs from order items
                                            $usedSleeveIds = $designOrderItems
                                                ->pluck('sleeve_id')
                                                ->unique()
                                                ->filter()
                                                ->values()
                                                ->toArray();
                                            $usedSizeIds = $designOrderItems
                                                ->pluck('size_id')
                                                ->unique()
                                                ->filter()
                                                ->values()
                                                ->toArray();

                                            // Query sleeves/sizes that are used, with proper ORDER BY sort_order
                                            $usedSleevesQuery = \App\Models\MaterialSleeve::whereIn('id', $usedSleeveIds)
                                                ->orderBy('sort_order')
                                                ->get();
                                            
                                            \Log::info('Used Sleeve IDs: ' . json_encode($usedSleeveIds));
                                            \Log::info('Query result with sort_order:', $usedSleevesQuery->map(fn($s) => [
                                                'id' => $s->id,
                                                'name' => $s->sleeve_name,
                                                'sort' => $s->sort_order
                                            ])->toArray());
                                            
                                            $usedSleeves = $usedSleevesQuery->pluck('sleeve_name')->toArray();
                                            
                                            $usedSizes = \App\Models\MaterialSize::whereIn('id', $usedSizeIds)
                                                ->orderBy('sort_order')
                                                ->pluck('size_name')
                                                ->toArray();

                                            // Get all sleeve/size names for filling (ordered by sort_order)
                                            $allSleeves = \App\Models\MaterialSleeve::orderBy('sort_order')->pluck('sleeve_name')->toArray();
                                            $allSizes = \App\Models\MaterialSize::orderBy('sort_order')->pluck('size_name')->toArray();

                                            // Fill sleeves to minimum 4 - combine used + remaining BUT maintain global sort_order
                                            if (count($usedSleeves) < 4) {
                                                // Get sleeves that are NOT used
                                                $remainingSleeves = array_diff($allSleeves, $usedSleeves);
                                                $neededCount = 4 - count($usedSleeves);
                                                // Combine used + needed remaining
                                                $combined = array_merge($usedSleeves, array_slice($remainingSleeves, 0, $neededCount));
                                                // Re-sort based on global $allSleeves order
                                                $displaySleeves = array_values(array_intersect($allSleeves, $combined));
                                            } else {
                                                $displaySleeves = $usedSleeves;
                                            }

                                            // Fill sizes to minimum 6 - combine used + remaining BUT maintain global sort_order
                                            if (count($usedSizes) < 6) {
                                                $remainingSizes = array_diff($allSizes, $usedSizes);
                                                $neededCount = 6 - count($usedSizes);
                                                $combined = array_merge($usedSizes, array_slice($remainingSizes, 0, $neededCount));
                                                // Re-sort based on global $allSizes order
                                                $displaySizes = array_values(array_intersect($allSizes, $combined));
                                            } else {
                                                $displaySizes = $usedSizes;
                                            }

                                            // Prepare order items data
                                            $orderItemsData = $designOrderItems
                                                ->map(
                                                    fn($item) => [
                                                        'size_name' => $item->size->name ?? '-',
                                                        'sleeve_name' => $item->sleeve->name ?? '-',
                                                        'qty' => $item->qty ?? 0,
                                                    ],
                                                )
                                                ->values();

                                            // Debug: Log the sorted data
                                            \Log::info('Design Variant ID: ' . $design->id);
                                            \Log::info('Display Sleeves (sorted): ' . json_encode($displaySleeves));
                                            \Log::info('Display Sizes (sorted): ' . json_encode($displaySizes));
                                        @endphp
                                        <button
                                            @click="openShowModal({
                                                id: {{ $design->id }},
                                                variant_index: {{ $index + 1 }},
                                                design_name: {{ Js::from($design->design_name) }},
                                                color: {{ Js::from($design->color ?? '-') }},
                                                product_category: {{ Js::from($order->productCategory->name ?? '-') }},
                                                material_category: {{ Js::from($order->materialCategory->name ?? '-') }},
                                                material_texture: {{ Js::from($order->materialTexture->name ?? '-') }},
                                                sleeves: {{ Js::from($displaySleeves) }},
                                                sizes: {{ Js::from($displaySizes) }},
                                                order_items: {{ Js::from($orderItemsData) }},
                                                shipping_type: {{ Js::from(ucfirst($order->shipping_type)) }},
                                                work_order: {{ Js::from([
                                                    'id' => $design->workOrder->id ?? null,
                                                    'mockup_img_url' => $design->workOrder->mockup_img_url ?? null,
                                                    'status' => $design->workOrder->status ?? null,
                                                    'cutting' => $design->workOrder->cutting
                                                        ? [
                                                            'id' => $design->workOrder->cutting->id,
                                                            'cutting_pattern_id' => $design->workOrder->cutting->cutting_pattern_id,
                                                            'cutting_pattern_name' => $design->workOrder->cutting->cuttingPattern->name ?? '-',
                                                            'chain_cloth_id' => $design->workOrder->cutting->chain_cloth_id,
                                                            'chain_cloth_name' => $design->workOrder->cutting->chainCloth->name ?? '-',
                                                            'rib_size_id' => $design->workOrder->cutting->rib_size_id,
                                                            'rib_size_name' => $design->workOrder->cutting->ribSize->name ?? '-',
                                                            'custom_size_chart_img_url' => $design->workOrder->cutting->custom_size_chart_img_url,
                                                            'notes' => $design->workOrder->cutting->notes,
                                                        ]
                                                        : null,
                                                    'printing' => $design->workOrder->printing
                                                        ? [
                                                            'id' => $design->workOrder->printing->id,
                                                            'print_ink_id' => $design->workOrder->printing->print_ink_id,
                                                            'print_ink_name' => $design->workOrder->printing->printInk->name ?? '-',
                                                            'finishing_id' => $design->workOrder->printing->finishing_id,
                                                            'finishing_name' => $design->workOrder->printing->finishing->name ?? '-',
                                                            'detail_img_url' => $design->workOrder->printing->detail_img_url,
                                                            'notes' => $design->workOrder->printing->notes,
                                                        ]
                                                        : null,
                                                    'printing_placement' => $design->workOrder->printingPlacement
                                                        ? [
                                                            'id' => $design->workOrder->printingPlacement->id,
                                                            'detail_img_url' => $design->workOrder->printingPlacement->detail_img_url,
                                                            'notes' => $design->workOrder->printingPlacement->notes,
                                                        ]
                                                        : null,
                                                    'sewing' => $design->workOrder->sewing
                                                        ? [
                                                            'id' => $design->workOrder->sewing->id,
                                                            'neck_overdeck_id' => $design->workOrder->sewing->neck_overdeck_id,
                                                            'neck_overdeck_name' => $design->workOrder->sewing->neckOverdeck->name ?? '-',
                                                            'underarm_overdeck_id' => $design->workOrder->sewing->underarm_overdeck_id,
                                                            'underarm_overdeck_name' => $design->workOrder->sewing->underarmOverdeck->name ?? '-',
                                                            'side_split_id' => $design->workOrder->sewing->side_split_id,
                                                            'side_split_name' => $design->workOrder->sewing->sideSplit->name ?? '-',
                                                            'sewing_label_id' => $design->workOrder->sewing->sewing_label_id,
                                                            'sewing_label_name' => $design->workOrder->sewing->sewingLabel->name ?? '-',
                                                            'detail_img_url' => $design->workOrder->sewing->detail_img_url,
                                                            'notes' => $design->workOrder->sewing->notes,
                                                        ]
                                                        : null,
                                                    'packing' => $design->workOrder->packing
                                                        ? [
                                                            'id' => $design->workOrder->packing->id,
                                                            'plastic_packing_id' => $design->workOrder->packing->plastic_packing_id,
                                                            'plastic_packing_name' => $design->workOrder->packing->plasticPacking->name ?? '-',
                                                            'sticker_id' => $design->workOrder->packing->sticker_id,
                                                            'sticker_name' => $design->workOrder->packing->sticker->name ?? '-',
                                                            'hangtag_img_url' => $design->workOrder->packing->hangtag_img_url,
                                                            'notes' => $design->workOrder->packing->notes,
                                                        ]
                                                        : null,
                                                ]) }}
                                            }); open = false"
                                            class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 text-left">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Show
                                        </button>
                                    @else
                                        {{-- Create (Aktif) --}}
                                        <button
                                            @click="openDesignModal({
                                                id: {{ $design->id }},
                                                design_name: {{ Js::from($design->design_name) }},
                                                work_order: null
                                            }); open = false"
                                            class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 text-left">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Create
                                        </button>

                                        {{-- Edit (Disabled) --}}
                                        <button disabled
                                            class="w-full px-4 py-2 text-sm text-gray-400 bg-gray-50 flex items-center gap-2 text-left cursor-not-allowed"
                                            title="No work order yet">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>

                                        {{-- Show (Disabled) --}}
                                        <button disabled
                                            class="w-full px-4 py-2 text-sm text-gray-400 bg-gray-50 flex items-center gap-2 text-left cursor-not-allowed"
                                            title="No work order yet">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Show
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Work Order Progress --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <div class="flex flex-col justify-center">
                <h3 class="text-base md:text-lg font-semibold text-gray-900">Work Order Progress</h3>
                <p class="text-xs md:text-sm text-gray-500 mt-1">
                    @if ($order->work_order_status === 'created')
                        ✅ All work orders completed!
                    @else
                        Complete all designs to update work order status
                    @endif
                </p>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex-1 bg-gray-200 rounded-full h-2.5">
                        <div class="bg-primary h-2.5 rounded-full transition-all duration-500"
                            style="width: {{ $totalDesigns > 0 ? ($completedDesigns / $totalDesigns) * 100 : 0 }}%">
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">
                        {{ $completedDesigns }} / {{ $totalDesigns }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ================= CREATE/EDIT MODAL (Combined) ================= --}}
        <div x-show="openModal" x-cloak x-transition.opacity class="fixed inset-0 z-50">
            {{-- Background Overlay --}}
            <div x-show="openModal" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="closeModal()" 
                    class="relative bg-white rounded-xl shadow-lg w-full max-w-5xl"
                    style="height: min(calc(100vh - 6rem), 850px); min-height: 0; display: flex; flex-direction: column;">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <span x-text="selectedDesign?.work_order?.id ? 'Edit' : 'Create'"></span> Work Order - 
                            <span x-text="selectedDesign ? 'Variant Design ' + (@json($order->designVariants->pluck('id')->toArray()).indexOf(selectedDesign.id) + 1) : ''"></span>
                            <span x-show="selectedDesign?.design_name" class="text-gray-600 font-normal">
                                (<span x-text="selectedDesign?.design_name"></span>)
                            </span>
                        </h3>
                        <button @click="closeModal()" type="button" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                    </div>

                    {{-- Scrollable Content --}}
                    <div class="overflow-y-auto flex-1">
                        {{-- Include Form Body --}}
                        @include('pages.admin.work-orders.partials.form-body')
                    </div>

                    {{-- Fixed Footer --}}
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                        <button type="button" @click="closeModal()" :disabled="isSubmitting"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            Cancel
                        </button>
                        <button type="button" form="workOrderForm" @click="handleSubmit()" :disabled="isSubmitting"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed flex items-center gap-2">
                            {{-- Loading Spinner --}}
                            <svg x-show="isSubmitting" x-cloak class="animate-spin h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="isSubmitting ? 'Processing...' : 'Save Work Order'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SHOW MODAL (Separated File) ================= --}}
        @include('pages.admin.work-orders.partials.show-modal')

    </div>

    @push('styles')
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // All image validations removed - images are optional per migration
        </script>
    @endpush
@endsection
