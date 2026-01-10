@extends('layouts.app')

@section('title', str_replace(['INV-', 'INV'], '', $order->invoice->invoice_no) . ' ' . $order->customer->customer_name . ' Work Order')

@section('content')
    @php
        $role = auth()->user()?->role;
        if ($role === 'owner') {
            $root = 'Employee';
        } elseif ($role === 'admin') {
            $root = 'Admin';
        } elseif ($role === 'pm') {
            $root = 'PM';
        } else {
            $root = 'Menu';
        }
    @endphp

    <x-nav-locate :items="[$root, 'Task Employee', 'View Work Order']" />

    {{-- Alpine Root State --}}
    <div x-data="{
        showModal: false,
        showData: null,
        zoomLevel: 100,
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
        },
        locationData: {
            province_name: 'Loading...',
            city_name: 'Loading...',
            district_name: 'Loading...',
            village_name: 'Loading...'
        },
        async loadLocationData() {
            try {
                const response = await fetch('{{ route('customers.location', $order->customer_id) }}');
                const data = await response.json();
                this.locationData = data;
            } catch (error) {
                console.error('Error loading location:', error);
                this.locationData = {
                    province_name: '-',
                    city_name: '-',
                    district_name: '-',
                    village_name: '-'
                };
            }
        },
        openShowModal(design) {
            console.log('Opening show modal for design:', design);
            this.showData = design;
            this.showModal = true;
        },
        closeShowModal() {
            this.showModal = false;
            this.showData = null;
        }
    }" x-init="loadLocationData()" class="space-y-6">

        {{-- ================= SECTION 1: HEADER (Left Only) ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <div class="flex items-center gap-3 md:gap-4">
                {{-- Order Image Container - Hidden on mobile, shown on md+ --}}
                <div class="hidden md:flex flex-shrink-0">
                    @if($order->img_url)
                        <img src="{{ route('orders.serve-image', $order->id) }}" 
                             alt="Order Image" 
                             class="w-20 h-20 object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                    @else
                        <div class="w-20 h-20 rounded-lg border-2 border-gray-200 bg-gray-50 flex items-center justify-center">
                            <span class="text-xs text-gray-400 font-medium">No Image</span>
                        </div>
                    @endif
                </div>

                {{-- Invoice & Status --}}
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 md:gap-3">
                        <h1 class="text-md md:text-2xl font-bold text-gray-900">{{ $order->invoice->invoice_no }}</h1>
                        @php
                            $statusClasses = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'wip' => 'bg-blue-100 text-blue-800',
                                'finished' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                            ];
                            $statusClass = $statusClasses[$order->production_status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <div class="px-3 py-2 rounded-full text-xs md:text-sm md:px-4 font-bold {{ $statusClass }}">
                            {{ strtoupper(str_replace('_', ' ', $order->production_status)) }}
                        </div>
                    </div>

                    {{-- Order Date & Deadline - Moved under invoice --}}
                    <div class="flex items-center gap-4 text-xs md:text-sm text-gray-600">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 md:w-4 md:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 md:w-4 md:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium text-gray-900">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d M Y') : '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 3: DETAIL PRODUCTS ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detail Products</h2>

            {{-- Product Info --}}
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Product</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">
                        {{ $order->productCategory->product_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Material</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">
                        {{ $order->materialCategory->material_name ?? '-' }} -
                        {{ $order->materialTexture->texture_name ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Total Design</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">{{ $order->designVariants->count() ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Shipping</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">
                        {{ $order->shipping_type === 'pickup' ? 'Pickup' : 'Delivery' }}
                    </p>
                </div>
                @if ($order->notes)
                    <div class="col-span-2 md:col-span-1">
                        <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Notes</p>
                        <p class="text-sm md:text-base font-normal text-gray-700">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ================= SECTION 4: WORK ORDER DOCUMENTS ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Work Order Documents</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($order->designVariants as $index => $design)
                    @php
                        // Get order items for this design
                        $designOrderItems = $order->orderItems->where(
                            'design_variant_id',
                            $design->id,
                        );

                        // Get unique sleeve and size IDs from order items
                        $usedSleeveIds = $designOrderItems
                            ->pluck('sleeve.id')
                            ->unique()
                            ->filter()
                            ->values()
                            ->toArray();
                        $usedSizeIds = $designOrderItems
                            ->pluck('size.id')
                            ->unique()
                            ->filter()
                            ->values()
                            ->toArray();

                        // Query sleeves/sizes that are used, with proper ORDER BY sort_order
                        $usedSleeves = \App\Models\MaterialSleeve::whereIn('id', $usedSleeveIds)
                            ->orderBy('sort_order')
                            ->pluck('sleeve_name')
                            ->toArray();
                        
                        $usedSizes = \App\Models\MaterialSize::whereIn('id', $usedSizeIds)
                            ->orderBy('sort_order')
                            ->pluck('size_name')
                            ->toArray();

                        // Get all sleeve/size names for filling (ordered by sort_order)
                        $allSleeves = \App\Models\MaterialSleeve::orderBy('sort_order')->pluck('sleeve_name')->toArray();
                        $allSizes = \App\Models\MaterialSize::orderBy('sort_order')->pluck('size_name')->toArray();

                        // Fill sleeves to minimum 4 - combine used + remaining BUT maintain global sort_order
                        if (count($usedSleeves) < 4) {
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
                    @endphp
                    <div @if($design->workOrder && $design->workOrder->status === 'created')
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
                            })"
                        @endif
                        class="relative bg-white border rounded-2xl p-4 md:p-6 transition-all duration-200 {{ $design->workOrder && $design->workOrder->status === 'created' ? 'border-gray-200 cursor-pointer hover:border-primary hover:bg-primary/5 hover:shadow-md' : 'border-gray-300 bg-gray-50 cursor-not-allowed' }}">
                        
                        {{-- Icon Block for Not Created --}}
                        @if(!$design->workOrder || $design->workOrder->status !== 'created')
                            <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                                <div class="bg-gray-400/40 rounded-full p-4">
                                    <svg class="w-12 h-12 md:w-16 md:h-16 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center gap-3 md:gap-4 {{ !$design->workOrder || $design->workOrder->status !== 'created' ? 'opacity-40' : '' }}">
                            {{-- 1. Image (Kiri) - dari work_orders.mockup_img_url --}}
                            <div class="flex-shrink-0">
                                @if ($design->workOrder && $design->workOrder->mockup_img_url)
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
                                    @if ($design->design_name || $design->color)
                                        <span class="text-gray-600 italic">( 
                                            @if($design->design_name){{ $design->design_name }}@endif
                                            @if($design->design_name && $design->color) - @endif
                                            @if($design->color){{ $design->color }}@endif
                                        )</span>
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
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ========== SHOW MODAL ========== --}}
        @include('pages.admin.work-orders.partials.show-modal')
    </div>
@endsection
