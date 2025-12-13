<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->invoice->invoice_no }}</title>
    <style>
        @page {
            margin: 10mm;
        }
        
        body {
            font-family: serif;
            margin: 0;
            padding: 0;
        }
        
        /* Prevent page breaks inside these elements */
        .no-break {
            page-break-inside: avoid;
        }
        
        /* Prevent orphan rows at page bottom */
        tbody tr {
            page-break-inside: avoid;
        }
        
        /* Keep header with at least 2 rows */
        thead {
            display: table-header-group;
        }
    </style>
</head>
<body>
    
    <div style="width: 100%; min-height: 297mm; background-color: #ffffff;">
        <div style="padding: 10mm;">
            
            {{-- Header Section --}}
            <div style="border-bottom: 2px solid #000000; padding-bottom: 16px; margin-bottom: 24px;" class="no-break">
                <table style="width: 100%; margin-bottom: 8px;">
                    <tr>
                        {{-- Logo --}}
                        <td style="width: 50%; vertical-align: top;">
                            <img src="{{ public_path('images/logo-invoice.png') }}" alt="STGR Logo" style="height: 96px; width: auto;">
                        </td>
                        
                        {{-- Invoice Info --}}
                        <td style="width: 50%; vertical-align: top; text-align: right;">
                            <p style="font-size: 32px; font-weight: bold; color: #000000; margin: 0 0 8px 0;">INVOICE</p>
                            <table style="font-size: 11px; display: inline-table; margin-left: auto;">
                                <tr>
                                    <td style="color: #000000; font-weight: 600; padding: 2px 0;">No</td>
                                    <td style="color: #000000; font-weight: 600; padding: 0 8px;">:</td>
                                    <td style="color: #000000; text-align: right;">{{ $order->invoice->invoice_no }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #000000; font-weight: 600; padding: 2px 0;">Order Date</td>
                                    <td style="color: #000000; font-weight: 600; padding: 0 8px;">:</td>
                                    <td style="color: #000000; text-align: right;">{{ \Carbon\Carbon::parse($order->order_date)->format('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #000000; font-weight: 600; padding: 2px 0;">Deadline</td>
                                    <td style="color: #000000; font-weight: 600; padding: 0 8px;">:</td>
                                    <td style="color: #000000; text-align: right;">{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d F Y') : '-' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                {{-- Company Address --}}
                <div style="text-align: center; font-size: 14px; color: #000000;">
                    <p style="margin: 4px 0;">Jl. KH Muhdi Demangan, Maguwoharjo, Depok, Sleman, Yogyakarta</p>
                    <p style="margin: 4px 0;">0823 1377 8296 - 0858 7067 1741</p>
                </div>
            </div>

            {{-- Bill To & Detail Product (2 Columns) --}}
            <h3 style="font-size: 14px; font-weight: bold; color: #000000; padding-bottom: 4px; margin: 0 0 4px 0;">RECIPIENT :</h3>
            <table style="width: 100%; margin-bottom: 16px;" class="no-break">
                <tr>
                    {{-- Data Customers --}}
                    <td style="width: 50%; vertical-align: top; padding-right: 24px;">
                        <div style="font-size: 14px; margin-top: 4px;">
                            <p style="color: #000000; font-weight: 600; margin: 0 0 8px 0;">{{ $order->customer->customer_name }}</p>
                            <p style="color: #000000; margin: 0 0 8px 0;">
                                @php
                                    $phone = $order->customer->phone;
                                    $maskedPhone = strlen($phone) > 8 
                                        ? substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 8) . substr($phone, -4)
                                        : $phone;
                                @endphp
                                {{ $maskedPhone }}
                            </p>
                            <p style="color: #000000; line-height: 1.6; margin: 0;">{{ $order->customer->address }}</p>
                        </div>
                    </td>

                    {{-- Detail Product --}}
                    <td style="width: 50%; vertical-align: top; padding-left: 24px;">
                        <table style="font-size: 14px; width: 100%;">
                            <tr>
                                <td style="width: 40%;"></td>
                                <td style="color: #000000; font-weight: 600; padding: 4px 0; width: 75px; padding-right: 4px; white-space: nowrap;">Product</td>
                                <td style="color: #000000; font-weight: 600; width: 8px; padding-right: 8px; white-space: nowrap;">:</td>
                                <td style="color: #000000; white-space: nowrap;">{{ $order->productCategory->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="width: 40%;"></td>
                                <td style="color: #000000; font-weight: 600; padding: 4px 0; width: 75px; padding-right: 4px; white-space: nowrap;">Material</td>
                                <td style="color: #000000; font-weight: 600; width: 8px; padding-right: 8px; white-space: nowrap;">:</td>
                                <td style="color: #000000; white-space: nowrap;">{{ $order->materialCategory->name ?? '-' }} - {{ $order->materialTexture->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="width: 40%;"></td>
                                <td style="color: #000000; font-weight: 600; padding: 4px 0; width: 75px; padding-right: 4px; white-space: nowrap;">Color</td>
                                <td style="color: #000000; font-weight: 600; width: 8px; padding-right: 8px; white-space: nowrap;">:</td>
                                <td style="color: #000000; white-space: nowrap;">{{ $order->product_color ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="width: 40%;"></td>
                                <td style="color: #000000; font-weight: 600; padding: 4px 0; width: 75px; padding-right: 4px; white-space: nowrap;">Total QTY</td>
                                <td style="color: #000000; font-weight: 600; width: 8px; padding-right: 8px; white-space: nowrap;">:</td>
                                <td style="color: #000000; font-weight: 600; white-space: nowrap;">{{ $order->orderItems->sum('qty') }} pcs</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Order Items Table --}}
            <h3 style="font-size: 14px; font-weight: bold; color: #000000; margin: 0 0 8px 0;">DETAIL ORDERS :</h3>
            <div style="margin-bottom: 16px;">
                <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                    <thead style="background-color: #5a5a5a;">
                        <tr>
                            <th style="padding: 8px 12px; text-align: left; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #ffffff;">
                                Product & Size</th>
                            <th style="padding: 8px 12px; text-align: center; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #ffffff;">Qty
                            </th>
                            <th style="padding: 8px 12px; text-align: right; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #ffffff;">Price
                            </th>
                            <th style="padding: 8px 12px; text-align: right; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #ffffff;">Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->designVariants as $variant)
                            {{-- Variant Header --}}
                            <tr style="background-color: #f3f4f6;" class="no-break">
                                <td colspan="4" style="padding: 6px 12px; font-weight: 600; color: #111827; border-top: 1px solid #e5e7eb;">
                                    Variant: {{ $variant->design_name }}
                                </td>
                            </tr>

                            @php
                                $itemsBySleeve = $variant->orderItems->groupBy('sleeve_id');
                            @endphp

                            @foreach ($itemsBySleeve as $sleeveId => $items)
                                {{-- Sleeve Header --}}
                                <tr style="background-color: #f9fafb;" class="no-break">
                                    <td colspan="4" style="padding: 4px 12px 4px 20px; font-style: italic; color: #4b5563; font-size: 12px; border-top: 1px solid #e5e7eb;">
                                        Sleeve: {{ $items->first()->sleeve->sleeve_name ?? 'Unknown' }}
                                    </td>
                                </tr>

                                {{-- Items --}}
                                @foreach ($items as $item)
                                    <tr class="no-break">
                                        <td style="padding: 6px 12px 6px 32px; color: #111827; border-top: 1px solid #e5e7eb;">{{ $item->size->size_name ?? '-' }}</td>
                                        <td style="padding: 6px 12px; text-align: center; color: #111827; border-top: 1px solid #e5e7eb;">{{ $item->qty }}</td>
                                        <td style="padding: 6px 12px; text-align: right; color: #111827; border-top: 1px solid #e5e7eb;">
                                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        <td style="padding: 6px 12px; text-align: right; color: #111827; border-top: 1px solid #e5e7eb;">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach

                        {{-- Extra Services --}}
                        @if ($order->extraServices->count() > 0)
                            <tr style="background-color: #f3f4f6;" class="no-break">
                                <td colspan="4" style="padding: 6px 12px; font-weight: 600; color: #111827; border-top: 1px solid #e5e7eb;">
                                    Additional Services
                                </td>
                            </tr>
                            @foreach ($order->extraServices as $extra)
                                <tr class="no-break">
                                    <td style="padding: 6px 12px 6px 20px; color: #111827; border-top: 1px solid #e5e7eb;">{{ $extra->service->service_name ?? 'Service' }}
                                    </td>
                                    <td style="padding: 6px 12px; text-align: center; color: #111827; border-top: 1px solid #e5e7eb;">1</td>
                                    <td style="padding: 6px 12px; text-align: right; color: #111827; border-top: 1px solid #e5e7eb;">
                                        Rp {{ number_format($extra->price, 0, ',', '.') }}</td>
                                    <td style="padding: 6px 12px; text-align: right; color: #111827; border-top: 1px solid #e5e7eb;">
                                        Rp {{ number_format($extra->price, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <table style="width: 100%; margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb;" class="no-break">
                <tr>
                    {{-- Bank Info --}}
                    <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                        <div style="border: 1px solid #d1d5db; border-radius: 8px; padding: 12px; background-color: #f9fafb;">
                            <p style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 4px 0;">Rekening Bank Central Asia</p>
                            <p style="font-size: 20px; font-weight: bold; color: #111827; margin: 0 0 2px 0; letter-spacing: 0.05em;">7315 40 1313</p>
                            <p style="font-size: 12px; font-weight: 600; color: #111827; margin: 0 0 8px 0; padding-bottom: 8px; border-bottom: 1px solid #d1d5db;">APRI KUSUMA PRAWIRA</p>
                            <p style="font-size: 12px; color: #4b5563; margin: 4px 0; line-height: 1.4;">• Untuk pembayaran DP minimal 60% dari total harga, pelunasan dilakukan saat pengambilan barang.</p>
                            <p style="font-size: 12px; color: #4b5563; margin: 4px 0; line-height: 1.4;">• Jangan lupa mengirimkan bukti transfer DP untuk konfirmasi orderan.</p>
                            <p style="font-size: 12px; color: #4b5563; margin: 4px 0; line-height: 1.4;">• Pastikan sudah mengecek keseluruhan detail sesuai dengan pesanannya.</p>
                        </div>
                    </td>

                    {{-- Price Summary --}}
                    <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                        <div style="border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden;">
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

                            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                                <tr style="background-color: #ffffff;">
                                    <td style="padding: 8px 12px; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Subtotal</td>
                                    <td style="padding: 8px 12px; text-align: right; font-weight: bold; border-bottom: 1px solid #e5e7eb;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="padding: 8px 12px; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Additional</td>
                                    <td style="padding: 8px 12px; text-align: right; font-weight: bold; color: #000000; border-bottom: 1px solid #e5e7eb;">{{ $additional > 0 ? 'Rp ' . number_format($additional, 0, ',', '.') : '-' }}</td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="padding: 8px 12px; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Discount</td>
                                    <td style="padding: 8px 12px; text-align: right; font-weight: bold; color: #000000; border-bottom: 1px solid #e5e7eb;">{{ $discount > 0 ? '- Rp ' . number_format($discount, 0, ',', '.') : '-' }}</td>
                                </tr>
                                <tr style="background-color: #5a5a5a;">
                                    <td style="padding: 10px 12px; font-weight: 800; font-size: 14px; color: #ffffff;">Total Price</td>
                                    <td style="padding: 10px 12px; text-align: right; font-weight: 800; font-size: 14px; color: #ffffff;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                </tr>

                                @if ($approvedPayments->count() > 0)
                                    {{-- Payment Activity Header --}}
                                    <tr style="background-color: #f3f4f6;">
                                        <td colspan="2" style="padding: 8px 12px; text-align: center; font-weight: bold;">PAYMENT ACTIVITY</td>
                                    </tr>

                                    @foreach ($approvedPayments as $index => $payment)
                                        <tr style="background-color: #ffffff;">
                                            <td style="padding: 8px 12px; color: #4b5563; border-top: 1px solid #e5e7eb;">
                                                Payment #{{ $index + 1 }}
                                                ({{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') }})
                                            </td>
                                            <td style="padding: 8px 12px; text-align: right; font-weight: 600; color: #111827; border-top: 1px solid #e5e7eb;">
                                                - Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                {{-- Remaining Due --}}
                                @if($remainingDue == 0)
                                    {{-- LUNAS --}}
                                    <tr style="background-color: #5a5a5a;">
                                        <td colspan="2" style="padding: 12px; text-align: center; font-weight: 800; font-size: 20px; color: #ffffff;">
                                            LUNAS
                                        </td>
                                    </tr>
                                @else
                                    {{-- Still have remaining due --}}
                                    <tr style="background-color: #dc2626;">
                                        <td style="padding: 10px 12px; font-weight: 800; font-size: 14px; color: #ffffff;">Remaining Due</td>
                                        <td style="padding: 10px 12px; text-align: right; font-weight: 800; font-size: 14px; color: #ffffff;">
                                            Rp {{ number_format($remainingDue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

        </div>
    </div>

</body>
</html>
