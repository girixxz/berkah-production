<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->invoice->invoice_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet" />
    <style>
        @page {
            margin: 10mm;
        }
        
        /* Prevent page breaks inside these elements */
        .no-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        /* Allow page breaks after these elements */
        .allow-break-after {
            page-break-after: auto;
            break-after: auto;
        }
        
        /* Prevent orphan rows at page bottom */
        tbody tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        /* Keep header with at least 2 rows */
        thead {
            display: table-header-group;
        }
        
        /* Keep footer at bottom of last page */
        tfoot {
            display: table-footer-group;
        }
    </style>
</head>
<body style="font-family: 'Lora', serif; margin: 0; padding: 0;">
    
    <div class="bg-white" style="width: 210mm; min-height: 297mm;">
        <div style="padding: 10mm;">
            
            {{-- Header Section --}}
            <div class="border-b-2 border-black pb-4 mb-6 no-break">
                <div class="flex justify-between items-center mb-2">
                    {{-- Logo --}}
                    <div class="flex-shrink-0">
                        <img src="{{ public_path('images/logo-invoice.png') }}" alt="STGR Logo" class="h-24 w-auto object-contain">
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
            <div class="grid grid-cols-2 gap-6 mb-4 no-break allow-break-after">
                {{-- Data Customers --}}
                <div class="mt-1">
                    <div class="text-sm space-y-2">
                        <div>
                            <p class="text-black font-semibold">{{ $order->customer->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-black">{{ $order->customer->phone }}</p>
                        </div>
                        <div>
                            <p class="text-black leading-relaxed">{{ $order->customer->address }}</p>
                        </div>
                    </div>
                </div>

                {{-- Detail Product --}}
                <div>
                    <table class="w-full text-sm">
                        <tr>
                            <td class="text-black font-semibold py-1" style="width: 35%;">Product</td>
                            <td class="text-black font-semibold px-2">:</td>
                            <td class="text-black">{{ $order->productCategory->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-black font-semibold py-1">Material</td>
                            <td class="text-black font-semibold px-2">:</td>
                            <td class="text-black">{{ $order->materialCategory->name ?? '-' }} - {{ $order->materialTexture->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-black font-semibold py-1">Color</td>
                            <td class="text-black font-semibold px-2">:</td>
                            <td class="text-black">{{ $order->product_color ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-black font-semibold py-1">Total QTY</td>
                            <td class="text-black font-semibold px-2">:</td>
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
                            <tr class="bg-gray-100 no-break">
                                <td colspan="4" class="py-1.5 px-3 font-semibold text-gray-900">
                                    Variant: {{ $variant->design_name }}
                                </td>
                            </tr>

                            @php
                                $itemsBySleeve = $variant->orderItems->groupBy('sleeve_id');
                            @endphp

                            @foreach ($itemsBySleeve as $sleeveId => $items)
                                {{-- Sleeve Header --}}
                                <tr class="bg-gray-50 no-break">
                                    <td colspan="4" class="py-1 px-3 pl-5 italic text-gray-600 text-xs">
                                        Sleeve: {{ $items->first()->sleeve->sleeve_name ?? 'Unknown' }}
                                    </td>
                                </tr>

                                {{-- Items --}}
                                @foreach ($items as $item)
                                    <tr class="hover:bg-gray-50 no-break">
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
                            <tr class="bg-gray-100 no-break">
                                <td colspan="4" class="py-1.5 px-3 font-semibold text-gray-900">
                                    Additional Services
                                </td>
                            </tr>
                            @foreach ($order->extraServices as $extra)
                                <tr class="hover:bg-gray-50 no-break">
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
            <div class="grid grid-cols-2 gap-4 mt-4 pt-3 border-t border-gray-200 no-break">
                {{-- Bank Info --}}
                <div class="border border-gray-300 rounded-lg p-3 bg-gray-50 no-break">
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
                <div class="border border-gray-300 rounded-lg overflow-hidden no-break">
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
                        <div class="flex justify-between items-center py-2.5 px-3 bg-red-600">
                            <span class="text-sm font-extrabold text-white">Remaining Due</span>
                            <span class="text-sm font-extrabold text-white">
                                Rp {{ number_format($remainingDue, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
