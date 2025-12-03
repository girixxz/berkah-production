<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->invoice->invoice_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: white;
            padding: 10mm;
            width: 210mm;
            min-height: 297mm;
        }

        /* Header Section */
        .header {
            border-bottom: 2px solid black;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .logo {
            flex-shrink: 0;
        }

        .logo img {
            height: 96px;
            width: auto;
            object-fit: contain;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-title {
            font-size: 36px;
            font-weight: bold;
            color: black;
            margin-bottom: 8px;
        }

        .invoice-table {
            font-size: 11px;
        }

        .invoice-table tr td:first-child {
            color: black;
            font-weight: 600;
            padding: 2px 0;
        }

        .invoice-table tr td:nth-child(2) {
            color: black;
            font-weight: 600;
            padding: 0 8px;
        }

        .invoice-table tr td:last-child {
            color: #4b5563;
            text-align: right;
        }

        .company-address {
            text-align: center;
            font-size: 14px;
            color: black;
            margin-top: 10px;
        }

        .company-address p {
            margin: 2px 0;
        }

        /* Bill To & Detail Product Section */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: black;
            text-transform: uppercase;
            border-bottom: 1px solid black;
            padding-bottom: 4px;
            margin-bottom: 12px;
        }

        .bill-to-content {
            font-size: 14px;
        }

        .bill-to-content > div {
            margin-bottom: 6px;
        }

        .bill-to-content .customer-name {
            color: black;
            font-weight: 600;
        }

        .bill-to-content .customer-phone,
        .bill-to-content .customer-address {
            color: #4b5563;
        }

        .bill-to-content .customer-address {
            line-height: 1.6;
        }

        .detail-product-table {
            width: 100%;
            font-size: 14px;
        }

        .detail-product-table tr td:first-child {
            color: black;
            font-weight: 600;
            padding: 4px 0;
            width: 35%;
        }

        .detail-product-table tr td:nth-child(2) {
            color: black;
            font-weight: 600;
            padding: 0 8px;
        }

        .detail-product-table tr td:last-child {
            color: #4b5563;
        }

        /* Order Items Section */
        .order-items {
            margin-bottom: 25px;
        }

        .design-header {
            background: #f3f4f6;
            border-left: 4px solid black;
            padding: 6px 12px;
            margin-bottom: 8px;
        }

        .design-header p {
            font-weight: 600;
            color: black;
            font-size: 14px;
        }

        .design-group {
            margin-bottom: 20px;
        }

        .sleeve-section {
            margin-left: 12px;
            margin-bottom: 12px;
        }

        .sleeve-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .sleeve-info .label {
            font-size: 12px;
            font-weight: 600;
            color: black;
        }

        .sleeve-info .value {
            font-size: 12px;
            font-weight: 500;
            color: #4b5563;
        }

        /* Tables */
        .items-table {
            width: 100%;
            font-size: 12px;
            border: 1px solid black;
            border-collapse: collapse;
        }

        .items-table thead tr {
            background: #e5e7eb;
        }

        .items-table thead th {
            padding: 6px 8px;
            text-align: left;
            border-right: 1px solid black;
            font-weight: 600;
            color: black;
        }

        .items-table thead th:last-child {
            border-right: none;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table thead th.text-center {
            text-align: center;
        }

        .items-table tbody {
            background: white;
        }

        .items-table tbody tr {
            border-top: 1px solid #d1d5db;
        }

        .items-table tbody td {
            padding: 6px 8px;
            border-right: 1px solid #d1d5db;
        }

        .items-table tbody td:last-child {
            border-right: none;
        }

        .items-table tbody td.text-right {
            text-align: right;
        }

        .items-table tbody td.text-center {
            text-align: center;
        }

        .items-table tbody td.text-black {
            color: black;
        }

        .items-table tbody td.text-gray {
            color: #4b5563;
        }

        .extra-price {
            font-size: 10px;
            color: #6b7280;
            margin-left: 4px;
        }

        .subtotal-row {
            background: #f3f4f6;
            font-weight: 600;
            border-top: 2px solid black;
        }

        .subtotal-row td {
            color: black;
        }

        /* Additionals Section */
        .additionals-section {
            margin-bottom: 20px;
        }

        /* Summary Section */
        .summary-section {
            border-top: 2px solid black;
            padding-top: 15px;
            margin-bottom: 25px;
        }

        .summary-wrapper {
            display: flex;
            justify-content: flex-end;
        }

        .summary-table-container {
            width: 400px;
        }

        .summary-table {
            width: 100%;
            font-size: 14px;
        }

        .summary-table tr {
            border-bottom: 1px solid #d1d5db;
        }

        .summary-table tr td:first-child {
            padding: 8px 0;
            color: black;
            font-weight: 600;
        }

        .summary-table tr td:last-child {
            padding: 8px 0;
            text-align: right;
            color: #4b5563;
        }

        .summary-table tr.total-row {
            border-top: 2px solid black;
            background: #f3f4f6;
        }

        .summary-table tr.total-row td:first-child {
            padding: 12px 8px;
            color: black;
            font-weight: bold;
            font-size: 16px;
        }

        .summary-table tr.total-row td:last-child {
            padding: 12px 8px;
            color: black;
            font-weight: bold;
            font-size: 18px;
        }

        .summary-table tr.status-row {
            border: none;
        }

        .summary-table tr.status-row td {
            padding: 12px 0;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 24px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }

        .status-pending {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .status-dp {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        /* Payment History Section */
        .payment-history {
            margin-bottom: 25px;
        }

        /* Notes Section */
        .notes-section {
            margin-bottom: 25px;
        }

        .notes-title {
            font-size: 14px;
            font-weight: bold;
            color: black;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .notes-content {
            font-size: 12px;
            color: #4b5563;
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        /* Footer */
        .footer {
            border-top: 2px solid black;
            padding-top: 15px;
            margin-top: 30px;
            text-align: center;
        }

        .footer p {
            font-size: 12px;
        }

        .footer .thank-you {
            color: #4b5563;
            margin-bottom: 4px;
        }

        .footer .company-name {
            color: black;
            font-weight: 600;
        }
    </style>
</head>
<body>
    {{-- Header Section --}}
    <div class="header">
        <div class="header-top">
            {{-- Logo --}}
            <div class="logo">
                <img src="{{ asset('images/logo-invoice.png') }}" alt="STGR Logo">
            </div>
            
            {{-- Invoice Info --}}
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <table class="invoice-table">
                    <tr>
                        <td>No</td>
                        <td>:</td>
                        <td>{{ $order->invoice->invoice_no }}</td>
                    </tr>
                    <tr>
                        <td>Order Date</td>
                        <td>:</td>
                        <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Deadline</td>
                        <td>:</td>
                        <td>{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('d F Y') : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Company Address --}}
        <div class="company-address">
            <p>Jl. KH Muhdi Demangan, Maguwoharjo, Depok, Sleman, Yogyakarta</p>
            <p>0823 1377 8296 - 0858 7067 1741</p>
        </div>
    </div>

    {{-- Bill To & Detail Product (2 Columns) --}}
    <div class="two-columns">
        {{-- Bill To --}}
        <div>
            <h3 class="section-title">Bill To:</h3>
            <div class="bill-to-content">
                <div>
                    <p class="customer-name">{{ $order->customer->customer_name }}</p>
                </div>
                <div>
                    <p class="customer-phone">{{ $order->customer->phone }}</p>
                </div>
                <div>
                    <p class="customer-address">{{ $order->customer->address }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Product --}}
        <div>
            <h3 class="section-title">Detail Product:</h3>
            <table class="detail-product-table">
                <tr>
                    <td>Product</td>
                    <td>:</td>
                    <td>{{ $order->productCategory->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Material</td>
                    <td>:</td>
                    <td>{{ $order->materialCategory->name ?? '-' }} - {{ $order->materialTexture->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Color</td>
                    <td>:</td>
                    <td>{{ $order->product_color ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Total QTY</td>
                    <td>:</td>
                    <td style="font-weight: 600;">{{ $order->orderItems->sum('qty') }} pcs</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Order Items Table --}}
    <div class="order-items">
        <h3 class="section-title">Order Items:</h3>

        @php
            // Group by design variant
            $groupedByDesign = $order->orderItems->groupBy('design_variant_id');
        @endphp

        @foreach($groupedByDesign as $designVariantId => $designItems)
            @php
                $designVariant = $designItems->first()->designVariant;
                // Group by sleeve within design
                $groupedBySleeve = $designItems->groupBy('material_sleeve_id');
            @endphp

            {{-- Design Variant Header --}}
            <div class="design-group">
                <div class="design-header">
                    <p>Design: {{ $designVariant->design_name ?? 'N/A' }}</p>
                </div>

                @foreach($groupedBySleeve as $sleeveId => $sleeveItems)
                    @php
                        $sleeve = $sleeveItems->first()->sleeve;
                        $basePrice = $sleeveItems->first()->unit_price - ($sleeveItems->first()->size->extra_price ?? 0);
                    @endphp

                    {{-- Sleeve Type Header --}}
                    <div class="sleeve-section">
                        <div class="sleeve-info">
                            <span class="label">Sleeve:</span>
                            <span class="value">
                                {{ $sleeve->name ?? 'N/A' }} (Base Price: Rp {{ number_format($basePrice, 0, ',', '.') }})
                            </span>
                        </div>

                        {{-- Items Table --}}
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Size</th>
                                    <th class="text-right" style="width: 110px;">Unit Price</th>
                                    <th class="text-center" style="width: 60px;">Qty</th>
                                    <th class="text-right" style="width: 120px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sleeveItems as $index => $item)
                                <tr>
                                    <td class="text-black">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="text-black">{{ $item->size->name ?? 'N/A' }}</span>
                                        @if(($item->size->extra_price ?? 0) > 0)
                                            <span class="extra-price">
                                                +Rp {{ number_format($item->size->extra_price, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right text-gray">
                                        Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center text-black">{{ $item->qty }}</td>
                                    <td class="text-right text-black">
                                        Rp {{ number_format($item->unit_price * $item->qty, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                                {{-- Subtotal per Sleeve --}}
                                <tr class="subtotal-row">
                                    <td colspan="3" class="text-right">Subtotal {{ $sleeve->name ?? '' }}:</td>
                                    <td class="text-center">{{ $sleeveItems->sum('qty') }}</td>
                                    <td class="text-right">
                                        Rp {{ number_format($sleeveItems->sum(fn($i) => $i->unit_price * $i->qty), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- Additional Services Section --}}
        @if($order->extraServices && $order->extraServices->count() > 0)
        <div class="additionals-section">
            <div class="design-header">
                <p>Additionals</p>
            </div>

            <div class="sleeve-section">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Service Name</th>
                            <th class="text-right" style="width: 130px;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->extraServices as $index => $service)
                        <tr>
                            <td class="text-black">{{ $index + 1 }}</td>
                            <td class="text-black">{{ $service->service->service_name ?? 'N/A' }}</td>
                            <td class="text-right text-gray">Rp {{ number_format($service->price, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        {{-- Subtotal Additional --}}
                        <tr class="subtotal-row">
                            <td colspan="2" class="text-right">Subtotal Additionals:</td>
                            <td class="text-right">
                                Rp {{ number_format($order->extraServices->sum('price'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Summary Section --}}
    <div class="summary-section">
        <div class="summary-wrapper">
            <div class="summary-table-container">
                <table class="summary-table">
                    {{-- Subtotal Items --}}
                    <tr>
                        <td>Subtotal Items</td>
                        <td>Rp {{ number_format($order->orderItems->sum('subtotal'), 0, ',', '.') }}</td>
                    </tr>
                    
                    {{-- Subtotal Additional --}}
                    @if($order->extraServices && $order->extraServices->count() > 0)
                    <tr>
                        <td>Subtotal Additionals</td>
                        <td>Rp {{ number_format($order->extraServices->sum('price'), 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    {{-- Discount --}}
                    @if(($order->discount ?? 0) > 0)
                    <tr>
                        <td>Discount</td>
                        <td>- Rp {{ number_format($order->discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    {{-- Total --}}
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td>Rp {{ number_format($order->invoice->total_bill, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Dibayar --}}
                    <tr>
                        <td>Dibayar</td>
                        <td>Rp {{ number_format($order->invoice->amount_paid, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Sisa --}}
                    <tr>
                        <td>Sisa</td>
                        <td>Rp {{ number_format($order->invoice->amount_due, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Status --}}
                    <tr class="status-row">
                        <td colspan="2">
                            @php
                                $invoiceStatus = $order->invoice->status;
                                $statusLabel = 'UNKNOWN';
                                $statusClass = 'status-pending';
                                
                                if ($invoiceStatus === 'unpaid') {
                                    $statusLabel = 'PENDING';
                                    $statusClass = 'status-pending';
                                } elseif ($invoiceStatus === 'dp') {
                                    $statusLabel = 'DP';
                                    $statusClass = 'status-dp';
                                } elseif ($invoiceStatus === 'paid') {
                                    $statusLabel = 'PAID';
                                    $statusClass = 'status-paid';
                                }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Payment History --}}
    @if($order->invoice->payments && $order->invoice->payments->count() > 0)
    <div class="payment-history">
        <h3 class="section-title">Payment History:</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th>Tipe</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->invoice->payments as $payment)
                <tr>
                    <td class="text-black">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                    <td class="text-gray">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td class="text-gray">{{ strtoupper($payment->payment_type) }}</td>
                    <td class="text-right text-gray">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notes --}}
    @if($order->invoice->notes)
    <div class="notes-section">
        <h3 class="notes-title">Notes:</h3>
        <p class="notes-content">{{ $order->invoice->notes }}</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p class="thank-you">Terima kasih atas kepercayaan Anda</p>
        <p class="company-name">STGR PRODUCTION</p>
    </div>
</body>
</html>
