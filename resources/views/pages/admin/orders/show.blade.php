@extends('layouts.app')

@section('title', 'Order Detail')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet" />
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
        invoiceZoomLevel: 100,
        invoiceZoomIn() {
            if (this.invoiceZoomLevel < 200) {
                this.invoiceZoomLevel += 10;
            }
        },
        invoiceZoomOut() {
            if (this.invoiceZoomLevel > 50) {
                this.invoiceZoomLevel -= 10;
            }
        },
        invoiceResetZoom() {
            this.invoiceZoomLevel = 100;
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
    }" x-init="loadLocationData()">
        {{-- ================= SECTION 1: HEADER ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <div class="flex justify-between gap-4">
                {{-- Left Section: Order Image + Invoice & Status --}}
                <div class="flex items-center gap-3 md:gap-4">
                    {{-- Order Image Container - Hidden on mobile, shown on md+ --}}
                    <div class="hidden md:flex flex-shrink-0">
                        @if($order->img_url)
                            <img src="{{ route('orders.serve-image', $order->id) }}" 
                                 alt="Order Image" 
                                 class="w-20 h-20 object-cover rounded-lg border-2 border-gray-200 shadow-sm cursor-pointer hover:border-primary transition-colors"
                                 @click="showImage('{{ route('orders.serve-image', $order->id) }}')"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%239ca3af%22 font-family=%22Arial%22 font-size=%2214%22 text-anchor=%22middle%22 x=%2250%22 y=%2250%22 dy=%220.3em%22%3ENo Image%3C/text%3E%3C/svg%3E';">
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
                    <div x-data="{ open: false }" class="relative">
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

                            {{-- Edit (Hidden for finished/cancelled) --}}
                            @if ($order->production_status !== 'finished' && $order->production_status !== 'cancelled')
                                <a href="{{ route('admin.orders.edit', $order->id) }}"
                                    class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </a>
                            @endif

                            {{-- Add Payment (Hidden if no invoice, fully paid, or cancelled) --}}
                            @if ($order->invoice && $order->invoice->amount_due > 0 && $order->production_status !== 'cancelled')
                                <button type="button" @click="openPaymentModal = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Add Payment
                                </button>
                            @endif

                            {{-- Move to Shippings (Only for finished orders that haven't been shipped) --}}
                            @if ($order->production_status === 'finished' && $order->shipping_status === 'pending')
                                @php
                                    $hasRemainingDue = $order->invoice && $order->invoice->amount_due > 0;
                                @endphp
                                <button type="button"
                                    @if (!$hasRemainingDue) @click="showMoveToShippingConfirm = true; open = false"
                                    @else
                                        disabled title="Complete payment first (Remaining: Rp {{ number_format($order->invoice->amount_due, 0, ',', '.') }})" @endif
                                    class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 {{ $hasRemainingDue ? 'text-gray-400 cursor-not-allowed' : 'text-green-600 hover:bg-green-50' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    Move to Shippings
                                    @if ($hasRemainingDue)
                                        <svg class="w-4 h-4 ml-auto" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    @endif
                                </button>
                            @endif

                            {{-- Cancel (Hidden for cancelled and finished) --}}
                            @if ($order->production_status !== 'cancelled' && $order->production_status !== 'finished')
                                <button type="button" @click="showCancelConfirm = true; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Cancel Order
                                </button>
                            @endif

                            {{-- No actions for completed orders --}}
                            @if ($order->production_status === 'finished' && $order->shipping_status === 'shipped')
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
        </div>

        {{-- ================= SECTION 2: CUSTOMER DATA ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Name</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">{{ $order->customer->customer_name }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Phone</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">
                        @php
                            $phone = $order->customer->phone;
                            $maskedPhone = strlen($phone) > 8 
                                ? substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 8) . substr($phone, -4)
                                : $phone;
                        @endphp
                        {{ $maskedPhone }}
                    </p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Sales</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">{{ $order->sale->sales_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Province</p>
                    <p class="text-sm md:text-base font-normal text-gray-700" x-text="locationData.province_name"></p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">City</p>
                    <p class="text-sm md:text-base font-normal text-gray-700" x-text="locationData.city_name"></p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">District</p>
                    <p class="text-sm md:text-base font-normal text-gray-700" x-text="locationData.district_name"></p>
                </div>
                <div>
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Village</p>
                    <p class="text-sm md:text-base font-normal text-gray-700" x-text="locationData.village_name"></p>
                </div>
                <div class="col-span-2 md:col-span-2">
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Address</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">{{ $order->customer->address }}</p>
                </div>
            </div>
        </div>

        {{-- ================= SECTION 3: PRODUCT DETAILS ================= --}}
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
                    <p class="text-xs md:text-sm font-semibold text-gray-900 mb-1">Color</p>
                    <p class="text-sm md:text-base font-normal text-gray-700">{{ $order->product_color ?? '-' }}</p>
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

        {{-- ================= SECTION 4: ORDER ITEMS ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detail Order Items</h2>

            {{-- Design Variants --}}
            @php
                $grandTotalQty = 0;
                $grandTotalPrice = 0;
            @endphp

            @foreach ($designVariants as $designName => $variants)
                @php
                    $designTotalQty = 0;
                    $designTotalPrice = 0;
                @endphp

                <div class="border border-gray-300 rounded-lg p-4 mb-4">
                    {{-- Label Design Variant - Row Layout --}}
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-xs font-semibold text-gray-900 uppercase tracking-wide">Design Variant:</span>
                        <h3 class="text-md font-normal text-gray-700">{{ $designName }}</h3>
                    </div>

                    @foreach ($variants as $sleeveData)
                        @php
                            $sleeveTotalQty = 0;
                            $sleeveTotalPrice = 0;
                            foreach ($sleeveData['items'] as $item) {
                                $sleeveTotalQty += $item->qty;
                                $sleeveTotalPrice += $item->unit_price * $item->qty;
                            }
                            $designTotalQty += $sleeveTotalQty;
                            $designTotalPrice += $sleeveTotalPrice;
                        @endphp

                        <div class="mb-6 last:mb-0">
                            {{-- Label Sleeve Type - Row Layout --}}
                            <div class="flex items-center gap-4 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-gray-900 uppercase tracking-wide">Sleeve Type:</span>
                                    <span
                                        class="px-3 py-1 bg-primary/10 text-primary rounded-md text-sm font-medium">{{ $sleeveData['sleeve_name'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900">Base Price:</span>
                                    <span class="text-sm font-normal text-gray-700">Rp {{ number_format($sleeveData['base_price'], 0, ',', '.') }}</span>
                                </div>
                            </div>

                            {{-- Table with responsive scrolling --}}
                            <div class="overflow-x-auto -mx-4 md:mx-0">
                                <div class="inline-block min-w-full align-middle">
                                    <div class="overflow-hidden">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-primary-light text-gray-600">
                                                <tr>
                                                    <th class="py-3 px-4 text-left rounded-l-lg whitespace-nowrap min-w-[60px]">No</th>
                                                    <th class="py-3 px-4 text-left whitespace-nowrap min-w-[140px]">Size</th>
                                                    <th class="py-3 px-4 text-right whitespace-nowrap min-w-[150px]">Unit Price</th>
                                                    <th class="py-3 px-4 text-center whitespace-nowrap min-w-[80px]">QTY</th>
                                                    <th class="py-3 px-4 text-right rounded-r-lg whitespace-nowrap min-w-[170px]">Total Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($sleeveData['items'] as $index => $item)
                                                    <tr class="border-t border-gray-200 hover:bg-gray-50">
                                                        <td class="py-3 px-4 whitespace-nowrap">{{ $index + 1 }}</td>
                                                        <td class="py-3 px-4 whitespace-nowrap">
                                                            <span class="font-medium">{{ $item->size->size_name ?? 'N/A' }}</span>
                                                            @if (($item->size->extra_price ?? 0) > 0)
                                                                <span class="text-xs text-gray-500 ml-1.5">
                                                                    +Rp {{ number_format($item->size->extra_price, 0, ',', '.') }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="py-3 px-4 whitespace-nowrap text-right">
                                                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                                        </td>
                                                        <td class="py-3 px-4 whitespace-nowrap text-center">
                                                            {{ $item->qty }}
                                                        </td>
                                                        <td class="py-3 px-4 font-semibold whitespace-nowrap text-right">
                                                            Rp {{ number_format($item->unit_price * $item->qty, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                {{-- Row TOTAL per Sleeve --}}
                                                @if (count($sleeveData['items']) > 0)
                                                    <tr class="bg-primary-light/50">
                                                        <td colspan="3" class="py-3 px-4 text-center font-bold text-gray-800">
                                                            TOTAL
                                                        </td>
                                                        <td class="py-3 px-4 font-bold text-gray-900 text-center">
                                                            {{ $sleeveTotalQty }}
                                                        </td>
                                                        <td class="py-3 px-4 font-bold text-gray-900 text-right">
                                                            Rp {{ number_format($sleeveTotalPrice, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- TOTAL per Design (Gabungan semua sleeve) --}}
                    @if (count($variants) > 0)
                        <div class="mt-4 overflow-x-auto -mx-4 md:mx-0">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden">
                                    <table class="min-w-full text-sm">
                                        <thead class="invisible">
                                            <tr>
                                                <th class="py-0 px-4 whitespace-nowrap min-w-[60px]"></th>
                                                <th class="py-0 px-4 whitespace-nowrap min-w-[140px]"></th>
                                                <th class="py-0 px-4 whitespace-nowrap min-w-[150px]"></th>
                                                <th class="py-0 px-4 whitespace-nowrap min-w-[80px]"></th>
                                                <th class="py-0 px-4 whitespace-nowrap min-w-[170px]"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="bg-gradient-to-br from-red-100 to-red-50">
                                                <td colspan="3" class="py-3 px-4 text-center font-bold text-red-600">
                                                    TOTAL ({{ $designName }})
                                                </td>
                                                <td class="py-3 px-4 font-bold text-red-600 text-center whitespace-nowrap">
                                                    {{ $designTotalQty }}
                                                </td>
                                                <td class="py-3 px-4 font-bold text-red-600 text-right whitespace-nowrap">
                                                    Rp {{ number_format($designTotalPrice, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @php
                    $grandTotalQty += $designTotalQty;
                    $grandTotalPrice += $designTotalPrice;
                @endphp
            @endforeach

            {{-- GRAND TOTAL (Seluruh Design) --}}
            @if (count($designVariants) > 0)
                <div class="mt-4 overflow-x-auto -mx-4 md:mx-0 mb-6">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead class="invisible">
                                    <tr>
                                        <th class="py-0 px-4 whitespace-nowrap min-w-[60px]"></th>
                                        <th class="py-0 px-4 whitespace-nowrap min-w-[140px]"></th>
                                        <th class="py-0 px-4 whitespace-nowrap min-w-[150px]"></th>
                                        <th class="py-0 px-4 whitespace-nowrap min-w-[80px]"></th>
                                        <th class="py-0 px-4 whitespace-nowrap min-w-[170px]"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-primary-light">
                                        <td colspan="3" class="py-4 px-4 text-center font-bold text-gray-600">
                                            GRAND TOTAL
                                        </td>
                                        <td class="py-4 px-4 font-bold text-gray-600 text-center text-lg whitespace-nowrap">
                                            {{ $grandTotalQty }}
                                        </td>
                                        <td class="py-4 px-4 font-bold text-gray-600 text-right text-lg whitespace-nowrap">
                                            Rp {{ number_format($grandTotalPrice, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

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
                <div class="bg-gradient-to-br from-blue-100 to-blue-50 border border-blue-300 rounded-lg p-4 shadow-sm">
                    <p class="text-sm font-semibold text-blue-700 mb-1">Total Bill</p>
                    <p class="text-2xl font-bold text-blue-600">Rp
                        {{ number_format($order->invoice->total_bill ?? 0, 0, ',', '.') }}</p>
                </div>

                {{-- Amount Paid --}}
                <div class="bg-gradient-to-br from-green-100 to-green-50 border border-green-300 rounded-lg p-4 shadow-sm">
                    <p class="text-sm font-semibold text-green-700 mb-1">Amount Paid</p>
                    <p class="text-2xl font-bold text-green-600">Rp
                        {{ number_format($order->invoice->amount_paid ?? 0, 0, ',', '.') }}</p>
                </div>

                {{-- Remaining Due --}}
                @php
                    $remainingDueAmount = $order->invoice->amount_due ?? 0;
                @endphp
                
                @if($remainingDueAmount == 0)
                    {{-- LUNAS - Same style as Total Price --}}
                    <div class="bg-gradient-to-br from-gray-400 to-gray-300 rounded-lg p-4 shadow-sm items-center justify-center flex">
                        <p class="text-3xl font-extrabold text-white">PAID OFF</p>
                    </div>
                @else
                    {{-- Still have remaining due --}}
                    <div class="bg-gradient-to-br from-red-100 to-red-50 border border-red-300 rounded-lg p-4 shadow-sm">
                        <p class="text-sm font-semibold text-red-700 mb-1">Remaining Due</p>
                        <p class="text-2xl font-bold text-red-600">Rp
                            {{ number_format($remainingDueAmount, 0, ',', '.') }}</p>
                    </div>
                @endif
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
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 rounded text-xs font-medium {{ $statusClass }}">
                                                {{ strtoupper($payment->status) }}
                                            </span>
                                            @if ($payment->amount <= 10)
                                                <span class="text-xs font-medium text-red-600 italic">(Fiktif)</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">{{ $payment->notes ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($payment->img_url)
                                            <button @click="showImage('{{ route('payments.serve-image', $payment->id) }}')"
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

        {{-- ================= SECTION 5: WORK ORDER DOCUMENTS ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Work Order Documents</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($order->designVariants as $index => $design)
                    <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
                        <div class="flex items-center gap-3 md:gap-4">
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
        </div>

        {{-- ========== SHOW MODAL ========== --}}
        @include('pages.admin.work-orders.partials.show-modal')

        {{-- ================= IMAGE VIEWER MODAL ================= --}}
        <div x-show="showImageModal" x-cloak
            class="fixed inset-0 z-50">
            
            {{-- Background Overlay --}}
            <div x-show="showImageModal" @click="showImageModal = false; currentImage = ''" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.stop class="relative max-w-3xl w-full flex justify-center items-center" style="max-height: calc(100vh - 6rem);">
                    <button @click="showImageModal = false; currentImage = ''" class="absolute -top-10 right-0 text-white hover:text-gray-300 z-10">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img :src="currentImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain" style="max-height: calc(100vh - 10rem);" alt="Payment proof">
                </div>
            </div>
        </div>

        {{-- ================= INVOICE MODAL ================= --}}
        <div x-show="openInvoiceModal" x-cloak
            class="fixed inset-0 z-50 "
            @keydown.escape.window="openInvoiceModal && (openInvoiceModal = false)"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">

            {{-- Background Overlay --}}
            <div x-show="openInvoiceModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="openInvoiceModal = false">
            </div>

            {{-- Modal Panel --}}
            <div class="flex items-center justify-center h-screen py-8">
                <div x-show="openInvoiceModal"
                    class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl transform transition-all flex flex-col"
                    style="max-height: 95vh;" @click.away="openInvoiceModal = false">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-md md:text-lg font-semibold text-gray-900">
                                    Invoice Preview
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $order->invoice->invoice_no }} - {{ $order->customer->customer_name }}
                                </p>
                            </div>
                            
                            {{-- Zoom Controls + Close Button --}}
                            <div class="flex items-center gap-3">
                                {{-- Zoom Controls --}}
                                <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-2 py-1.5 bg-white">
                                    {{-- Zoom Out --}}
                                    <button @click="invoiceZoomOut()" type="button"
                                        :disabled="invoiceZoomLevel <= 50"
                                        :class="invoiceZoomLevel <= 50 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100'"
                                        class="p-1 rounded transition-colors">
                                        <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                        </svg>
                                    </button>

                                    {{-- Zoom Percentage - Hidden on mobile --}}
                                    <span class="hidden md:inline text-xs font-medium text-gray-700 min-w-[45px] text-center" x-text="invoiceZoomLevel + '%'"></span>

                                    {{-- Zoom In --}}
                                    <button @click="invoiceZoomIn()" type="button"
                                        :disabled="invoiceZoomLevel >= 200"
                                        :class="invoiceZoomLevel >= 200 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100'"
                                        class="p-1 rounded transition-colors">
                                        <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>

                                    {{-- Reset Zoom --}}
                                    <button @click="invoiceResetZoom()" type="button"
                                        class="p-1 rounded hover:bg-gray-100 transition-colors ml-1 border-l border-gray-300 pl-2">
                                        <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Close Button --}}
                                <button @click="openInvoiceModal = false" type="button"
                                    class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Content - Invoice Preview --}}
                    <div class="bg-gray-100 flex-1 overflow-auto">
                        <div class="p-4 inline-block min-w-full" :style="`transform: scale(${invoiceZoomLevel / 100}); transform-origin: top center; transition: transform 0.2s ease;`">
                        
                        {{-- Invoice Paper --}}
                        <div class="bg-white border-2 mx-auto shadow-lg" style="width: 210mm; min-height: 297mm; font-family: 'Lora', serif;">
                            
                            <div style="padding: 10mm;">
                                
                                {{-- Header Section --}}
                                <div class="border-b-2 border-black pb-4 mb-6">
                                    <div class="flex justify-between items-center mb-2">
                                        {{-- Logo --}}
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('images/logo-invoice.png') }}" alt="STGR Logo" class="h-24 w-auto object-contain">
                                        </div>
                                        
                                        {{-- Invoice Info --}}
                                        <div class="">
                                            <p class="text-4xl font-bold text-black mb-2 text-right">INVOICE</p>
                                            <table class="text-[11px]">
                                                <tr>
                                                    <td class="text-black font-semibold py-0.5">No</td>
                                                    <td class="text-black font-semibold px-2">:</td>
                                                    <td class="text-black text-right">{{ $order->invoice->invoice_no }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-black font-semibold py-0.5">Order Date</td>
                                                    <td class="text-black font-semibold px-2">:</td>
                                                    <td class="text-black text-right">{{ \Carbon\Carbon::parse($order->order_date)->format('d F Y') }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-black font-semibold py-0.5">Deadline</td>
                                                    <td class="text-black font-semibold px-2">:</td>
                                                    <td class="text-black text-right">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d F Y') : '-' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- Company Address --}}
                                    <div class="text-center text-sm text-black">
                                        <p>Jl. KH Muhdi Demangan, Maguwoharjo, Depok, Sleman, Yogyakarta</p>
                                        <p>0823 1377 8296 - 0858 7067 1741</p>
                                    </div>
                                </div>

                                {{-- Bill To & Detail Product (2 Columns) --}}
                                <h3 class="text-sm font-bold text-black pb-1">RECIPIENT :</h3>
                                <div class="grid grid-cols-2 gap-6 mb-4">
                                    {{-- Data Customers --}}
                                    <div class="mt-1">
                                        <div class="text-sm space-y-2">
                                            <div>
                                                <p class="text-black font-semibold">{{ $order->customer->customer_name }}</p>
                                            </div>
                                            <div>
                                                <p class="text-black">
                                                    @php
                                                        $phone = $order->customer->phone;
                                                        $maskedPhone = strlen($phone) > 8 
                                                            ? substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 8) . substr($phone, -4)
                                                            : $phone;
                                                    @endphp
                                                    {{ $maskedPhone }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-black leading-relaxed">{{ $order->customer->address }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Detail Product --}}
                                    <div class="flex justify-end">
                                        <table class="text-sm">
                                            <tr>
                                                <td class="text-black font-semibold py-1" style="width: 75px; padding-right: 4px;">Product</td>
                                                <td class="text-black font-semibold" style="width: 8px; padding-right: 8px;">:</td>
                                                <td class="text-black">{{ $order->productCategory->name ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-black font-semibold py-1" style="width: 75px; padding-right: 4px;">Material</td>
                                                <td class="text-black font-semibold" style="width: 8px; padding-right: 8px;">:</td>
                                                <td class="text-black">{{ $order->materialCategory->name ?? '-' }} - {{ $order->materialTexture->name ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-black font-semibold py-1" style="width: 75px; padding-right: 4px;">Color</td>
                                                <td class="text-black font-semibold" style="width: 8px; padding-right: 8px;">:</td>
                                                <td class="text-black">{{ $order->product_color ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-black font-semibold py-1" style="width: 75px; padding-right: 4px;">Total QTY</td>
                                                <td class="text-black font-semibold" style="width: 8px; padding-right: 8px;">:</td>
                                                <td class="text-black font-semibold">{{ $order->orderItems->sum('qty') }} pcs</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                {{-- Order Items Table --}}
                                <h3 class="text-sm font-bold text-black mb-2">DETAIL ORDERS :</h3>
                                <div class="mb-4">
                                    <table class="w-full text-xs">
                                        <thead style="background-color: #5a5a5a">
                                            <tr>
                                                <th class="py-2 px-3 text-left font-semibold uppercase tracking-wide" style="color: #ffffff;">
                                                    Product & Size</th>
                                                <th class="py-2 px-3 text-center font-semibold uppercase tracking-wide" style="color: #ffffff;">Qty
                                                </th>
                                                <th class="py-2 px-3 text-right font-semibold uppercase tracking-wide" style="color: #ffffff;">Price
                                                </th>
                                                <th class="py-2 px-3 text-right font-semibold uppercase tracking-wide" style="color: #ffffff;">Total
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($order->designVariants as $variant)
                                                {{-- Variant Header --}}
                                                <tr class="bg-gray-100">
                                                    <td colspan="4" class="py-1.5 px-3 font-semibold text-gray-900">
                                                        Variant: {{ $variant->design_name }}
                                                    </td>
                                                </tr>

                                                @php
                                                    $itemsBySleeve = $variant->orderItems->groupBy('sleeve_id');
                                                @endphp

                                                @foreach ($itemsBySleeve as $sleeveId => $items)
                                                    {{-- Sleeve Header --}}
                                                    <tr class="bg-gray-50">
                                                        <td colspan="4" class="py-1 px-3 pl-5 italic text-gray-600 text-xs">
                                                            Sleeve: {{ $items->first()->sleeve->sleeve_name ?? 'Unknown' }}
                                                        </td>
                                                    </tr>

                                                    {{-- Items --}}
                                                    @foreach ($items as $item)
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="py-1.5 px-3 pl-8 text-gray-900">{{ $item->size->size_name ?? '-' }}</td>
                                                            <td class="py-1.5 px-3 text-center text-gray-900">{{ $item->qty }}</td>
                                                            <td class="py-1.5 px-3 text-right text-gray-900">
                                                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                                            <td class="py-1.5 px-3 text-right text-gray-900">
                                                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            @endforeach

                                            {{-- Extra Services --}}
                                            @if ($order->extraServices->count() > 0)
                                                <tr class="bg-gray-100">
                                                    <td colspan="4" class="py-1.5 px-3 font-semibold text-gray-900">
                                                        Additional Services
                                                    </td>
                                                </tr>
                                                @foreach ($order->extraServices as $extra)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="py-1.5 px-3 pl-5 text-gray-900">{{ $extra->service->service_name ?? 'Service' }}
                                                        </td>
                                                        <td class="py-1.5 px-3 text-center text-gray-900">1</td>
                                                        <td class="py-1.5 px-3 text-right text-gray-900">
                                                            Rp {{ number_format($extra->price, 0, ',', '.') }}</td>
                                                        <td class="py-1.5 px-3 text-right text-gray-900">
                                                            Rp {{ number_format($extra->price, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Footer --}}
                                <div class="grid grid-cols-2 gap-4 mt-4 pt-3 border-t border-gray-200">
                                    {{-- Bank Info --}}
                                    <div class="border border-gray-300 rounded-lg p-3 bg-gray-50">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Rekening Bank Central Asia</p>
                                        <p class="text-xl font-bold text-gray-900 mb-0.5 tracking-wider">7315 40 1313</p>
                                        <p class="text-xs font-semibold text-gray-900 mb-2 pb-2 border-b border-gray-300">APRI KUSUMA PRAWIRA</p>
                                        <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                            <li>Untuk pembayaran DP minimal 60% dari total harga, pelunasan dilakukan saat pengambilan barang.</li>
                                            <li>Jangan lupa mengirimkan bukti transfer DP untuk konfirmasi orderan.</li>
                                            <li>Pastikan sudah mengecek keseluruhan detail sesuai dengan pesanannya.</li>
                                        </ul>
                                    </div>

                                    {{-- Price Summary --}}
                                    <div class="border border-gray-300 rounded-lg overflow-hidden">
                                        <div class="divide-y divide-gray-200">
                                            @php
                                                $subtotal = $order->orderItems->sum('subtotal');
                                                $additional = $order->extraServices->sum('price');
                                                $discount = $order->discount ?? 0;
                                                $total = $subtotal + $additional - $discount;
                                                // Get all approved payments for calculation (including fiktif)
                                                $allApprovedPayments = $order->invoice->payments->where('status', 'approved');
                                                $totalPaid = $allApprovedPayments->sum('amount');
                                                // Filter out fiktif payments for display only
                                                $approvedPayments = $allApprovedPayments->where('amount', '>', 10)->sortBy('paid_at');
                                                $remainingDue = $total - $totalPaid;
                                            @endphp

                                            <div class="flex justify-between items-center py-2 px-3 bg-white">
                                                <span class="text-xs font-semibold">Subtotal</span>
                                                <span class="text-xs font-bold">Rp
                                                    {{ number_format($subtotal, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 px-3 bg-white">
                                                <span class="text-xs font-semibold">Additional</span>
                                                <span class="text-xs font-bold"
                                                    style="color: #000000;">{{ $additional > 0 ? 'Rp ' . number_format($additional, 0, ',', '.') : '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 px-3 bg-white">
                                                <span class="text-xs font-semibold">Discount</span>
                                                <span class="text-xs font-bold"
                                                    style="color: #000000;">{{ $discount > 0 ? '- Rp ' . number_format($discount, 0, ',', '.') : '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2.5 px-3" style="background-color: #5a5a5a">
                                                <span class="text-sm font-extrabold" style="color: #ffffff;">Total Price</span>
                                                <span class="text-sm font-extrabold" style="color: #ffffff;">Rp
                                                    {{ number_format($total, 0, ',', '.') }}</span>
                                            </div>

                                            @if ($approvedPayments->count() > 0)
                                                {{-- Payment Activity Header --}}
                                                <div class="py-2 px-3 bg-gray-100 text-center">
                                                    <span class="text-xs font-bold">PAYMENT ACTIVITY</span>
                                                </div>

                                                @foreach ($approvedPayments as $index => $payment)
                                                    <div class="flex justify-between items-center py-2 px-3 bg-white">
                                                        <span class="text-xs text-gray-600">
                                                            Payment #{{ $index + 1 }}
                                                            ({{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') }})
                                                        </span>
                                                        <span class="text-xs font-semibold text-gray-900">
                                                            - Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Remaining Due --}}
                                            @if($remainingDue == 0)
                                                {{-- LUNAS --}}
                                                <div class="py-3 px-3 bg-red-500 text-center">
                                                    <span class="text-2xl font-extrabold text-white">LUNAS</span>
                                                </div>
                                            @else
                                                {{-- Still have remaining due --}}
                                                <div class="flex justify-between items-center py-2.5 px-3 bg-red-600">
                                                    <span class="text-sm font-extrabold text-white">Remaining Due</span>
                                                    <span class="text-sm font-extrabold text-white">
                                                        Rp {{ number_format($remainingDue, 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                        
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end items-center gap-3">
                        {{-- Download PDF Button --}}
                        <a href="{{ route('admin.orders.invoice.download', $order->id) }}" target="_blank"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 rounded-md transition duration-150">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="hidden md:inline ml-2">Download PDF</span>
                        </a>

                        {{-- Close Button --}}
                        <button @click="openInvoiceModal = false" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= ADD PAYMENT MODAL ================= --}}
        <div x-show="openPaymentModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50">
            
            {{-- Background Overlay --}}
            <div x-show="openPaymentModal" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="openPaymentModal = false"
                    class="relative bg-white rounded-xl shadow-lg w-full max-w-3xl"
                    style="height: min(calc(100vh - 6rem), 700px); min-height: 0; display: flex; flex-direction: column;"
                    x-data="{
                        paymentAmount: '',
                        isSubmittingPayment: false,
                        paymentErrors: {},
                        
                        resetPaymentForm() {
                            this.paymentAmount = '';
                            this.isSubmittingPayment = false;
                            this.paymentErrors = {};
                        }
                    }">

                    {{-- Fixed Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <h3 class="text-lg font-semibold text-gray-900">Add Payment</h3>
                        <button @click="openPaymentModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            ✕
                        </button>
                    </div>

                    {{-- Scrollable Content --}}
                    <div class="overflow-y-auto flex-1">
                        <form id="addPaymentForm" class="px-6 py-4"
                            @submit.prevent="
                                // Frontend validation
                                paymentErrors = {};
                                let hasValidationError = false;
                                const formData = new FormData($event.target);
                            
                            if (!formData.get('payment_method')) {
                                paymentErrors.payment_method = ['Payment method is required'];
                                hasValidationError = true;
                            }
                            
                            if (!formData.get('payment_type')) {
                                paymentErrors.payment_type = ['Payment type is required'];
                                hasValidationError = true;
                            }
                            
                            const amount = formData.get('amount');
                            if (!amount || amount === '0' || amount === '') {
                                paymentErrors.amount = ['Amount is required'];
                                hasValidationError = true;
                            }
                            
                            const imageFile = formData.get('image');
                            if (!imageFile || imageFile.size === 0) {
                                paymentErrors.image = ['Payment proof image is required'];
                                hasValidationError = true;
                            }
                            
                            if (hasValidationError) {
                                return;
                            }
                            
                            // Proceed with submission
                            isSubmittingPayment = true;
                            
                            fetch('{{ route('admin.payments.store') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                isSubmittingPayment = false;
                                
                                if (data.success) {
                                    // Show success toast
                                    window.dispatchEvent(new CustomEvent('show-toast', {
                                        detail: {
                                            message: '✅ Payment added successfully!',
                                            type: 'success'
                                        }
                                    }));
                                    
                                    // Close modal and reload after short delay
                                    setTimeout(() => {
                                        openPaymentModal = false;
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    // Handle validation errors
                                    if (data.errors) {
                                        paymentErrors = data.errors;
                                    }
                                    
                                    // Show error message if available
                                    if (data.message) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: {
                                                message: '❌ ' + data.message,
                                                type: 'error'
                                            }
                                        }));
                                    }
                                }
                            })
                            .catch(error => {
                                isSubmittingPayment = false;
                                console.error('Error:', error);
                                
                                window.dispatchEvent(new CustomEvent('show-toast', {
                                    detail: {
                                        message: '❌ Failed to add payment. Please try again.',
                                        type: 'error'
                                    }
                                }));
                            });
                        ">
                        <input type="hidden" name="invoice_id" value="{{ $order->invoice->id }}">

                        <div class="p-5 space-y-4 max-h-[calc(100vh-250px)] overflow-y-auto">
                            {{-- Invoice Info --}}
                            <div class="bg-gray-50 border border-gray-200 rounded-md p-3 space-y-3">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Invoice No</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ $order->invoice->invoice_no }}</p>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <p class="text-xs text-gray-500">Remaining Due</p>
                                        <p class="text-sm font-semibold text-red-600">Rp {{ number_format($order->invoice->amount_due, 0, ',', '.') }}</p>
                                    </div>
                                </div>

                                @php
                                    $pendingPayments = $order->invoice->payments->where('status', 'pending');
                                    $pendingCount = $pendingPayments->count();
                                    $pendingAmount = $pendingPayments->sum('amount');
                                @endphp

                                <div class="flex items-start justify-between gap-4 pt-2 border-t border-gray-300">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Pending Transaction</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ $pendingCount }} Transaction(s)</p>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <p class="text-xs text-gray-500">Pending Amount</p>
                                        <p class="text-sm font-semibold text-orange-600">Rp {{ number_format($pendingAmount, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Method & Type (1 Row) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Payment Method --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Method <span class="text-red-600">*</span>
                                    </label>
                                    <div x-data="{
                                        open: false,
                                        options: [
                                            { value: 'transfer', name: 'Transfer' },
                                            { value: 'cash', name: 'Cash' }
                                        ],
                                        selected: null,
                                        selectedValue: '',
                                        
                                        select(option) {
                                            this.selected = option;
                                            this.selectedValue = option.value;
                                            this.open = false;
                                            if (paymentErrors.payment_method) {
                                                delete paymentErrors.payment_method;
                                            }
                                        }
                                    }" class="relative w-full">
                                        <button type="button" @click="open = !open"
                                            :class="paymentErrors.payment_method ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="selected ? selected.name : 'Select Method'" :class="!selected ? 'text-gray-400' : 'text-gray-500'"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <input type="hidden" name="payment_method" x-model="selectedValue">

                                        <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                            <ul class="max-h-60 overflow-y-auto py-1">
                                                <template x-for="option in options" :key="option.value">
                                                    <li @click="select(option)"
                                                        class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                        :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.value === option.value }">
                                                        <span x-text="option.name"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                    <p x-show="paymentErrors.payment_method" x-cloak x-text="paymentErrors.payment_method?.[0]" class="mt-1 text-sm text-red-600"></p>
                                </div>

                                {{-- Payment Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Payment Type <span class="text-red-600">*</span>
                                    </label>
                                    <div x-data="{
                                        open: false,
                                        options: [
                                            { value: 'dp', name: 'DP (Down Payment)' },
                                            { value: 'repayment', name: 'Repayment' },
                                            { value: 'full_payment', name: 'Full Payment' }
                                        ],
                                        selected: null,
                                        selectedValue: '',
                                        
                                        select(option) {
                                            this.selected = option;
                                            this.selectedValue = option.value;
                                            this.open = false;
                                            
                                            if (option.value === 'repayment' || option.value === 'full_payment') {
                                                const remainingDue = {{ $order->invoice->amount_due }};
                                                paymentAmount = Math.floor(remainingDue).toLocaleString('id-ID');
                                            }
                                            
                                            if (paymentErrors.payment_type) {
                                                delete paymentErrors.payment_type;
                                            }
                                        }
                                    }" class="relative w-full">
                                        <button type="button" @click="open = !open"
                                            :class="paymentErrors.payment_type ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                            class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                            <span x-text="selected ? selected.name : 'Select Type'" :class="!selected ? 'text-gray-400' : 'text-gray-500'"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <input type="hidden" name="payment_type" x-model="selectedValue">

                                        <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                            <ul class="max-h-60 overflow-y-auto py-1">
                                                <template x-for="option in options" :key="option.value">
                                                    <li @click="select(option)"
                                                        class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                        :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.value === option.value }">
                                                        <span x-text="option.name"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                    <p x-show="paymentErrors.payment_type" x-cloak x-text="paymentErrors.payment_type?.[0]" class="mt-1 text-sm text-red-600"></p>
                                </div>
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Amount <span class="text-red-600">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
                                    <input type="text" x-model="paymentAmount"
                                        @input="
                                            let value = $event.target.value.replace(/[^\d]/g, '');
                                            paymentAmount = parseInt(value || 0).toLocaleString('id-ID');
                                            $event.target.nextElementSibling.value = value;
                                        "
                                        placeholder="0"
                                        :class="paymentErrors.amount ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full rounded-md pl-10 pr-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                                    <input type="hidden" name="amount" :value="paymentAmount.replace(/[^\d]/g, '')">
                                </div>
                                <p x-show="paymentErrors.amount" x-cloak x-text="paymentErrors.amount?.[0]" class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Payment Proof Image --}}
                            <div x-data="{
                                imagePreview: null,
                                fileName: '',
                                isDragging: false,
                                handleFileChange(event) {
                                    const file = event.target.files[0];
                                    this.processFile(file);
                                },
                                processFile(file) {
                                    if (file && file.type.startsWith('image/')) {
                                        this.fileName = file.name;
                                        const reader = new FileReader();
                                        reader.onload = (e) => {
                                            this.imagePreview = e.target.result;
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                },
                                handleDrop(event) {
                                    this.isDragging = false;
                                    const file = event.dataTransfer.files[0];
                                    if (file) {
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        this.$refs.fileInput.files = dataTransfer.files;
                                        this.processFile(file);
                                    }
                                },
                                handleDragOver(event) {
                                    this.isDragging = true;
                                },
                                handleDragLeave() {
                                    this.isDragging = false;
                                }
                            }">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Proof <span
                                        class="text-red-500">*</span></label>

                        {{-- Drag & Drop Area --}}
                        <div @drop.prevent="handleDrop($event)"
                            @dragover.prevent="handleDragOver($event)"
                            @dragleave="handleDragLeave()"
                            @click="$refs.fileInput.click()"
                            :class="{
                                'border-primary bg-primary/5': isDragging && !imagePreview,
                                'border-red-500 bg-red-50': paymentErrors.image && !imagePreview,
                                'border-gray-300 bg-gray-50': !isDragging && !paymentErrors.image && !imagePreview,
                                'border-gray-200 bg-white p-2': imagePreview,
                                'p-6': !imagePreview
                            }"
                            class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary"
                            :style="imagePreview ? 'min-height: 200px;' : ''">
                            <input type="file" x-ref="fileInput" name="image" accept="image/jpeg,image/png,image/jpg"
                                @change="handleFileChange($event)"
                                class="hidden">
                            
                            {{-- Image Preview (Full Container) --}}
                            <div x-show="imagePreview" x-cloak class="relative w-full h-full flex items-center justify-center">
                                <img :src="imagePreview" alt="Preview"
                                    class="w-full h-auto max-h-80 object-contain rounded-lg">
                                <button type="button" @click.stop="imagePreview = null; fileName = ''; $refs.fileInput.value = ''"
                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            {{-- Upload Icon & Text (Only when no preview) --}}
                            <div x-show="!imagePreview">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-gray-700">
                                        <span class="text-primary font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PNG, JPG, JPEG (Max 10MB)</p>
                                </div>
                            </div>

                            {{-- File Name Display --}}
                            <div x-show="fileName && !imagePreview" x-cloak class="mt-3 pt-3 border-t border-gray-200">
                                <div class="flex items-center justify-center gap-2 text-sm text-gray-700">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span x-text="fileName" class="font-medium truncate max-w-xs"></span>
                                </div>
                            </div>
                        </div>

                                <p class="mt-1 text-xs text-gray-500">Drag and drop your image here or click to browse</p>
                                <p x-show="paymentErrors.image" x-cloak x-text="paymentErrors.image?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="3"
                                    :class="paymentErrors.notes ?
                                        'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                        'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                    placeholder="Optional payment notes..."
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700"></textarea>
                                <p x-show="paymentErrors.notes" x-cloak x-text="paymentErrors.notes?.[0]"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>
                        </div>
                    </form>
                    </div>

                    {{-- Fixed Footer --}}
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                        <button type="button" @click="openPaymentModal = false; resetPaymentForm()"
                            :disabled="isSubmittingPayment"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            Cancel
                        </button>
                        <button type="submit" form="addPaymentForm" :disabled="isSubmittingPayment"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed flex items-center gap-2">
                            {{-- Loading Spinner --}}
                            <svg x-show="isSubmittingPayment" x-cloak class="animate-spin h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="isSubmittingPayment ? 'Processing...' : 'Add Payment'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= CANCEL CONFIRMATION MODAL ================= --}}
        <div x-show="showCancelConfirm" x-cloak
            class="fixed inset-0 z-50">
            
            {{-- Background Overlay --}}
            <div x-show="showCancelConfirm" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showCancelConfirm = false"
                    class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
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
        </div>

        {{-- ================= MOVE TO SHIPPING CONFIRMATION MODAL ================= --}}
        <div x-show="showMoveToShippingConfirm" x-cloak
            class="fixed inset-0 z-50">
            
            {{-- Background Overlay --}}
            <div x-show="showMoveToShippingConfirm" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
            
            {{-- Modal Panel --}}
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div @click.away="showMoveToShippingConfirm = false"
                    class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
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
