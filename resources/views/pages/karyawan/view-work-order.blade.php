@extends('layouts.app')

@section('title', 'View Work Order')

@section('content')
    @php
        $role = auth()->user()?->role;
        if ($role === 'owner') {
            $root = 'Karyawan';
        } elseif ($role === 'admin') {
            $root = 'Admin';
        } elseif ($role === 'pm') {
            $root = 'PM';
        } else {
            $root = 'Menu';
        }
    @endphp

    <x-nav-locate :items="[$root, 'Task Karyawan', 'View Work Order']" />

    {{-- Alpine Root State --}}
    <div x-data="{
        showModal: false,
        showData: null,
        openShowModal(design) {
            console.log('Opening show modal for design:', design);
            this.showData = design;
            this.showModal = true;
        },
        closeShowModal() {
            this.showModal = false;
            this.showData = null;
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
                    <div class="px-3 py-2 rounded-full text-xs md:text-sm md:px-4 font-bold {{ $statusClass }}">
                        <span>{{ $allCompleted ? '✅ CREATED' : '⏳ ' . strtoupper(str_replace('_', ' ', $order->work_order_status)) }}</span>
                    </div>
                </div>
                {{-- Order Date & Deadline --}}
                <div class="flex items-center justify-between sm:justify-start sm:gap-6 text-sm text-gray-600 mt-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium">Order:</span>
                        <span>{{ $order->order_date->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 text-red-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Deadline:</span>
                        <span class="font-semibold text-red-600">{{ $order->deadline->format('d M Y') }}</span>
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
                                <img src="{{ route('admin.work-orders.mockup-image', ['workOrder' => $design->workOrder->id]) }}" 
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
                                @if ($design->design_name)
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
                                        {{-- Show Only --}}
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
                                                variant_index: {{ $index + 1 }},
                                                design_name: {{ Js::from($design->design_name) }},
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
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ========== SHOW MODAL ========== --}}
        @include('pages.admin.work-orders.partials.show-modal')

    </div>
@endsection
