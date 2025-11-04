@extends('layouts.app')

@section('title', 'Order Detail')

@push('styles')
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .print-area,
            .print-area * {
                visibility: visible;
            }

            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')

    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    {{-- Nav Locate & Back Button --}}
    <x-nav-locate :items="[$root, 'Orders', 'Order Detail']" />

    {{-- <a href="{{ route('admin.orders.index') }}"
        class="mb-4 no-print inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Orders
    </a> --}}

    <div class="space-y-6" x-data="{
        ...orderDetail(),
        showCancelConfirm: false,
        showMoveToShippingConfirm: false,
        locationData: {
            province_name: 'Loading...',
            city_name: 'Loading...',
            district_name: 'Loading...',
            village_name: 'Loading...'
        },
        async loadLocationData() {
            try {
                const response = await fetch('{{ route('admin.customers.location', $order->customer_id) }}');
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
        }
    }" x-init="loadLocationData()">
        {{-- ================= SECTION 1: HEADER ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <div class="flex justify-between gap-4">
                {{-- Invoice & Status --}}
                <div class="flex items-center gap-2 md:gap-4 justify-between">
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

                {{-- Action Buttons --}}
                <div class="flex items-center gap-3">
                    {{-- Show Invoice Button - Hidden on mobile, shown on md and up --}}
                    <button @click="openInvoiceModal = true"
                        class="hidden md:flex px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark items-center gap-2 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Show Invoice
                    </button>

                    {{-- Dropdown Menu --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="p-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        {{-- Dropdown Content --}}
                        <div x-show="open" @click.away="open = false" x-cloak
                            class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-20">
                            {{-- Show Invoice - Only visible on mobile (< md) --}}
                            <button @click="openInvoiceModal = true; open = false"
                                class="md:hidden w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-200">
                                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Show Invoice
                            </button>

                            @php
                                $isPending = $order->production_status === 'pending';
                                $isWip = $order->production_status === 'wip';
                                $isFinished = $order->production_status === 'finished';
                                $isCancelled = $order->production_status === 'cancelled';
                                $isShipped = $order->shipping_status === 'shipped';
                                $hasRemainingDue = ($order->invoice->amount_due ?? 0) > 0;
                            @endphp

                            {{-- 1. STATUS == PENDING --}}
                            @if ($isPending)
                                <a href="{{ route('admin.orders.edit', $order->id) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Order
                                </a>
                                <button @click="openPaymentModal = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Add Payment
                                </button>
                                <button @click="showCancelConfirm = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Cancel Order
                                </button>
                            @endif

                            {{-- 2. STATUS == WIP --}}
                            @if ($isWip)
                                <a href="{{ route('admin.orders.edit', $order->id) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Order
                                </a>
                                <button @click="openPaymentModal = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Add Payment
                                </button>
                                <button @click="showCancelConfirm = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Cancel Order
                                </button>
                            @endif

                            {{-- 3. STATUS == FINISHED, REMAINING > 0 --}}
                            @if ($isFinished && $hasRemainingDue && !$isShipped)
                                <button @click="openPaymentModal = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Add Payment
                                </button>
                                {{-- Move to Shipping - Locked --}}
                                <div
                                    class="w-full text-left px-4 py-2 text-sm text-gray-400 cursor-not-allowed bg-gray-50 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                        Move to Shipping
                                    </div>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <p class="px-4 py-2 text-xs text-orange-600 bg-orange-50">
                                    ⚠️ Complete payment first
                                </p>
                            @endif

                            {{-- 4. STATUS == FINISHED, REMAINING == 0, SHIPPED == PENDING --}}
                            @if ($isFinished && !$hasRemainingDue && !$isShipped)
                                <button @click="showMoveToShippingConfirm = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    Move to Shipping
                                </button>
                            @endif

                            {{-- 5. STATUS == FINISHED, REMAINING == 0, SHIPPED == SHIPPED --}}
                            @if ($isFinished && !$hasRemainingDue && $isShipped)
                                <div class="px-4 py-6 text-center text-gray-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs">Order Completed</p>
                                </div>
                            @endif
                        </div>
                    </div>
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

        {{-- ================= SECTION 2: CUSTOMER DATA ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Name</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">{{ $order->customer->customer_name }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Phone</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">{{ $order->customer->phone }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Sales</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">{{ $order->sale->sales_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Province</p>
                    <p class="text-sm md:text-base font-medium text-gray-900" x-text="locationData.province_name"></p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">City</p>
                    <p class="text-sm md:text-base font-medium text-gray-900" x-text="locationData.city_name"></p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">District</p>
                    <p class="text-sm md:text-base font-medium text-gray-900" x-text="locationData.district_name"></p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Village</p>
                    <p class="text-sm md:text-base font-medium text-gray-900" x-text="locationData.village_name"></p>
                </div>
                <div class="col-span-2 md:col-span-2">
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Address</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">{{ $order->customer->address }}</p>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 3: PRODUCT DETAILS ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detail Products</h2>

            {{-- Product Info --}}
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Product</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">
                        {{ $order->productCategory->product_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Material</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">
                        {{ $order->materialCategory->material_name ?? '-' }} -
                        {{ $order->materialTexture->texture_name ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Color</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">{{ $order->product_color ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm text-gray-500 mb-1">Shipping</p>
                    <p class="text-sm md:text-base font-medium text-gray-900">
                        {{ $order->shipping_type === 'pickup' ? 'Pickup' : 'Delivery' }}
                    </p>
                </div>
                @if ($order->notes)
                    <div class="col-span-2 md:col-span-1">
                        <p class="text-xs md:text-sm text-gray-500 mb-1">Notes</p>
                        <p class="text-sm md:text-base font-medium text-gray-900">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ================= SECTION 4: ORDER ITEMS ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detail Order Items</h2>

            {{-- Design Variants --}}
            @foreach ($designVariants as $designName => $variants)
                <div class="border border-gray-300 rounded-lg p-4 mb-4">
                    {{-- Label Design Variant - Row Layout --}}
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-xs text-gray-500 uppercase tracking-wide">Design Variant:</span>
                        <h3 class="text-md font-semibold text-gray-900">{{ $designName }}</h3>
                    </div>

                    @foreach ($variants as $sleeveData)
                        <div class="mb-6 last:mb-0">
                            {{-- Label Sleeve Type - Row Layout --}}
                            <div class="flex items-center gap-4 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">Sleeve Type:</span>
                                    <span
                                        class="px-3 py-1 bg-primary/10 text-primary rounded-md text-sm font-medium">{{ $sleeveData['sleeve_name'] }}</span>
                                </div>
                                <span class="text-sm text-gray-600">Base Price: Rp
                                    {{ number_format($sleeveData['base_price'], 0, ',', '.') }}</span>
                            </div>

                            {{-- Table --}}
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-primary-light text-gray-600">
                                        <tr>
                                            <th class="py-2 px-4 text-left rounded-l-lg">No</th>
                                            <th class="py-2 px-4 text-left">Size</th>
                                            <th class="py-2 px-4 text-left">Unit Price</th>
                                            <th class="py-2 px-4 text-left">QTY</th>
                                            <th class="py-2 px-4 text-left rounded-r-lg">Total Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sleeveData['items'] as $index => $item)
                                            <tr class="border-t border-gray-200">
                                                <td class="py-2 px-4">{{ $index + 1 }}</td>
                                                <td class="py-2 px-4">
                                                    {{ $item->size->size_name ?? 'N/A' }}
                                                    @if (($item->size->extra_price ?? 0) > 0)
                                                        <span class="text-xs text-gray-500">
                                                            (+Rp
                                                            {{ number_format($item->size->extra_price, 0, ',', '.') }})
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-4">Rp
                                                    {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                                <td class="py-2 px-4">{{ $item->qty }}</td>
                                                <td class="py-2 px-4 font-medium">Rp
                                                    {{ number_format($item->unit_price * $item->qty, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            {{-- Additional Services --}}
            <div class="mt-6 border-t pt-6">
                <h3 class="text-md font-semibold text-gray-900 mb-3">Additional Services</h3>
                @if ($order->extraServices->count() > 0)
                    <div class="space-y-2">
                        @foreach ($order->extraServices as $extra)
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm text-gray-700">{{ $extra->service->service_name ?? 'N/A' }}</span>
                                <span class="text-sm font-medium text-gray-900">Rp
                                    {{ number_format($extra->price, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 bg-gray-50 rounded-lg text-start">
                        <span class="text-sm text-gray-500">-</span>
                    </div>
                @endif
            </div>

            {{-- Order Summary --}}
            <div class="mt-6 border-t pt-6">
                <div class="flex justify-end">
                    <div class="w-full md:w-1/2 lg:w-1/3 space-y-3">
                        @php
                            // Calculate subtotal from order items
                            $subtotalItems = $order->orderItems->sum(function ($item) {
                                return $item->unit_price * $item->qty;
                            });
                            // Calculate extra services total
                            $subtotalServices = $order->extraServices->sum('price');
                        @endphp

                        {{-- Subtotal Items --}}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal Items</span>
                            <span class="font-medium text-gray-900">Rp
                                {{ number_format($subtotalItems, 0, ',', '.') }}</span>
                        </div>

                        {{-- Subtotal Services --}}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal Services</span>
                            <span class="font-medium text-gray-900">Rp
                                {{ number_format($subtotalServices, 0, ',', '.') }}</span>
                        </div>

                        {{-- Discount --}}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Discount</span>
                            <span class="font-medium text-red-600">
                                @if (($order->discount ?? 0) > 0)
                                    - Rp {{ number_format($order->discount, 0, ',', '.') }}
                                @else
                                    Rp 0
                                @endif
                            </span>
                        </div>

                        {{-- Total Bill --}}
                        <div class="flex justify-between text-base border-t pt-3">
                            <span class="font-semibold text-gray-900">Total Bill</span>
                            <span class="font-bold text-gray-900">Rp
                                {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 4: PAYMENT DETAILS ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h2>

            {{-- Payment Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- Total Bill --}}
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-600 font-medium mb-1">Total Bill</p>
                    <p class="text-2xl font-bold text-blue-900">Rp
                        {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</p>
                </div>

                {{-- Amount Paid --}}
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
                    <p class="text-sm text-green-600 font-medium mb-1">Amount Paid</p>
                    <p class="text-2xl font-bold text-green-900">Rp
                        {{ number_format($order->invoice->amount_paid ?? 0, 0, ',', '.') }}</p>
                </div>

                {{-- Remaining Due --}}
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-4">
                    <p class="text-sm text-orange-600 font-medium mb-1">Remaining Due</p>
                    <p class="text-2xl font-bold text-orange-900">Rp
                        {{ number_format($order->invoice->amount_due ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Payment Table --}}
            @if ($order->invoice && $order->invoice->payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="py-3 px-4 text-left rounded-l-lg">No</th>
                                <th class="py-3 px-4 text-left">Payment Type</th>
                                <th class="py-3 px-4 text-left">Payment Method</th>
                                <th class="py-3 px-4 text-left">Amount</th>
                                <th class="py-3 px-4 text-left">Status</th>
                                <th class="py-3 px-4 text-left">Notes</th>
                                <th class="py-3 px-4 text-left rounded-r-lg">Attachment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->invoice->payments as $index => $payment)
                                @php
                                    $paymentTypeClasses = [
                                        'dp' => 'bg-blue-100 text-blue-800',
                                        'repayment' => 'bg-purple-100 text-purple-800',
                                        'full_payment' => 'bg-green-100 text-green-800',
                                    ];
                                    $paymentClass =
                                        $paymentTypeClasses[$payment->payment_type] ?? 'bg-gray-100 text-gray-800';

                                    $statusClasses = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusClass = $statusClasses[$payment->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <tr class="border-t border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ $index + 1 }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $paymentClass }}">
                                            {{ strtoupper(str_replace('_', ' ', $payment->payment_type)) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 capitalize">{{ $payment->payment_method }}</td>
                                    <td class="py-3 px-4 font-medium">Rp
                                        {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $statusClass }}">
                                            {{ strtoupper($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">{{ $payment->notes ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($payment->img_url)
                                            <button @click="showImage('{{ $payment->img_url }}')"
                                                class="text-primary hover:text-primary-dark font-medium text-xs flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                View Image
                                            </button>
                                        @else
                                            <span class="text-gray-400 text-xs">No attachment</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm">No payments recorded yet</p>
                </div>
            @endif
        </div>

        {{-- ================= SECTION 5: WORK ORDER ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Work Order Documents</h2>
            <div class="text-center py-12 text-gray-400">
                <svg class="w-20 h-20 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-medium">Work Order Documents</p>
                <p class="text-xs mt-1">PDF documents will be available here</p>
            </div>
        </div>

        {{-- ================= IMAGE VIEWER MODAL ================= --}}
        <div x-show="showImageModal" x-cloak @click="showImageModal = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div @click.stop class="relative max-w-4xl w-full">
                <button @click="showImageModal = false" class="absolute -top-10 right-0 text-white hover:text-gray-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img :src="currentImage" class="w-full h-auto rounded-lg shadow-2xl" alt="Payment proof">
            </div>
        </div>

        {{-- ================= INVOICE MODAL ================= --}}
        <div x-show="openInvoiceModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm px-4 overflow-y-auto py-8">
            <div @click.away="openInvoiceModal = false"
                class="bg-white rounded-xl shadow-2xl w-full max-w-4xl my-8 max-h-[90vh] flex flex-col">
                {{-- Header --}}
                <div
                    class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-xl flex items-center justify-between z-10">
                    <h3 class="text-lg font-semibold text-gray-900">Invoice Preview</h3>
                    <div class="flex items-center gap-3">
                        {{-- Download PDF Button --}}
                        <a href="{{ route('admin.orders.invoice.download', $order->id) }}" target="_blank"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark flex items-center gap-2 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download PDF
                        </a>
                        {{-- Close Button --}}
                        <button @click="openInvoiceModal = false" type="button"
                            class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Invoice Content --}}
                <div class="flex-1 overflow-y-auto p-8 bg-gray-50">
                    @include('pages.admin.orders.partials.invoice-content')
                </div>
            </div>
        </div>

        {{-- ================= ADD PAYMENT MODAL (Reuse from index) ================= --}}
        <div x-show="openPaymentModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4 overflow-y-auto py-8">
            <div @click.away="openPaymentModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-2xl my-8"
                x-data="{
                    payment_method: '',
                    payment_type: '',
                    amount: '',
                    amountDisplay: '',
                    notes: '',
                    image: null,
                    imagePreview: null,
                    uploading: false,
                    errors: {},
                
                    formatRupiah(value) {
                        let number = value.replace(/[^0-9]/g, '');
                        if (number) {
                            return Number(number).toLocaleString('id-ID');
                        }
                        return '';
                    },
                
                    handleAmountInput(event) {
                        const value = event.target.value;
                        this.amount = value.replace(/[^0-9]/g, '');
                        this.amountDisplay = this.formatRupiah(value);
                        event.target.value = this.amountDisplay;
                    },
                
                    addImage(event) {
                        const file = event.target.files[0];
                        if (file && file.type.startsWith('image/')) {
                            this.image = file;
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.imagePreview = e.target.result;
                            };
                            reader.readAsDataURL(file);
                            this.errors.image = null;
                        }
                        event.target.value = '';
                    },
                
                    removeImage() {
                        this.image = null;
                        this.imagePreview = null;
                    },
                
                    validateForm() {
                        this.errors = {};
                        let isValid = true;
                
                        if (!this.payment_method) {
                            this.errors.payment_method = 'Payment method is required';
                            isValid = false;
                        }
                
                        if (!this.payment_type) {
                            this.errors.payment_type = 'Payment type is required';
                            isValid = false;
                        }
                
                        if (!this.amount || this.amount <= 0) {
                            this.errors.amount = 'Please enter a valid amount (greater than 0)';
                            isValid = false;
                        }
                
                        const amountDue = {{ $order->invoice->amount_due }};
                        if (this.amount > amountDue) {
                            this.errors.amount = `Payment amount cannot exceed remaining due (Rp ${amountDue.toLocaleString('id-ID')})`;
                            isValid = false;
                        }
                
                        if (!this.image) {
                            this.errors.image = 'Payment proof image is required';
                            isValid = false;
                        }
                
                        return isValid;
                    },
                
                    async submitPayment() {
                        if (!this.validateForm()) {
                            return;
                        }
                
                        this.uploading = true;
                        const formData = new FormData();
                        formData.append('invoice_id', {{ $order->invoice->id }});
                        formData.append('payment_method', this.payment_method);
                        formData.append('payment_type', this.payment_type);
                        formData.append('amount', this.amount);
                
                        if (this.notes && this.notes.trim() !== '') {
                            formData.append('notes', this.notes.trim());
                        }
                
                        if (this.image) {
                            formData.append('image', this.image);
                        }
                
                        try {
                            const response = await fetch('{{ route('admin.payments.store') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: formData
                            });
                
                            const data = await response.json();
                
                            if (response.ok && data.success) {
                                openPaymentModal = false;
                
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: {
                                        message: '✅ Payment added successfully! Amount: Rp ' + Number(this.amount).toLocaleString('id-ID'),
                                        type: 'success'
                                    }
                                }));
                
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                if (data.errors) {
                                    this.errors = data.errors;
                                } else {
                                    window.dispatchEvent(new CustomEvent('show-toast', {
                                        detail: {
                                            message: '❌ Error: ' + (data.message || 'Failed to add payment'),
                                            type: 'error'
                                        }
                                    }));
                                }
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: {
                                    message: '❌ Network Error: ' + error.message,
                                    type: 'error'
                                }
                            }));
                        } finally {
                            this.uploading = false;
                        }
                    }
                }">

                {{-- Header --}}
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Add Payment</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Invoice: <span class="font-medium">{{ $order->invoice->invoice_no }}</span> |
                                Amount Due: <span class="font-medium text-red-600">Rp
                                    {{ number_format($order->invoice->amount_due, 0, ',', '.') }}</span>
                            </p>
                        </div>
                        <button @click="openPaymentModal = false" type="button"
                            class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-5 max-h-[calc(100vh-200px)] overflow-y-auto">
                    {{-- Payment Method --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Payment Method <span
                                class="text-red-500">*</span></label>
                        <select x-model="payment_method"
                            :class="errors.payment_method ? 'border-red-500' : 'border-gray-300'"
                            class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select payment method</option>
                            <option value="tranfer">Transfer</option>
                            <option value="cash">Cash</option>
                        </select>
                        <p x-show="errors.payment_method" x-text="errors.payment_method"
                            class="text-xs text-red-600 mt-1" x-cloak></p>
                    </div>

                    {{-- Payment Type --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Payment Type <span
                                class="text-red-500">*</span></label>
                        <select x-model="payment_type" :class="errors.payment_type ? 'border-red-500' : 'border-gray-300'"
                            class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select payment type</option>
                            <option value="dp">Down Payment (DP)</option>
                            <option value="repayment">Repayment</option>
                            <option value="full_payment">Full Payment</option>
                        </select>
                        <p x-show="errors.payment_type" x-text="errors.payment_type" class="text-xs text-red-600 mt-1"
                            x-cloak></p>
                    </div>

                    {{-- Amount --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Amount <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                                Rp
                            </span>
                            <input type="text" @input="handleAmountInput($event)" :value="amountDisplay"
                                placeholder="0" :class="errors.amount ? 'border-red-500' : 'border-gray-300'"
                                class="w-full rounded-md border pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                        <p x-show="errors.amount" x-text="errors.amount" class="text-xs text-red-600 mt-1" x-cloak></p>
                    </div>

                    {{-- Notes --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                        <textarea x-model="notes" rows="3" placeholder="Additional notes..."
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>

                    {{-- Upload Image --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Payment Proof <span class="text-red-500">*
                                (1 image required)</span></label>
                        <div :class="errors.image ? 'border-red-500' : 'border-gray-300'"
                            class="border-2 border-dashed rounded-lg p-4 text-center hover:border-primary transition-colors">
                            <input type="file" @change="addImage($event)" accept="image/*" class="hidden"
                                id="payment-image-detail">
                            <label for="payment-image-detail" class="cursor-pointer">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm text-gray-600">Click to upload image</p>
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 10MB</p>
                            </label>
                        </div>
                        <p x-show="errors.image" x-text="errors.image" class="text-xs text-red-600 mt-1" x-cloak></p>

                        {{-- Image Preview --}}
                        <div x-show="imagePreview" class="mt-3">
                            <div class="relative inline-block">
                                <img :src="imagePreview"
                                    class="w-full max-w-xs h-48 object-cover rounded-lg border-2 border-gray-200">
                                <button type="button" @click="removeImage()"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div
                    class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end gap-3 rounded-b-xl">
                    <button @click="openPaymentModal = false" type="button"
                        class="px-4 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="submitPayment()" type="button" :disabled="uploading"
                        :class="uploading ? 'opacity-50 cursor-not-allowed' : ''"
                        class="px-6 py-2 rounded-md bg-primary text-white hover:bg-primary-dark flex items-center gap-2">
                        <svg x-show="uploading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span x-text="uploading ? 'Uploading...' : 'Add Payment'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ================= CANCEL CONFIRMATION MODAL ================= --}}
        <div x-show="showCancelConfirm" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showCancelConfirm = false"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                {{-- Icon --}}
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                    Cancel Order?
                </h3>

                {{-- Message --}}
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to cancel this order? This action cannot be undone and the order status will
                    be changed to <span class="font-semibold text-red-600">Cancelled</span>.
                </p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="showCancelConfirm = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        No, Keep Order
                    </button>
                    <form action="{{ route('admin.orders.cancel', $order->id) }}" method="POST" class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                            Yes, Cancel Order
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ================= MOVE TO SHIPPING CONFIRMATION MODAL ================= --}}
        <div x-show="showMoveToShippingConfirm" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showMoveToShippingConfirm = false"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                {{-- Icon --}}
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                    Move Order to Shippings?
                </h3>

                {{-- Message --}}
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to move this finished order to the shipping page? The shipping status will be
                    changed to <span class="font-semibold text-green-600">Shipped</span> and the order will be available on
                    the Shippings page.
                </p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="showMoveToShippingConfirm = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form action="{{ route('admin.orders.move-to-shipping', $order->id) }}" method="POST"
                        class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 transition-colors">
                            Yes, Move to Shippings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function orderDetail() {
            return {
                openPaymentModal: false,
                openInvoiceModal: false,
                showImageModal: false,
                currentImage: '',

                showImage(url) {
                    this.currentImage = url;
                    this.showImageModal = true;
                },

                printInvoice() {
                    window.print();
                }
            }
        }
    </script>
@endpush
