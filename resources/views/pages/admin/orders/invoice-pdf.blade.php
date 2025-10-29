<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice - {{ $order->invoice->invoice_no }}</title>

    <!-- Google Font: Lora -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet" />

    <style>
        /* ===================== DOMPDF OPTIMIZED - CLEAN & PRECISE ===================== */
        * {
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 20mm 25mm;
        }

        body {
            font-family: "Lora", serif;
            color: #333;
            font-size: 10pt;
            line-height: 1.4;
            padding: 20px 30px 20px 30px;
        }

        /* ===================== HEADER - SIMPLIFIED ===================== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header-table>tbody>tr>td {
            padding: 0;
            vertical-align: middle;
        }

        .logo {
            width: 85px;
            height: auto;
            display: block;
        }

        .invoice-title {
            font-size: 26pt;
            font-weight: 700;
            color: #2c2c2c;
            text-align: right;
            margin-bottom: 6px;
        }

        /* Meta dengan Float Right untuk Alignment Lebih Baik */
        .meta-info {
            text-align: right;
            font-size: 9pt;
            color: #666;
            line-height: 1.6;
        }

        .meta-info strong {
            color: #333;
            font-weight: 600;
        }

        /* ===================== COMPANY ADDRESS ===================== */
        .company-address {
            font-size: 8.5pt;
            color: #666;
            text-align: center;
            padding: 6px 0 8px 0;
            border-bottom: 1px solid #d0d0d0;
            margin-bottom: 12px;
        }

        /* ===================== RECIPIENT ===================== */
        .recipient-label {
            font-size: 10pt;
            font-weight: 600;
            margin-bottom: 6px;
            color: #333;
        }

        .recipient-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .recipient-table>tbody>tr>td {
            vertical-align: top;
            padding: 0;
            font-size: 9pt;
            line-height: 1.4;
        }

        .customer-name {
            font-weight: 600;
            margin-bottom: 3px;
            color: #333;
        }

        .customer-info p {
            color: #666;
            margin: 0 0 2px 0;
        }

        /* Order Info - Table for Perfect Alignment */
        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table td {
            padding: 1px 0;
            font-size: 9pt;
            line-height: 1.5;
        }

        .order-label {
            width: 105px;
            color: #666;
        }

        .order-colon {
            width: 12px;
            color: #666;
            text-align: center;
        }

        .order-value {
            font-weight: 600;
            color: #333;
            text-align: left;
        }

        /* ===================== ITEMS TABLE - ULTRA PRECISE ===================== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }

        .items-table thead {
            background-color: #5a5a5a;
            color: #ffffff;
        }

        .items-table th {
            padding: 7px 8px;
            font-weight: 600;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e8e8e8;
        }

        .items-table td {
            padding: 6px 8px;
            color: #333;
        }

        /* Variant Row */
        .variant-row {
            background-color: #f0f0f0;
        }

        .variant-row td {
            font-weight: 600;
            padding: 5px 8px;
        }

        /* Sleeve Row */
        .sleeve-row td {
            font-style: italic;
            color: #888;
            font-size: 8.5pt;
            padding-left: 15px;
        }

        /* Size Row with Indent */
        .size-indent {
            padding-left: 25px !important;
        }

        /* ===================== FOOTER - SPACED & CLEAN ===================== */
        .footer-wrapper {
            width: 100%;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #d0d0d0;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table>tbody>tr>td {
            vertical-align: top;
            padding: 0;
        }

        /* Bank Section */
        .bank-wrapper {
            border: 1px solid #d0d0d0;
            padding: 12px;
            background-color: #fafafa;
            margin-right: 10px;
        }

        .bank-title {
            font-size: 7.5pt;
            color: #888;
            margin-bottom: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bank-account {
            font-size: 16pt;
            font-weight: 700;
            color: #2c2c2c;
            margin-bottom: 3px;
            letter-spacing: 1.5px;
        }

        .bank-name {
            font-size: 9pt;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        .bank-notes {
            font-size: 7.5pt;
            color: #666;
            line-height: 1.6;
            padding-left: 14px;
            margin-top: 8px;
        }

        .bank-notes li {
            margin-bottom: 5px;
        }

        /* Summary Section */
        .summary-wrapper {
            border: 1px solid #d0d0d0;
            margin-left: 10px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 7px 12px;
            font-size: 9pt;
            border-bottom: 1px solid #e8e8e8;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 600;
            color: #333;
            text-align: right;
        }

        /* Total Row */
        .total-row {
            background-color: #2c2c2c;
        }

        .total-row td {
            font-weight: 700;
            font-size: 10pt;
            color: #ffffff;
            padding: 9px 12px;
        }
    </style>
</head>

<body>
    <!-- HEADER - SIMPLIFIED -->
    <table class="header-table">
        <tr>
            <td style="width: 30%">
                <img src="{{ public_path('images/logo-invoice.png') }}" alt="STGR Logo" class="logo" />
            </td>
            <td style="width: 70%">
                <div class="invoice-title">INVOICE</div>
                <div class="meta-info">
                    Invoice Number : <strong>{{ $order->invoice->invoice_no }}</strong><br />
                    Invoice Date :
                    <strong>{{ \Carbon\Carbon::parse($order->invoice->created_at)->format('d F Y') }}</strong><br />
                    Finish Date :
                    <strong>{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d F Y') : '-' }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- COMPANY ADDRESS -->
    <div class="company-address">
        Jl. KH Muhdi Demangan, Maguwoharjo, Depok, Sleman, Daerah Istimewa Yogyakarta // 0823 1377 8296 - 0858 7067 1741
    </div>

    <!-- RECIPIENT -->
    <div class="recipient-label">Recipient :</div>
    <div style="width: 100%; margin-bottom: 12px; overflow: hidden;">
        <!-- Customer Info (Left) -->
        <div style="width: 48%; float: left; font-size: 9pt; line-height: 1.4;">
            <p style="font-weight: 600; margin-bottom: 3px; color: #333;">{{ $order->customer->customer_name }}</p>
            <p style="color: #666; margin: 0 0 2px 0;">{{ $order->customer->address }}</p>
            <p style="color: #666; margin: 0 0 2px 0;">{{ $order->customer->village->village_name ?? '-' }},
                {{ $order->customer->village->district->district_name ?? '-' }},
                {{ $order->customer->village->district->city->city_name ?? '-' }},
                {{ $order->customer->village->district->city->province->province_name ?? '-' }}</p>
            <p style="color: #666; margin: 0;">{{ $order->customer->phone }}</p>
        </div>

        <!-- Order Info (Right) -->
        <div style="width: 48%; float: right; font-size: 9pt; text-align: right;">
            <div style="display: inline-block; text-align: left;">
                <p style="margin: 1px 0; color: #666;">Order Quantity: <span
                        style="font-weight: 600; color: #333;">{{ $order->total_qty ?? $order->orderItems->sum('qty') }}</span>
                </p>
                <p style="margin: 1px 0; color: #666;">Product: <span
                        style="font-weight: 600; color: #333;">{{ $order->productCategory->product_name ?? '-' }}</span>
                </p>
                <p style="margin: 1px 0; color: #666;">Material: <span
                        style="font-weight: 600; color: #333;">{{ $order->materialCategory->material_name ?? '-' }} -
                        {{ $order->materialTexture->texture_name ?? '-' }}</span></p>
                <p style="margin: 1px 0; color: #666;">Color: <span
                        style="font-weight: 600; color: #333;">{{ $order->product_color ?? '-' }}</span></p>
            </div>
        </div>

        <div style="clear: both;"></div>
    </div>

    <!-- ITEMS TABLE - INLINE WIDTH FOR PRECISION -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%; text-align: left">PRODUCT & SIZE</th>
                <th style="width: 15%; text-align: center">QTY</th>
                <th style="width: 17.5%; text-align: right">PRICE</th>
                <th style="width: 17.5%; text-align: right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->designVariants as $variant)
                <!-- Variant Header -->
                <tr style="background-color: #f0f0f0">
                    <td colspan="4" style="padding: 5px 8px; font-weight: 600; color: #333; font-size: 9pt;">
                        Variant : {{ $variant->design_name }}
                    </td>
                </tr>

                @php
                    $itemsBySleeve = $variant->orderItems->groupBy('sleeve_id');
                @endphp

                @foreach ($itemsBySleeve as $sleeveId => $items)
                    <!-- Sleeve Header -->
                    <tr>
                        <td colspan="4"
                            style="font-style: italic; color: #888; font-size: 8.5pt; padding-left: 15px;">
                            Sleeve : {{ $items->first()->sleeve->sleeve_name ?? 'Unknown' }}
                        </td>
                    </tr>

                    <!-- Items -->
                    @foreach ($items as $item)
                        <tr>
                            <td style="width: 50%; padding-left: 25px">{{ $item->size->size_name ?? '-' }}</td>
                            <td style="width: 15%; text-align: center">{{ $item->qty }}</td>
                            <td style="width: 17.5%; text-align: right">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td style="width: 17.5%; text-align: right">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach

            @if ($order->extraServices->count() > 0)
                <!-- Additional Services Header -->
                <tr style="background-color: #f0f0f0">
                    <td colspan="4" style="padding: 5px 8px; font-weight: 600; color: #333; font-size: 9pt;">
                        Additional Services
                    </td>
                </tr>

                @foreach ($order->extraServices as $extra)
                    <tr>
                        <td style="width: 50%; padding-left: 15px">{{ $extra->service->service_name ?? 'Service' }}
                        </td>
                        <td style="width: 15%; text-align: center">1</td>
                        <td style="width: 17.5%; text-align: right">Rp {{ number_format($extra->price, 0, ',', '.') }}
                        </td>
                        <td style="width: 17.5%; text-align: right">Rp {{ number_format($extra->price, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <!-- FOOTER - SPACED & CLEAN -->
    @php
        $subtotal = $order->orderItems->sum('subtotal');
        $additional = $order->extraServices->sum('price');
        $discount = $order->discount ?? 0;
        $total = $subtotal + $additional - $discount;
    @endphp

    <div style="width: 100%; margin-top: 20px; padding-top: 15px; border-top: 1px solid #d0d0d0; overflow: hidden;">
        <!-- Bank Info (Left Column) -->
        <div style="width: 55%; float: left; margin-right: 3%;">
            <div style="border: 1px solid #d0d0d0; padding: 12px; background-color: #fafafa;">
                <div
                    style="font-size: 7.5pt; color: #888; margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                    REKENING BANK CENTRAL ASIA
                </div>
                <div
                    style="font-size: 16pt; font-weight: 700; color: #2c2c2c; margin-bottom: 3px; letter-spacing: 1.5px;">
                    7315 40 1313
                </div>
                <div
                    style="font-size: 9pt; font-weight: 600; color: #333; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e0e0e0;">
                    APRI KUSUMA PRAWIRA
                </div>
                <ul style="font-size: 7.5pt; color: #666; line-height: 1.6; padding-left: 14px; margin-top: 8px;">
                    <li style="margin-bottom: 5px">Untuk pembayaran DP minimal 60% dari total harga, pelunasan dilakukan
                        saat pengambilan barang.</li>
                    <li style="margin-bottom: 5px">Jangan lupa mengirimkan bukti transfer DP untuk konfirmasi orderan.
                    </li>
                    <li style="margin-bottom: 5px">Pastikan sudah mengecek keseluruhan detail sesuai dengan pesanannya.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Price Summary (Right Column) - USING TABLE -->
        <div style="width: 42%; float: right;">
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #d0d0d0;">
                <tr>
                    <td style="padding: 8px 10px; font-size: 9pt; color: #666; border-bottom: 1px solid #e8e8e8;">Sub
                        Total</td>
                    <td
                        style="padding: 8px 10px; font-size: 9pt; font-weight: 600; color: #333; text-align: right; border-bottom: 1px solid #e8e8e8;">
                        Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 10px; font-size: 9pt; color: #666; border-bottom: 1px solid #e8e8e8;">
                        Additional</td>
                    <td
                        style="padding: 8px 10px; font-size: 9pt; font-weight: 600; color: #333; text-align: right; border-bottom: 1px solid #e8e8e8;">
                        {{ $additional > 0 ? 'Rp ' . number_format($additional, 0, ',', '.') : '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 10px; font-size: 9pt; color: #666; border-bottom: 1px solid #e8e8e8;">
                        Discount</td>
                    <td
                        style="padding: 8px 10px; font-size: 9pt; font-weight: 600; color: #333; text-align: right; border-bottom: 1px solid #e8e8e8;">
                        {{ $discount > 0 ? '- Rp ' . number_format($discount, 0, ',', '.') : '-' }}</td>
                </tr>
                <tr style="background-color: #2c2c2c;">
                    <td style="padding: 10px; font-size: 10pt; font-weight: 700; color: #ffffff;">Total Price</td>
                    <td style="padding: 10px; font-size: 10pt; font-weight: 700; color: #ffffff; text-align: right;">
                        Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <!-- Clear Float -->
        <div style="clear: both;"></div>
    </div>
</body>

</html>
