@php
use Illuminate\Support\Facades\Storage;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Work Order - {{ $order->invoice->invoice_no }} - {{ $designVariant->design_name }}</title>
    <style>
        @page {
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: serif;
            font-size: 12pt;
        }
        .page {
            width: 210mm;
            height: 297mm;
            background: white;
            border: 4px solid black;
            page-break-after: always;
            position: relative;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .header-label {
            background: #92D050;
            border-bottom: 2px solid black;
            border-left: 2px solid black;
            padding: 6px 24px;
            font-size: 18pt;
            font-weight: bold;
            text-align: right;
        }
        .section-title {
            background: #FFFF00;
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            padding: 6px;
        }
        .footer-warning {
            background: #FF0000;
            border-top: 2px solid black;
            text-align: center;
            color: #FFFF00;
            font-weight: bold;
            font-size: 14pt;
            padding: 6px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td, table th {
            padding: 6px;
            border: 2px solid black;
        }
        .bg-blue { background: #00B0F0; }
        .bg-yellow { background: #FFFF00; }
        .bg-red { background: #FF0000; }
        .bg-green { background: #92D050; }
        .bg-cyan { background: #D0F0FF; }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>

{{-- PAGE 1 - CUTTING --}}
<div class="page">
    <div class="header-label">POTONG</div>
    
    {{-- Title Bar: MOCKUP DESIGN --}}
    <div class="section-title">MOCKUP DESIGN</div>
    
    {{-- Mockup Image --}}
    <div style="text-align: center; padding: 20px 0;">
        @if($workOrder->mockup_img_url)
            <img src="{{ Storage::disk('local')->path($workOrder->mockup_img_url) }}" alt="Mockup" style="max-height: 400px;">
        @else
            <img src="{{ public_path('images/work-order-null.png') }}" alt="Mockup" style="max-height: 400px;">
        @endif
    </div>

    {{-- 2 Column Layout using Table --}}
    <table style="margin-bottom: 10px; border: 0;">
        <tr>
            {{-- LEFT: Size Chart Custom --}}
            <td style="width: 50%; vertical-align: top; padding-right: 6px; border: 0;">
                <div style="background: #FFFF00; border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                    Size Chart Custom
                </div>
                <div style="text-align: center; padding: 10px; border: 2px solid black; border-top: 0; height: 200px;">
                    @if($workOrder->cutting && $workOrder->cutting->custom_size_chart_img_url)
                        <img src="{{ Storage::disk('local')->path($workOrder->cutting->custom_size_chart_img_url) }}" alt="Size Chart" style="max-height: 180px;">
                    @else
                        <img src="{{ public_path('images/work-order-null.png') }}" alt="Size Chart" style="max-height: 180px;">
                    @endif
                </div>
            </td>

            {{-- RIGHT: Material Info --}}
            <td style="width: 50%; vertical-align: top; padding-left: 6px; border: 0;">
                <div style="background: #FFFF00; border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                    Detail Product
                </div>
                <table style="font-size: 11pt;">
                    <tr>
                        <td class="bg-blue" style="font-weight: bold; width: 40%;">Product</td>
                        <td>{{ $order->productCategory->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-yellow" style="font-weight: bold;">Material</td>
                        <td>{{ $order->materialCategory->name ?? '-' }}{{ $order->materialTexture ? ' - ' . $order->materialTexture->name : '' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-blue" style="font-weight: bold;">Pola Potong</td>
                        <td>{{ $workOrder->cutting->cuttingPattern->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-yellow" style="font-weight: bold;">Kain Rantai</td>
                        <td>{{ $workOrder->cutting->chainCloth->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-blue" style="font-weight: bold;">Ukuran Rib</td>
                        <td>{{ $workOrder->cutting->ribSize->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="bg-yellow" style="font-weight: bold;">Notes</td>
                        <td style="font-size: 10pt;">{{ $workOrder->cutting->notes ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Order Items Table --}}
    <div style="margin-top: 10px;">
        <div style="background: #FFFF00; border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
            Detail Items
        </div>
        <table style="font-size: 10pt;">
            <thead>
                <tr style="color: #FFFF00;">
                    <th class="bg-blue">Size</th>
                    @foreach($displaySleeves as $sleeve)
                        <th class="bg-red">{{ $sleeve }}</th>
                    @endforeach
                    <th class="bg-blue">Total</th>
                </tr>
            </thead>
            <tbody style="text-align: center; font-size: 9pt;">
                @foreach($displaySizes as $size)
                <tr>
                    <td style="font-weight: bold;">{{ $size }}</td>
                    @foreach($displaySleeves as $sleeve)
                        <td>
                            @php
                                $item = $orderItemsData->first(function($i) use ($size, $sleeve) {
                                    return $i['size_name'] === $size && $i['sleeve_name'] === $sleeve;
                                });
                            @endphp
                            {{ ($item && $item['qty']) ? $item['qty'] : '' }}
                        </td>
                    @endforeach
                    <td class="bg-cyan" style="font-weight: bold;">
                        @php
                            $total = $orderItemsData->filter(function($i) use ($size) {
                                return $i['size_name'] === $size;
                            })->sum('qty');
                        @endphp
                        {{ $total ? $total : '' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-cyan" style="font-weight: bold;">
                    <td colspan="{{ count($displaySleeves) + 1 }}" style="text-align: center;">Total:</td>
                    <td class="bg-blue" style="text-align: center; color: #FFFF00;">{{ $orderItemsData->sum('qty') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
</div>

{{-- PAGE 2: SABLON (PRINTING) --}}
<div class="page">
    <div class="header-label">SCREEN - PROOFING - SABLON - PRESS</div>

    {{-- Print Ink & Finishing Info --}}
    <table style="margin-bottom: 10px; border: 0;">
        <tr>
            <td style="width: 50%; padding-right: 6px; border: 0;">
                <table style="font-size: 12pt;">
                    <tr>
                        <td class="bg-blue" style="font-weight: bold; width: 40%;">Print Ink</td>
                        <td>{{ $workOrder->printing->printInk->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; padding-left: 6px; border: 0;">
                <table style="font-size: 12pt;">
                    <tr>
                        <td class="bg-yellow" style="font-weight: bold; width: 40%;">Finishing</td>
                        <td>{{ $workOrder->printing->finishing->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">DETAIL & UKURAN SABLON</div>
    
    <div style="text-align: center; padding: 20px 0;">
        @if($workOrder->printing && $workOrder->printing->detail_img_url)
            <img src="{{ Storage::disk('local')->path($workOrder->printing->detail_img_url) }}" alt="Printing Detail" style="height: 900px;">
        @else
            <img src="{{ public_path('images/work-order-null.png') }}" alt="Printing Detail" style="height: 600px;">
        @endif
    </div>

    <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
</div>

{{-- PAGE 3: JARAK & POSISI SABLON --}}
<div class="page">
    <div class="header-label">SCREEN - PROOFING - SABLON - PRESS</div>

    <div class="section-title">JARAK & POSISI SABLON</div>
    
    <div style="text-align: center; padding: 20px 0;">
        @if($workOrder->printingPlacement && $workOrder->printingPlacement->detail_img_url)
            <img src="{{ Storage::disk('local')->path($workOrder->printingPlacement->detail_img_url) }}" alt="Placement Detail" style="max-height: 800px;">
        @else
            <img src="{{ public_path('images/work-order-null.png') }}" alt="Placement Detail" style="max-height: 600px;">
        @endif
    </div>

    {{-- Notes Section --}}
    <div style="margin: 20px 40px;">
        <div style="background: #FFFF00; border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
            Notes:
        </div>
        <div style="padding: 10px; background: white; height: 80px; border: 2px solid black; border-top: 0; font-size: 10pt;">
            {{ $workOrder->printingPlacement->notes ?? '-' }}
        </div>
    </div>

    <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
</div>

{{-- PAGE 4: JAHIT & PACKING (Two Separate Pages) --}}

{{-- PAGE 4A: JAHIT --}}
<div class="page">
    <div class="header-label">JAHIT</div>

    <div style="padding: 20px;">
        <table style="margin-bottom: 10px; border: 0;">
            <tr>
                {{-- LEFT: Posisi Jahit Label --}}
                <td style="width: 50%; vertical-align: top; padding-right: 6px; border: 0;">
                    <div class="bg-green" style="border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                        Posisi Jahit Label
                    </div>
                    <div style="text-align: center; padding: 10px; border: 2px solid black; border-top: 0; height: 200px;">
                        @if($workOrder->sewing && $workOrder->sewing->detail_img_url)
                            <img src="{{ Storage::disk('local')->path($workOrder->sewing->detail_img_url) }}" alt="Sewing Detail" style="max-height: 180px;">
                        @else
                            <img src="{{ public_path('images/work-order-null.png') }}" alt="Sewing Detail" style="max-height: 180px;">
                        @endif
                    </div>
                </td>

                {{-- RIGHT: Info Table --}}
                <td style="width: 50%; vertical-align: top; padding-left: 6px; border: 0;">
                    <div class="bg-green" style="border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                        Detail Jahit
                    </div>
                    <table style="font-size: 11pt;">
                        <tr>
                            <td class="bg-blue" style="font-weight: bold; width: 50%;">Overdek leher</td>
                            <td>{{ $workOrder->sewing->neckOverdeck->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="bg-yellow" style="font-weight: bold;">Overdek bawah & lengan</td>
                            <td>{{ $workOrder->sewing->underarmOverdeck->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="bg-blue" style="font-weight: bold;">Belah samping</td>
                            <td>{{ $workOrder->sewing->sideSplit->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="bg-yellow" style="font-weight: bold;">Label Jahit</td>
                            <td>{{ $workOrder->sewing->sewingLabel->name ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Notes Section --}}
        <div style="margin-top: 10px;">
            <div style="background: #FFFF00; border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                Notes:
            </div>
            <div style="padding: 10px; background: white; height: 60px; border: 2px solid black; border-top: 0; font-size: 10pt;">
                {{ $workOrder->sewing->notes ?? '-' }}
            </div>
        </div>
    </div>

    <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
</div>

{{-- PAGE 4B: PACKING --}}
<div class="page">
    <div class="header-label">PACKING</div>

    <div style="padding: 20px;">
        <table style="margin-bottom: 10px; border: 0;">
            <tr>
                {{-- LEFT: Hangtag --}}
                <td style="width: 50%; vertical-align: top; padding-right: 6px; border: 0;">
                    <div class="bg-green" style="border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                        Hangtag
                    </div>
                    <div style="text-align: center; padding: 10px; border: 2px solid black; border-top: 0; height: 200px;">
                        @if($workOrder->packing && $workOrder->packing->hangtag_img_url)
                            <img src="{{ Storage::disk('local')->path($workOrder->packing->hangtag_img_url) }}" alt="Hangtag" style="max-height: 180px;">
                        @else
                            <img src="{{ public_path('images/work-order-null.png') }}" alt="Hangtag" style="max-height: 180px;">
                        @endif
                    </div>
                </td>

                {{-- RIGHT: Info Table --}}
                <td style="width: 50%; vertical-align: top; padding-left: 6px; border: 0;">
                    <div class="bg-green" style="border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                        Detail Packing
                    </div>
                    <table style="font-size: 11pt;">
                        <tr>
                            <td class="bg-blue" style="font-weight: bold; width: 50%;">Plastik Packing</td>
                            <td>{{ $workOrder->packing->plasticPacking->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="bg-yellow" style="font-weight: bold;">Sticker</td>
                            <td>{{ $workOrder->packing->sticker->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="bg-blue" style="font-weight: bold;">Shipping Type</td>
                            <td>{{ $order->shipping_type ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Notes Section --}}
        <div style="margin-top: 10px;">
            <div style="background: #FFFF00; border: 2px solid black; padding: 6px; text-align: center; font-weight: bold;">
                Notes:
            </div>
            <div style="padding: 10px; background: white; height: 60px; border: 2px solid black; border-top: 0; font-size: 10pt;">
                {{ $workOrder->packing->notes ?? '-' }}
            </div>
        </div>
    </div>

    <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
</div>

</body>
</html>
