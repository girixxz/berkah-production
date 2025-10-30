{{-- Invoice Preview Content with Tailwind --}}
<div class="bg-white rounded-lg p-4 max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex items-start justify-between mb-4 pb-3 border-b border-gray-300">
        <div class="flex items-center">
            <img src="{{ asset('images/logo-invoice.png') }}" alt="STGR Logo" class="h-12 w-auto object-contain">
        </div>
        <div class="text-right">
            <h1 class="text-2xl font-bold text-gray-900 mb-1">INVOICE</h1>
            <div class="text-xs text-gray-600 space-y-0.5">
                <p><span class="text-gray-500">Invoice Number:</span> <span
                        class="font-semibold text-gray-900">{{ $order->invoice->invoice_no }}</span></p>
                <p><span class="text-gray-500">Invoice Date:</span> <span
                        class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($order->invoice->created_at)->format('d F Y') }}</span>
                </p>
                <p><span class="text-gray-500">Finish Date:</span> <span
                        class="font-semibold text-gray-900">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d F Y') : '-' }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Company Address --}}
    <div class="text-center text-xs text-gray-600 mb-3 pb-2 border-b border-gray-200">
        Jl. KH Muhdi Demangan, Maguwoharjo, Depok, Sleman, Daerah Istimewa Yogyakarta // 0823 1377 8296 - 0858 7067 1741
    </div>

    {{-- Recipient --}}
    <div class="mb-4">
        <h3 class="text-xs font-semibold text-gray-800 mb-2">Recipient:</h3>
        <div class="flex justify-between gap-6">
            {{-- Customer Info --}}
            <div class="flex-1 space-y-0.5 text-xs">
                <p class="font-semibold text-gray-900">{{ $order->customer->customer_name }}</p>
                <p class="text-gray-600">{{ $order->customer->address }}</p>
                <p class="text-gray-600">{{ $locationData['village_name'] ?? '-' }},
                    {{ $locationData['district_name'] ?? '-' }}</p>
                <p class="text-gray-600">{{ $locationData['city_name'] ?? '-' }},
                    {{ $locationData['province_name'] ?? '-' }}</p>
                <p class="text-gray-600">{{ $order->customer->phone }}</p>
            </div>

            {{-- Order Info --}}
            <div class="flex-1 space-y-0.5 text-xs text-right">
                <p class="text-gray-600">Order Quantity: <span
                        class="font-semibold text-gray-900">{{ $order->total_qty ?? $order->orderItems->sum('qty') }}</span>
                </p>
                <p class="text-gray-600">Product: <span
                        class="font-semibold text-gray-900">{{ $order->productCategory->product_name ?? '-' }}</span>
                </p>
                <p class="text-gray-600">Material: <span
                        class="font-semibold text-gray-900">{{ $order->materialCategory->material_name ?? '-' }}
                        - {{ $order->materialTexture->texture_name ?? '-' }}</span></p>
                <p class="text-gray-600">Color: <span
                        class="font-semibold text-gray-900">{{ $order->product_color ?? '-' }}</span></p>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="mb-4">
        <table class="w-full text-xs">
            <thead style="background-color: #1f2937;">
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
                    $approvedPayments = $order->invoice->payments->where('status', 'approved')->sortBy('paid_at');
                    $totalPaid = $approvedPayments->sum('amount');
                    $remainingDue = $total - $totalPaid;
                @endphp

                <div class="flex justify-between items-center py-2 px-3 bg-white">
                    <span class="text-xs font-semibold" style="color: #333333;">Sub Total</span>
                    <span class="text-xs font-bold" style="color: #000000;">Rp
                        {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 px-3 bg-white">
                    <span class="text-xs font-semibold" style="color: #333333;">Additional</span>
                    <span class="text-xs font-bold"
                        style="color: #000000;">{{ $additional > 0 ? 'Rp ' . number_format($additional, 0, ',', '.') : '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 px-3 bg-white">
                    <span class="text-xs font-semibold" style="color: #333333;">Discount</span>
                    <span class="text-xs font-bold"
                        style="color: #000000;">{{ $discount > 0 ? '- Rp ' . number_format($discount, 0, ',', '.') : '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-2.5 px-3" style="background-color: #1a1a1a;">
                    <span class="text-sm font-extrabold" style="color: #ffffff;">Total Price</span>
                    <span class="text-sm font-extrabold" style="color: #ffffff;">Rp
                        {{ number_format($total, 0, ',', '.') }}</span>
                </div>

                @if ($approvedPayments->count() > 0)
                    {{-- Payment Activity Header --}}
                    <div class="py-2 px-3 bg-gray-100">
                        <span class="text-xs font-semibold text-gray-600">PAYMENT ACTIVITY</span>
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
                <div class="flex justify-between items-center py-2.5 px-3 bg-yellow-50">
                    <span class="text-sm font-extrabold text-yellow-800">Remaining Due</span>
                    <span class="text-sm font-extrabold text-yellow-800">
                        Rp {{ number_format($remainingDue, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
