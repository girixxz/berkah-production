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
    
            // Semua image sudah optional, tidak perlu validasi frontend
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
    
            // Load work order data
            this.formData.mockup_img_url = workOrder.mockup_img_url;
            this.mockupPreview = workOrder.mockup_img_url;
    
            // Load cutting data
            if (workOrder.cutting) {
                this.formData.cutting_pattern_id = workOrder.cutting.cutting_pattern_id;
                this.formData.chain_cloth_id = workOrder.cutting.chain_cloth_id;
                this.formData.rib_size_id = workOrder.cutting.rib_size_id;
                this.formData.custom_size_chart_img_url = workOrder.cutting.custom_size_chart_img_url;
                this.formData.cutting_notes = workOrder.cutting.notes || '';
            }
    
            // Load printing data
            if (workOrder.printing) {
                this.formData.print_ink_id = workOrder.printing.print_ink_id;
                this.formData.finishing_id = workOrder.printing.finishing_id;
                this.formData.printing_detail_img_url = workOrder.printing.detail_img_url;
                this.formData.printing_notes = workOrder.printing.notes || '';
            }
    
            // Load printing placement data
            if (workOrder.printing_placement) {
                this.formData.placement_detail_img_url = workOrder.printing_placement.detail_img_url;
                this.formData.placement_notes = workOrder.printing_placement.notes || '';
            }
    
            // Load sewing data
            if (workOrder.sewing) {
                this.formData.neck_overdeck_id = workOrder.sewing.neck_overdeck_id;
                this.formData.underarm_overdeck_id = workOrder.sewing.underarm_overdeck_id;
                this.formData.side_split_id = workOrder.sewing.side_split_id;
                this.formData.sewing_label_id = workOrder.sewing.sewing_label_id;
                this.formData.sewing_detail_img_url = workOrder.sewing.detail_img_url;
                this.formData.sewing_notes = workOrder.sewing.notes || '';
            }
    
            // Load packing data
            if (workOrder.packing) {
                this.formData.plastic_packing_id = workOrder.packing.plastic_packing_id;
                this.formData.sticker_id = workOrder.packing.sticker_id;
                this.formData.hangtag_img_url = workOrder.packing.hangtag_img_url;
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
            this.showData = design;
            this.showModal = true;
        },
        closeShowModal() {
            this.showModal = false;
            this.showData = null;
        },
        isAllDesignsCompleted() {
            const designs = {{ Js::from($order->designVariants) }};
            return designs.every(design => design.work_order && design.work_order.status === 'created');
        }
    }" class="space-y-6">

        {{-- ================= SECTION 1: HEADER ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <div class="flex justify-between gap-4">
                {{-- Invoice & Status --}}
                <div class="flex items-center gap-2 md:gap-4 justify-between">
                    <h1 class="text-md md:text-2xl font-bold text-gray-900">{{ $order->invoice->invoice_no }}</h1>
                    @php
                        $statusClasses = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'created' => 'bg-green-100 text-green-800',
                        ];
                        $statusClass = $statusClasses[$order->work_order_status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <div class="px-3 py-2 rounded-full text-xs md:text-sm md:px-4 font-bold {{ $statusClass }}">
                        {{ strtoupper(str_replace('_', ' ', $order->work_order_status)) }}
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-3">
                    {{-- Show Work Order Button - Icon only on mobile, full text on desktop --}}
                    <button @click="openInvoiceModal = true"
                        class="flex px-3 md:px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark items-center gap-2 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="hidden md:inline">Show Work Order</span>
                    </button>
                </div>
            </div>
            {{-- Order Date & Deadline - Responsive Layout --}}
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

        {{-- ================= SECTION 2: DESIGN VARIANTS ================= --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($order->designVariants as $index => $design)
                <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
                    <div class="flex items-center gap-3 md:gap-4">
                        {{-- 1. Image (Kiri) - dari work_orders.mockup_img_url --}}
                        <div class="flex-shrink-0">
                            @if ($design->workOrder && $design->workOrder->mockup_img_url)
                                <img src="{{ $design->workOrder->mockup_img_url }}" alt="{{ $design->design_name }}"
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
                                Variant Design {{ $index + 1 }}
                                @if ($design->design_name)
                                    <span class="text-gray-600">({{ $design->design_name }})</span>
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
                                                            'cutting_pattern_id' => $design->workOrder->cutting->cutting_pattern_id,
                                                            'chain_cloth_id' => $design->workOrder->cutting->chain_cloth_id,
                                                            'rib_size_id' => $design->workOrder->cutting->rib_size_id,
                                                            'custom_size_chart_img_url' => $design->workOrder->cutting->custom_size_chart_img_url,
                                                            'notes' => $design->workOrder->cutting->notes,
                                                        ]
                                                        : null,
                                                    'printing' => $design->workOrder->printing
                                                        ? [
                                                            'print_ink_id' => $design->workOrder->printing->print_ink_id,
                                                            'finishing_id' => $design->workOrder->printing->finishing_id,
                                                            'detail_img_url' => $design->workOrder->printing->detail_img_url,
                                                            'notes' => $design->workOrder->printing->notes,
                                                        ]
                                                        : null,
                                                    'printing_placement' => $design->workOrder->printingPlacement
                                                        ? [
                                                            'detail_img_url' => $design->workOrder->printingPlacement->detail_img_url,
                                                            'notes' => $design->workOrder->printingPlacement->notes,
                                                        ]
                                                        : null,
                                                    'sewing' => $design->workOrder->sewing
                                                        ? [
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
                                                            'plastic_packing_id' => $design->workOrder->packing->plastic_packing_id,
                                                            'sticker_id' => $design->workOrder->packing->sticker_id,
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

                                            // Get unique sleeves and sizes from order items
                                            $usedSleeves = $designOrderItems
                                                ->pluck('sleeve.name')
                                                ->unique()
                                                ->filter()
                                                ->values()
                                                ->toArray();
                                            $usedSizes = $designOrderItems
                                                ->pluck('size.name')
                                                ->unique()
                                                ->filter()
                                                ->values()
                                                ->toArray();

                                            // Get all sleeves and sizes from database
                                            $allSleeves = \App\Models\MaterialSleeve::all()->pluck('name')->toArray();
                                            $allSizes = \App\Models\MaterialSize::all()->pluck('name')->toArray();

                                            // Fill sleeves to minimum 4
                                            $displaySleeves = $usedSleeves;
                                            if (count($displaySleeves) < 4) {
                                                $remainingSleeves = array_diff($allSleeves, $usedSleeves);
                                                $neededCount = 4 - count($displaySleeves);
                                                $displaySleeves = array_merge(
                                                    $displaySleeves,
                                                    array_slice($remainingSleeves, 0, $neededCount),
                                                );
                                            }

                                            // Fill sizes to minimum 6
                                            $displaySizes = $usedSizes;
                                            if (count($displaySizes) < 6) {
                                                $remainingSizes = array_diff($allSizes, $usedSizes);
                                                $neededCount = 6 - count($displaySizes);
                                                $displaySizes = array_merge(
                                                    $displaySizes,
                                                    array_slice($remainingSizes, 0, $neededCount),
                                                );
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
                                        @endphp
                                        <button
                                            @click="openShowModal({
                                                id: {{ $design->id }},
                                                design_name: {{ Js::from($design->design_name) }},
                                                product_category: {{ Js::from($order->productCategory->name ?? '-') }},
                                                material_category: {{ Js::from($order->materialCategory->name ?? '-') }},
                                                material_texture: {{ Js::from($order->materialTexture->name ?? '-') }},
                                                sleeves: {{ Js::from($displaySleeves) }},
                                                sizes: {{ Js::from($displaySizes) }},
                                                order_items: {{ Js::from($orderItemsData) }},
                                                work_order: {{ Js::from([
                                                    'id' => $design->workOrder->id ?? null,
                                                    'mockup_img_url' => $design->workOrder->mockup_img_url ?? null,
                                                    'status' => $design->workOrder->status ?? null,
                                                    'cutting' => $design->workOrder->cutting
                                                        ? [
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
                                                            'detail_img_url' => $design->workOrder->printingPlacement->detail_img_url,
                                                            'notes' => $design->workOrder->printingPlacement->notes,
                                                        ]
                                                        : null,
                                                    'sewing' => $design->workOrder->sewing
                                                        ? [
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

        {{-- Finalize Button --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Finalize Work Orders</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Complete all designs to enable finalization and PDF generation
                    </p>
                </div>
                <button :disabled="!isAllDesignsCompleted()"
                    @click="if(isAllDesignsCompleted()) { 
                            if(confirm('Are you sure you want to finalize all work orders? This will generate PDFs for all designs.')) {
                                window.location.href = '{{ route('admin.work-orders.finalize', $order->id) }}';
                            }
                        }"
                    :class="isAllDesignsCompleted() ?
                        'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer' :
                        'bg-gray-300 text-gray-500 cursor-not-allowed'"
                    class="px-6 py-3 text-sm font-medium rounded-lg transition-colors">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Finalize All Work Orders
                    </span>
                </button>
            </div>
        </div>

        {{-- ================= WORK ORDER MODAL ================= --}}
        <div x-show="openModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.away="closeModal()" class="bg-white rounded-xl shadow-lg w-full max-w-5xl">

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between p-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Work Order - <span
                                x-text="selectedDesign ? 'Variant Design ' + (@json($order->designVariants->pluck('id')->toArray()).indexOf(selectedDesign.id) + 1) : ''"></span>
                            <span x-show="selectedDesign?.design_name" class="text-gray-600 font-normal">
                                (<span x-text="selectedDesign?.design_name"></span>)
                            </span>
                        </h3>
                        <button @click="closeModal()" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <form x-ref="workOrderForm" method="POST"
                        :action="selectedDesign?.work_order ? '{{ url('admin/work-orders') }}/' + selectedDesign.work_order.id :
                            '{{ route('admin.work-orders.store') }}'"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_method" :value="selectedDesign?.work_order ? 'PUT' : 'POST'">
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <input type="hidden" name="design_variant_id" x-model="formData.design_variant_id">

                        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">

                            {{-- Display Backend Validation Errors --}}
                            @if ($errors->any())
                                <div class="bg-red-50 border border-red-200 rounded-md p-3">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-semibold text-red-800 mb-1">Terdapat kesalahan
                                                validasi:
                                            </h4>
                                            <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- ===== UPLOAD MOCKUP ===== --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Mockup Image
                                </label>

                                {{-- Image Preview --}}
                                <div x-show="mockupPreview" class="mb-3">
                                    <img :src="mockupPreview" alt="Mockup preview"
                                        class="w-full max-w-md h-auto rounded-md border border-gray-200 mx-auto">
                                </div>

                                {{-- Upload Area with Drag & Drop --}}
                                <div class="flex items-center justify-center w-full" @drop="handleDrop($event)"
                                    @dragover="handleDragOver($event)" @dragleave="handleDragLeave()">
                                    <label for="mockup-upload"
                                        :class="isDragging ? 'border-primary bg-primary/5' : errors.mockup_img ?
                                            'border-red-500 bg-red-50' : 'border-gray-200 bg-white'"
                                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-md cursor-pointer hover:bg-gray-50 transition-all">
                                        <div class="flex flex-col items-center justify-center pt-4 pb-4">
                                            <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            <p class="mb-1 text-sm text-gray-500">
                                                <span class="font-semibold">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-500">PNG, JPG, JPEG (MAX. 5MB)</p>
                                        </div>
                                        <input x-ref="mockupInput" id="mockup-upload" type="file" name="mockup_img"
                                            class="hidden" accept="image/*" @change="handleMockupUpload($event)" />
                                    </label>
                                </div>
                                <p x-show="errors.mockup_img" x-cloak x-text="errors.mockup_img"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- 🔪 CUTTING SECTION --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="text-lg">🔪</span>
                                    Cutting Section
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Cutting Pattern <span class="text-red-600">*</span>
                                        </label>
                                        <select name="cutting_pattern_id" x-model="formData.cutting_pattern_id"
                                            :class="errors.cutting_pattern_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Pattern --</option>
                                            @foreach ($masterData['cuttingPatterns'] as $pattern)
                                                <option value="{{ $pattern->id }}">{{ $pattern->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.cutting_pattern_id" x-cloak x-text="errors.cutting_pattern_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Chain Cloth <span class="text-red-600">*</span>
                                        </label>
                                        <select name="chain_cloth_id" x-model="formData.chain_cloth_id"
                                            :class="errors.chain_cloth_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Cloth --</option>
                                            @foreach ($masterData['chainCloths'] as $cloth)
                                                <option value="{{ $cloth->id }}">{{ $cloth->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.chain_cloth_id" x-cloak x-text="errors.chain_cloth_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Rib Size <span class="text-red-600">*</span>
                                        </label>
                                        <select name="rib_size_id" x-model="formData.rib_size_id"
                                            :class="errors.rib_size_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Size --</option>
                                            @foreach ($masterData['ribSizes'] as $size)
                                                <option value="{{ $size->id }}">{{ $size->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.rib_size_id" x-cloak x-text="errors.rib_size_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Custom Size Chart Image
                                        </label>

                                        {{-- Show existing image if available --}}
                                        <div x-show="formData.custom_size_chart_img_url" class="mb-2">
                                            <img :src="formData.custom_size_chart_img_url" alt="Size chart"
                                                class="w-32 h-32 object-cover rounded border border-gray-200">
                                            <p class="text-xs text-gray-500 mt-1">Current image (upload new to replace)</p>
                                        </div>

                                        <input type="file" name="custom_size_chart_img" accept="image/*"
                                            @change="handleImageUpload($event, 'custom_size_chart_img_url')"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700">
                                        <p class="mt-1 text-xs text-gray-500">Optional: Upload custom size chart</p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea name="cutting_notes" x-model="formData.cutting_notes" rows="2"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                            placeholder="Optional notes for cutting..."></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- 🎨 PRINTING SECTION --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="text-lg">🎨</span>
                                    Printing Section
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Print Ink <span class="text-red-600">*</span>
                                        </label>
                                        <select name="print_ink_id" x-model="formData.print_ink_id"
                                            :class="errors.print_ink_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Ink --</option>
                                            @foreach ($masterData['printInks'] as $ink)
                                                <option value="{{ $ink->id }}">{{ $ink->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.print_ink_id" x-cloak x-text="errors.print_ink_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Finishing <span class="text-red-600">*</span>
                                        </label>
                                        <select name="finishing_id" x-model="formData.finishing_id"
                                            :class="errors.finishing_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Finishing --</option>
                                            @foreach ($masterData['finishings'] as $finishing)
                                                <option value="{{ $finishing->id }}">{{ $finishing->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.finishing_id" x-cloak x-text="errors.finishing_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Detail Image
                                        </label>

                                        {{-- Show existing image if available --}}
                                        <div x-show="formData.printing_detail_img_url" class="mb-2">
                                            <img :src="formData.printing_detail_img_url" alt="Printing detail"
                                                class="w-32 h-32 object-cover rounded border border-gray-200">
                                            <p class="text-xs text-gray-500 mt-1">Current image (upload new to replace)</p>
                                        </div>

                                        <input type="file" name="printing_detail_img" accept="image/*"
                                            @change="handleImageUpload($event, 'printing_detail_img_url')"
                                            :class="errors.printing_detail_img ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                        <p x-show="errors.printing_detail_img" x-cloak x-text="errors.printing_detail_img"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea name="printing_notes" x-model="formData.printing_notes" rows="2"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                            placeholder="Optional notes for printing..."></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- 📍 PRINTING PLACEMENT SECTION --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="text-lg">📍</span>
                                    Printing Placement Section
                                </h4>
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Placement Detail Image
                                        </label>

                                        {{-- Show existing image if available --}}
                                        <div x-show="formData.placement_detail_img_url" class="mb-2">
                                            <img :src="formData.placement_detail_img_url" alt="Placement detail"
                                                class="w-32 h-32 object-cover rounded border border-gray-200">
                                            <p class="text-xs text-gray-500 mt-1">Current image (upload new to replace)</p>
                                        </div>

                                        <input type="file" name="placement_detail_img" accept="image/*"
                                            @change="handleImageUpload($event, 'placement_detail_img_url')"
                                            :class="{ 'border-red-500': errors.placement_detail_img }"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700">
                                        <p x-show="errors.placement_detail_img" x-text="errors.placement_detail_img"
                                            class="mt-1 text-xs text-red-500"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea name="placement_notes" x-model="formData.placement_notes" rows="2"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                            placeholder="Optional notes for placement..."></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- 🧵 SEWING SECTION --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="text-lg">🧵</span>
                                    Sewing Section
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Neck Overdeck <span class="text-red-600">*</span>
                                        </label>
                                        <select name="neck_overdeck_id" x-model="formData.neck_overdeck_id"
                                            :class="errors.neck_overdeck_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Neck Overdeck --</option>
                                            @foreach ($masterData['neckOverdecks'] as $overdeck)
                                                <option value="{{ $overdeck->id }}">{{ $overdeck->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.neck_overdeck_id" x-cloak x-text="errors.neck_overdeck_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Underarm Overdeck <span class="text-red-600">*</span>
                                        </label>
                                        <select name="underarm_overdeck_id" x-model="formData.underarm_overdeck_id"
                                            :class="errors.underarm_overdeck_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Underarm Overdeck --</option>
                                            @foreach ($masterData['underarmOverdecks'] as $overdeck)
                                                <option value="{{ $overdeck->id }}">{{ $overdeck->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.underarm_overdeck_id" x-cloak
                                            x-text="errors.underarm_overdeck_id" class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Side Split <span class="text-red-600">*</span>
                                        </label>
                                        <select name="side_split_id" x-model="formData.side_split_id"
                                            :class="errors.side_split_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Side Split --</option>
                                            @foreach ($masterData['sideSplits'] as $split)
                                                <option value="{{ $split->id }}">{{ $split->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.side_split_id" x-cloak x-text="errors.side_split_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Sewing Label <span class="text-red-600">*</span>
                                        </label>
                                        <select name="sewing_label_id" x-model="formData.sewing_label_id"
                                            :class="errors.sewing_label_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Label --</option>
                                            @foreach ($masterData['sewingLabels'] as $label)
                                                <option value="{{ $label->id }}">{{ $label->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.sewing_label_id" x-cloak x-text="errors.sewing_label_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Detail Image
                                        </label>

                                        {{-- Show existing image if available --}}
                                        <div x-show="formData.sewing_detail_img_url" class="mb-2">
                                            <img :src="formData.sewing_detail_img_url" alt="Sewing detail"
                                                class="w-32 h-32 object-cover rounded border border-gray-200">
                                            <p class="text-xs text-gray-500 mt-1">Current image (upload new to replace)</p>
                                        </div>

                                        <input type="file" name="sewing_detail_img" accept="image/*"
                                            @change="handleImageUpload($event, 'sewing_detail_img_url')"
                                            :class="{ 'border-red-500': errors.sewing_detail_img }"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700">
                                        <p x-show="errors.sewing_detail_img" x-text="errors.sewing_detail_img"
                                            class="mt-1 text-xs text-red-500"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea name="sewing_notes" x-model="formData.sewing_notes" rows="2"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                            placeholder="Optional notes for sewing..."></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- 📦 PACKING SECTION --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                    <span class="text-lg">📦</span>
                                    Packing Section
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Plastic Packing <span class="text-red-600">*</span>
                                        </label>
                                        <select name="plastic_packing_id" x-model="formData.plastic_packing_id"
                                            :class="errors.plastic_packing_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Packing --</option>
                                            @foreach ($masterData['plasticPackings'] as $packing)
                                                <option value="{{ $packing->id }}">{{ $packing->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.plastic_packing_id" x-cloak x-text="errors.plastic_packing_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Sticker <span class="text-red-600">*</span>
                                        </label>
                                        <select name="sticker_id" x-model="formData.sticker_id"
                                            :class="errors.sticker_id ?
                                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                            <option value="">-- Select Sticker --</option>
                                            @foreach ($masterData['stickers'] as $sticker)
                                                <option value="{{ $sticker->id }}">{{ $sticker->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="errors.sticker_id" x-cloak x-text="errors.sticker_id"
                                            class="mt-1 text-sm text-red-600"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Hangtag Image
                                        </label>

                                        {{-- Show existing image if available --}}
                                        <div x-show="formData.hangtag_img_url" class="mb-2">
                                            <img :src="formData.hangtag_img_url" alt="Hangtag"
                                                class="w-32 h-32 object-cover rounded border border-gray-200">
                                            <p class="text-xs text-gray-500 mt-1">Current image (upload new to replace)</p>
                                        </div>

                                        <input type="file" name="hangtag_img" accept="image/*"
                                            @change="handleImageUpload($event, 'hangtag_img_url')"
                                            :class="{ 'border-red-500': errors.hangtag_img }"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700">
                                        <p x-show="errors.hangtag_img" x-text="errors.hangtag_img"
                                            class="mt-1 text-xs text-red-500"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea name="packing_notes" x-model="formData.packing_notes" rows="2"
                                            class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                            placeholder="Optional notes for packing..."></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Modal Footer --}}
                        <div class="flex justify-end gap-3 p-5 border-t border-gray-200">
                            <button type="button" @click="closeModal()" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                Cancel
                            </button>
                            <button type="button" @click="handleSubmit()" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed flex items-center gap-2">
                                {{-- Loading Spinner --}}
                                <svg x-show="isSubmitting" x-cloak class="animate-spin h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="isSubmitting ? 'Processing...' : 'Save Work Order'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Include Show Modal (Separated File) --}}
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
