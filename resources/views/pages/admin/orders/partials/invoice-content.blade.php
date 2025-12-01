{{-- Invoice Preview Content - A4 Format (Simple, Elegant, Modern) --}}
@php
    $subtotal = $order->orderItems->sum('subtotal');
    $additional = $order->extraServices->sum('price');
    $discount = $order->discount ?? 0;
    $total = $subtotal + $additional - $discount;
    $approvedPayments = $order->invoice->payments->where('status', 'approved')->sortBy('paid_at');
    $totalPaid = $approvedPayments->sum('amount');
    $remainingDue = $total - $totalPaid;
    $isPaid = $remainingDue <= 0;
@endphp

<div class="bg-white mx-auto shadow-xl" style="width: 210mm; aspect-ratio: 1 / 1.414; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <div class="h-full flex flex-col p-8">
        
        {{-- SECTION 1: HEADER --}}
        <div class="mb-6 pb-6 border-b-2 border-gray-200">
            {{-- Logo & Invoice Info --}}
            <div class="flex items-start justify-between mb-5">
                {{-- Logo --}}
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/logo-invoice.png') }}" alt="STGR Logo" class="h-16 w-auto object-contain">
                </div>

                {{-- Invoice Info --}}
                <div class="text-right">
                    <h1 class="text-4xl font-bold text-slate-800 mb-3 tracking-tight">INVOICE</h1>
                    <div class="text-sm text-slate-600 space-y-1 bg-slate-50 px-4 py-3 rounded-lg">
                        <p class="flex justify-between gap-3"><span class="text-slate-500">Invoice No</span> <span class="font-semibold text-slate-900">{{ $order->invoice->invoice_no }}</span></p>
                        <p class="flex justify-between gap-3"><span class="text-slate-500">Date</span> <span class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($order->invoice->created_at)->format('d F Y') }}</span></p>
                        <p class="flex justify-between gap-3"><span class="text-slate-500">Deadline</span> <span class="font-medium text-slate-800">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d F Y') : '-' }}</span></p>
                    </div>
                </div>
            </div>

            {{-- Company Address --}}
            <div class="text-center text-xs text-slate-500 bg-slate-50 py-2.5 px-4 rounded-lg">
                Jl. KH Muhdi Demangan, Maguwoharjo, Depok, Sleman, Yogyakarta • 0823 1377 8296 - 0858 7067 1741
            </div>
        </div>

        {{-- SECTION 2: CUSTOMER & PRODUCT DETAILS (2 Columns) --}}
        <div class="grid grid-cols-2 gap-5 mb-6">
            {{-- Customer Details --}}
            <div class="rounded-xl p-4 bg-slate-50 border border-slate-200">
                <h3 class="text-xs font-bold text-slate-500 mb-3 uppercase tracking-wider">Customer Details</h3>
                <div class="space-y-2 text-sm">
                    <div>
                        <p class="font-bold text-slate-900 text-base">{{ $order->customer->customer_name }}</p>
                    </div>
                    <div class="text-slate-600 leading-relaxed">
                        <p>{{ $order->customer->address }}</p>
                        <p>{{ $locationData['village_name'] ?? '-' }}, {{ $locationData['district_name'] ?? '-' }}</p>
                        <p>{{ $locationData['city_name'] ?? '-' }}, {{ $locationData['province_name'] ?? '-' }}</p>
                    </div>
                    <div class="pt-2">
                        <p class="font-semibold text-slate-900">{{ $order->customer->phone }}</p>
                    </div>
                </div>
            </div>

            {{-- Product Details --}}
            <div class="rounded-xl p-4 bg-slate-50 border border-slate-200">
                <h3 class="text-xs font-bold text-slate-500 mb-3 uppercase tracking-wider">Product Details</h3>
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Product</span>
                        <span class="font-semibold text-slate-900">{{ $order->productCategory->product_name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Material</span>
                        <span class="font-semibold text-slate-900">{{ $order->materialCategory->material_name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Texture</span>
                        <span class="font-semibold text-slate-900">{{ $order->materialTexture->texture_name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Color</span>
                        <span class="font-semibold text-slate-900">{{ $order->product_color ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-slate-300">
                        <span class="text-slate-500">Total Quantity</span>
                        <span class="font-bold text-slate-900 text-base">{{ $order->total_qty ?? $order->orderItems->sum('qty') }} pcs</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 3: ORDER ITEMS TABLE --}}
        <div class="mb-6 flex-grow">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-800">
                        <th class="py-3 px-4 text-left font-semibold text-white text-xs uppercase tracking-wide">Product & Size</th>
                        <th class="py-3 px-4 text-center font-semibold text-white text-xs uppercase tracking-wide">Qty</th>
                        <th class="py-3 px-4 text-right font-semibold text-white text-xs uppercase tracking-wide">Price</th>
                        <th class="py-3 px-4 text-right font-semibold text-white text-xs uppercase tracking-wide">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($order->designVariants as $variant)
                        {{-- Design Variant Header --}}
                        <tr class="bg-slate-100 border-t-2 border-slate-200">
                            <td colspan="4" class="py-2.5 px-4 font-bold text-slate-800">
                                Design: {{ $variant->design_name }}
                            </td>
                        </tr>

                        @php
                            $itemsBySleeve = $variant->orderItems->groupBy('sleeve_id');
                        @endphp

                        @foreach ($itemsBySleeve as $sleeveId => $items)
                            {{-- Sleeve Type Header --}}
                            <tr class="bg-slate-50">
                                <td colspan="4" class="py-2 px-4 pl-8 italic text-slate-600 text-xs">
                                    Sleeve: {{ $items->first()->sleeve->sleeve_name ?? 'Unknown' }}
                                </td>
                            </tr>

                            {{-- Individual Items --}}
                            @foreach ($items as $item)
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="py-2.5 px-4 pl-12 text-slate-700">{{ $item->size->size_name ?? '-' }}</td>
                                    <td class="py-2.5 px-4 text-center text-slate-700">{{ $item->qty }}</td>
                                    <td class="py-2.5 px-4 text-right text-slate-700">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-4 text-right font-semibold text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach

                    {{-- Extra Services --}}
                    @if ($order->extraServices->count() > 0)
                        <tr class="bg-amber-50 border-t-2 border-amber-200">
                            <td colspan="4" class="py-2.5 px-4 font-bold text-slate-800">
                                Additional Services
                            </td>
                        </tr>
                        @foreach ($order->extraServices as $extra)
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="py-2.5 px-4 pl-8 text-slate-700">{{ $extra->service->service_name ?? 'Service' }}</td>
                                <td class="py-2.5 px-4 text-center text-slate-700">1</td>
                                <td class="py-2.5 px-4 text-right text-slate-700">Rp {{ number_format($extra->price, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-right font-semibold text-slate-900">Rp {{ number_format($extra->price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        {{-- SECTION 4: FOOTER (2 Columns) --}}
        <div class="grid grid-cols-2 gap-5 mt-auto">
            {{-- Left Column: Bank Info & Summary --}}
            <div class="rounded-xl p-4 bg-slate-50 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-500 mb-3 uppercase tracking-wider">Payment Information</h4>
                <div class="bg-white rounded-lg p-4 mb-3 border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-1.5">Bank Central Asia (BCA)</p>
                    <p class="text-2xl font-bold text-slate-900 mb-1 tracking-wide">7315 40 1313</p>
                    <p class="text-sm font-semibold text-slate-700">APRI KUSUMA PRAWIRA</p>
                </div>
                <div class="text-xs text-slate-600 space-y-1.5 leading-relaxed">
                    <p class="font-semibold text-slate-700 text-sm mb-2">Payment Terms:</p>
                    <ul class="space-y-1.5">
                        <li class="flex gap-2"><span class="text-slate-400">•</span><span>DP minimum 60% of total bill</span></li>
                        <li class="flex gap-2"><span class="text-slate-400">•</span><span>Send payment proof for confirmation</span></li>
                        <li class="flex gap-2"><span class="text-slate-400">•</span><span>Full payment upon pickup/delivery</span></li>
                        <li class="flex gap-2"><span class="text-slate-400">•</span><span>Verify all order specifications</span></li>
                    </ul>
                </div>
            </div>

            {{-- Right Column: Price Breakdown & Payment Status --}}
            <div class="rounded-xl overflow-hidden border border-slate-200">
                <div class="divide-y divide-slate-200">
                    {{-- Subtotal Items --}}
                    <div class="flex justify-between items-center py-3 px-4 bg-white">
                        <span class="text-sm text-slate-600">Subtotal Items</span>
                        <span class="text-sm font-semibold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>

                    {{-- Additional Services --}}
                    <div class="flex justify-between items-center py-3 px-4 bg-white">
                        <span class="text-sm text-slate-600">Additional Services</span>
                        <span class="text-sm font-semibold text-slate-900">{{ $additional > 0 ? 'Rp ' . number_format($additional, 0, ',', '.') : '-' }}</span>
                    </div>

                    {{-- Discount --}}
                    <div class="flex justify-between items-center py-3 px-4 bg-white">
                        <span class="text-sm text-slate-600">Discount</span>
                        <span class="text-sm font-semibold text-slate-900">{{ $discount > 0 ? '- Rp ' . number_format($discount, 0, ',', '.') : '-' }}</span>
                    </div>

                    {{-- Total Bill --}}
                    <div class="flex justify-between items-center py-4 px-4 bg-slate-900">
                        <span class="text-base font-bold text-white uppercase tracking-wide">Total Bill</span>
                        <span class="text-lg font-bold text-white">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    {{-- Payment Activity --}}
                    @if ($approvedPayments->count() > 0)
                        <div class="bg-slate-100 py-2.5 px-4">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Payment History</span>
                        </div>

                        @foreach ($approvedPayments as $index => $payment)
                            <div class="flex justify-between items-center py-2.5 px-4 bg-white">
                                <span class="text-xs text-slate-500">
                                    Payment #{{ $index + 1 }} • {{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') }}
                                </span>
                                <span class="text-xs font-semibold text-slate-700">- Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @endif

                    {{-- Payment Status --}}
                    @if ($isPaid)
                        <div class="py-5 px-4 bg-gradient-to-r from-emerald-500 to-emerald-600">
                            <p class="text-center text-2xl font-black text-white uppercase tracking-widest">LUNAS</p>
                        </div>
                    @else
                        <div class="py-4 px-4 bg-gradient-to-r from-rose-50 to-red-50 border-t-2 border-rose-200">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-rose-800">Remaining Due</span>
                                <span class="text-lg font-extrabold text-rose-900">Rp {{ number_format($remainingDue, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
