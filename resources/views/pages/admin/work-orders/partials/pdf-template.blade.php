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
            size: A4 portrait;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 210mm;
            height: 297mm;
            background: white;
            border: 4px solid black;
            overflow: hidden;
            position: relative;
            page-break-inside: avoid;
            page-break-after: avoid;
        }
        .header-label {
            background: #92D050;
            border-bottom: 2px solid black;
            border-left: 2px solid black;
            padding: 4px 20px;
            font-size: 16pt;
            font-weight: bold;
            display: inline-block;
        }
        .section-title {
            background: #FFFF00;
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            padding: 5px;
        }
        .footer-warning {
            background: #FF0000;
            text-align: center;
            color: #FFFF00;
            font-weight: bold;
            font-size: 13pt;
            padding: 5px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td, table th {
            padding: 4px;
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
    {{-- Header --}}
    <table style="width: 100%; border: 0; margin: 0;">
        <tr>
            <td style="border: 0; width: 70%;"></td>
            <td style="border: 0; width: 30%; text-align: right; padding: 0;">
                <div class="header-label">POTONG</div>
            </td>
        </tr>
    </table>
    
    {{-- Title --}}
    <div class="section-title">MOCKUP DESIGN</div>
    
    {{-- Mockup Image (Single) --}}
    <div style="text-align: center; padding: 10px 0;">
        @if($workOrder->mockup_img_url)
            <img src="{{ Storage::disk('local')->path($workOrder->mockup_img_url) }}" alt="Mockup" style="min-height: 200px; width: auto; max-width: 90%;">
        @else
            <img src="{{ public_path('images/work-order-null.png') }}" alt="Mockup" style="min-height: 200px; width: auto; max-width: 90%;">
        @endif
    </div>

    {{-- 2 Column Layout --}}
    <table style="border: 0; margin: 5px 0;">
        <tr>
            {{-- LEFT: Size Chart Custom --}}
            <td style="width: 50%; vertical-align: top; padding-right: 5px; border: 0;">
                <div style="background: #FFFF00; border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                    Size Chart Custom
                </div>
                <div style="text-align: center; padding: 8px; border: 2px solid black; border-top: 0; height: 160px; overflow: hidden;">
                    @if($workOrder->cutting && $workOrder->cutting->custom_size_chart_img_url)
                        <img src="{{ Storage::disk('local')->path($workOrder->cutting->custom_size_chart_img_url) }}" alt="Size Chart" style="max-height: 140px; max-width: 100%; width: auto; height: auto; object-fit: contain;">
                    @else
                        <img src="{{ public_path('images/work-order-null.png') }}" alt="Size Chart" style="max-height: 140px; max-width: 100%; width: auto; height: auto; object-fit: contain;">
                    @endif
                </div>
            </td>

            {{-- RIGHT: Detail Product --}}
            <td style="width: 50%; vertical-align: top; padding-left: 5px; border: 0;">
                <div style="background: #FFFF00; border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                    Detail Product
                </div>
                <table style="font-size: 9pt;">
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
                        <td style="font-size: 8pt;">{{ $workOrder->cutting->notes ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Detail Items Table --}}
    <div style="margin-top: 5px;">
        <div style="background: #FFFF00; border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
            Detail Items
        </div>
        <table style="font-size: 9pt;">
            <thead>
                <tr style="color: #FFFF00;">
                    <th class="bg-blue">Size</th>
                    @foreach($displaySleeves as $sleeve)
                        <th class="bg-red">{{ $sleeve }}</th>
                    @endforeach
                    <th class="bg-blue">Total</th>
                </tr>
            </thead>
            <tbody style="text-align: center; font-size: 8pt;">
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

    {{-- Footer --}}
    <div style="position: absolute; bottom: 0; left: 0; right: 0;">
        <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
    </div>
</div>

{{-- PAGE 2: SABLON (PRINTING) --}}
<div class="page">
    {{-- Header --}}
    <table style="width: 100%; border: 0; margin: 0;">
        <tr>
            <td style="border: 0; width: 40%;"></td>
            <td style="border: 0; width: 60%; text-align: right; padding: 0;">
                <div class="header-label">SCREEN - PROOFING - SABLON - PRESS</div>
            </td>
        </tr>
    </table>

    {{-- Print Ink & Finishing Info --}}
    <table style="border: 0; margin: 5px 0;">
        <tr>
            <td style="width: 50%; padding-right: 5px; border: 0;">
                <table style="font-size: 10pt;">
                    <tr>
                        <td class="bg-blue" style="font-weight: bold; width: 35%;">Print Ink</td>
                        <td>{{ $workOrder->printing->printInk->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; padding-left: 5px; border: 0;">
                <table style="font-size: 10pt;">
                    <tr>
                        <td class="bg-yellow" style="font-weight: bold; width: 35%;">Finishing</td>
                        <td>{{ $workOrder->printing->finishing->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">DETAIL & UKURAN SABLON</div>
    
    {{-- Printing Image --}}
    <div style="text-align: center; padding: 10px;">
        @if($workOrder->printing && $workOrder->printing->detail_img_url)
            <img src="{{ Storage::disk('local')->path($workOrder->printing->detail_img_url) }}" alt="Printing Detail" style="max-height: 900px; max-width: 90%; width: auto; height: auto; object-fit: contain;">
        @else
            <img src="{{ public_path('images/work-order-null.png') }}" alt="Printing Detail" style="max-height: 600px; max-width: 90%; width: auto; height: auto; object-fit: contain;">
        @endif
    </div>

    {{-- Footer --}}
    <div style="position: absolute; bottom: 0; left: 0; right: 0;">
        <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
    </div>
</div>

{{-- PAGE 3: JARAK & POSISI SABLON --}}
<div class="page">
    {{-- Header --}}
    <table style="width: 100%; border: 0; margin: 0;">
        <tr>
            <td style="border: 0; width: 40%;"></td>
            <td style="border: 0; width: 60%; text-align: right; padding: 0;">
                <div class="header-label">SCREEN - PROOFING - SABLON - PRESS</div>
            </td>
        </tr>
    </table>

    <div class="section-title">JARAK & POSISI SABLON</div>
    
    <div style="text-align: center; padding: 10px 15px;">
        @if($workOrder->printingPlacement && $workOrder->printingPlacement->detail_img_url)
            <img src="{{ Storage::disk('local')->path($workOrder->printingPlacement->detail_img_url) }}" alt="Placement Detail" style="max-height: 850px; max-width: 90%; width: auto; height: auto; object-fit: contain;">
        @else
            <img src="{{ public_path('images/work-order-null.png') }}" alt="Placement Detail" style="max-height: 720px; max-width: 90%; width: auto; height: auto; object-fit: contain;">
        @endif
    </div>

    {{-- Notes Section --}}
    <div style="margin: 10px 15px;">
        <div style="background: #FFFF00; border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
            Notes:
        </div>
        <div style="padding: 8px; background: white; height: 60px; border: 2px solid black; border-top: 0; font-size: 9pt;">
            {{ $workOrder->printingPlacement->notes ?? '-' }}
        </div>
    </div>

    {{-- Footer --}}
    <div style="position: absolute; bottom: 0; left: 0; right: 0;">
        <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
    </div>
</div>

{{-- PAGE 4: JAHIT & PACKING (Single Page Split 50-50) --}}
<div class="page">
    {{-- TOP HALF: JAHIT (Fixed 50% Height) --}}
    <div style="height: 48%; border-bottom: 2px solid black;">
        <table style="width: 100%; border: 0; margin: 0;">
            <tr>
                <td style="border: 0; width: 75%;"></td>
                <td style="border: 0; width: 25%; text-align: right; padding: 0;">
                    <div class="header-label">JAHIT</div>
                </td>
            </tr>
        </table>

        <div style="padding: 8px 15px;">
            <table style="border: 0;">
                <tr>
                    {{-- LEFT: Posisi Jahit Label --}}
                    <td style="width: 50%; vertical-align: top; padding-right: 5px; border: 0;">
                        <div class="bg-green" style="border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                            Posisi Jahit Label
                        </div>
                        <div style="text-align: center; padding: 8px; border: 2px solid black; border-top: 0; height: 110px; overflow: hidden;">
                            @if($workOrder->sewing && $workOrder->sewing->detail_img_url)
                                <img src="{{ Storage::disk('local')->path($workOrder->sewing->detail_img_url) }}" alt="Sewing Detail" style="max-height: 90px; max-width: 100%; width: auto; height: auto; object-fit: contain;">
                            @else
                                <img src="{{ public_path('images/work-order-null.png') }}" alt="Sewing Detail" style="max-height: 90px; max-width: 100%; width: auto; height: auto; object-fit: contain;">
                            @endif
                        </div>
                    </td>

                    {{-- RIGHT: Detail Jahit --}}
                    <td style="width: 50%; vertical-align: top; padding-left: 5px; border: 0;">
                        <div class="bg-green" style="border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                            Detail Jahit
                        </div>
                        <table style="font-size: 9pt;">
                            <tr>
                                <td class="bg-blue" style="font-weight: bold; width: 55%;">Overdek leher</td>
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
            <div style="margin-top: 5px;">
                <div style="background: #FFFF00; border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                    Notes:
                </div>
                <div style="padding: 6px; background: white; height: 32px; border: 2px solid black; border-top: 0; font-size: 8pt; overflow: hidden;">
                    {{ $workOrder->sewing->notes ?? '-' }}
                </div>
            </div>
        </div>

        <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
    </div>

    {{-- BOTTOM HALF: PACKING (Fixed 50% Height) --}}
    <div style="height: 48%;">
        <table style="width: 100%; border: 0; margin: 0;">
            <tr>
                <td style="border: 0; width: 65%;"></td>
                <td style="border: 0; width: 35%; text-align: right; padding: 0;">
                    <div class="header-label">PACKING</div>
                </td>
            </tr>
        </table>

        <div style="padding: 8px 15px;">
            <table style="border: 0;">
                <tr>
                    {{-- LEFT: Hangtag --}}
                    <td style="width: 50%; vertical-align: top; padding-right: 5px; border: 0;">
                        <div class="bg-green" style="border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                            Hangtag
                        </div>
                        <div style="text-align: center; padding: 8px; border: 2px solid black; border-top: 0; height: 110px; overflow: hidden;">
                            @if($workOrder->packing && $workOrder->packing->hangtag_img_url)
                                <img src="{{ Storage::disk('local')->path($workOrder->packing->hangtag_img_url) }}" alt="Hangtag" style="max-height: 90px; max-width: 100%; width: auto; height: auto; object-fit: contain;">
                            @else
                                <img src="{{ public_path('images/work-order-null.png') }}" alt="Hangtag" style="max-height: 90px; max-width: 100%; width: auto; height: auto; object-fit: contain;">
                            @endif
                        </div>
                    </td>

                    {{-- RIGHT: Detail Packing --}}
                    <td style="width: 50%; vertical-align: top; padding-left: 5px; border: 0;">
                        <div class="bg-green" style="border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                            Detail Packing
                        </div>
                        <table style="font-size: 9pt;">
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
            <div style="margin-top: 5px;">
                <div style="background: #FFFF00; border: 2px solid black; padding: 4px; text-align: center; font-weight: bold; font-size: 10pt;">
                    Notes:
                </div>
                <div style="padding: 6px; background: white; height: 32px; border: 2px solid black; border-top: 0; font-size: 8pt; overflow: hidden;">
                    {{ $workOrder->packing->notes ?? '-' }}
                </div>
            </div>
        </div>

        {{-- Footer at Bottom --}}
        <div style="position: absolute; bottom: 0; left: 0; right: 0;">
            <div class="footer-warning">BACA DAN PAHAMI SEBELUM BEKERJA</div>
        </div>
    </div>
</div>

</body>
</html>
