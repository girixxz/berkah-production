{{-- SHOW MODAL - Work Order Print Preview (A4 Format) --}}
<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
    @keydown.escape.window="showModal && closeShowModal()" aria-labelledby="modal-title" role="dialog" aria-modal="true">

    {{-- Background Overlay --}}
    <div x-show="showModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeShowModal()">
    </div>

    {{-- Modal Panel --}}
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div x-show="showModal"
            class="relative w-full max-w-5xl bg-white rounded-lg shadow-xl transform transition-all flex flex-col"
            style="max-height: 95vh;" @click.away="closeShowModal()">

            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-md md:text-lg font-semibold text-gray-900">
                            Work Order Preview
                        </h3>
                    </div>
                    <button @click="closeShowModal()" type="button"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-gray-500">
                    <span>Variant </span><span x-text="showData?.variant_index || '1'"></span>
                    <span class="italic" x-show="showData?.design_name"> ( </span><span class="italic"
                        x-text="showData?.design_name || ''"></span><span class="italic" x-show="showData?.design_name">
                        )</span>
                </p>
            </div>

            {{-- Modal Content - A4 Print Preview --}}
            <div class="p-6 bg-gray-100 flex-1 overflow-y-auto">

                {{-- PAGE 1 - CUTTING --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- Header: POTONG (Right Corner) --}}
                    <div class="flex justify-end mb-3">
                        <div class="bg-[#92D050] border-b-2 border-l-2 border-black px-6 py-1.5">
                            <span class="text-lg font-bold text-black">POTONG</span>
                        </div>
                    </div>

                    {{-- Page Content with proper padding --}}
                    <div class="p-4 h-full flex flex-col text-xs overflow-y-auto">

                        {{-- Title Bar: MOCKUP DESAIN --}}
                        <div class="">
                            <h2 class="bg-[#FFFF00] text-center text-sm font-bold text-black py-1.5">
                                MOCKUP DESIGN
                            </h2>

                            {{-- Mockup Image --}}
                            <div class="flex justify-center px-2 py-6">
                                <img :src="showData?.work_order?.mockup_img_url || '/images/work-order-null.png'"
                                    alt="Mockup" class="">
                            </div>
                        </div>

                        {{-- 2 Column Layout --}}
                        <div class="grid grid-cols-2 gap-3 mb-3">

                            {{-- LEFT: Size Chart Custom --}}
                            <div class="border-2 border-black">
                                <div class="bg-[#FFFF00] border-b-2 border-black p-1.5">
                                    <h3 class="font-bold text-xs text-black text-center">Size Chart Custom</h3>
                                </div>
                                <div class="p-2 bg-white flex justify-center items-center" style="height: 140px;">
                                    <img :src="showData?.work_order?.cutting?.custom_size_chart_img_url ||
                                        '/images/work-order-null.png'"
                                        alt="Size Chart" class="max-h-18 w-auto object-contain">
                                </div>
                            </div>

                            {{-- RIGHT: Material Info --}}
                            <div class="">
                                <div class="bg-[#FFFF00] border-2 border-black p-1.5">
                                    <h3 class="font-bold text-xs text-black text-center">Detail Product</h3>
                                </div>
                                <div class="bg-white">
                                    <table class="w-full text-xs">
                                        <tr>
                                            <td class="bg-[#00B0F0] border-x-2 border-b-2 p-1.5 font-semibold w-2/5">
                                                Product
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5">
                                                <span x-text="showData?.product_category || 'DATA KOSONG'"></span>
                                                <span class="text-red-600 text-[8px]"
                                                    x-show="!showData?.product_category">
                                                    (Debug: <span
                                                        x-text="JSON.stringify(showData?.product_category)"></span>)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#FFFF00] border-x-2 border-b-2 p-1.5 font-semibold">Material
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5">
                                                <span x-text="showData?.material_category || '-'"></span>
                                                <span x-show="showData?.material_texture"> - </span>
                                                <span x-text="showData?.material_texture || ''"></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#00B0F0]  p-1.5 font-semibold border-x-2 border-b-2">Pola
                                                Potong
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.cutting_pattern_name || '-'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#FFFF00]  p-1.5 font-semibold border-x-2 border-b-2">Kain
                                                Rantai
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.chain_cloth_name || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#00B0F0]  p-1.5 font-semibold border-x-2 border-b-2">Ukuran
                                                Rib
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.rib_size_name || '-'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#FFFF00]  p-1.5 font-semibold border-x-2 border-b-2">Notes
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.notes || '-'">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Order Items Table (Group by Size, show sleeve types) --}}
                        <div class="mb-3 flex-grow">
                            <div class="bg-[#FFFF00] border-2 border-black p-1.5">
                                <h3 class="font-bold text-xs text-black text-center">Detail Items
                                </h3>
                            </div>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-[#FFFF00]">
                                        <th class="border-x border-b border-black p-1.5 font-bold bg-[#00B0F0] ">
                                            Size
                                        </th>
                                        <template x-if="showData && showData.sleeves">
                                            <template x-for="sleeve in showData.sleeves" :key="sleeve">
                                                <th class="border-x border-b border-black p-1.5 font-bold bg-[#FF0000]"
                                                    x-text="sleeve"></th>
                                            </template>
                                        </template>
                                        <th class="border-x border-b border-black p-1.5 font-bold bg-[#00B0F0]">Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="text-center bg-white">
                                    <template x-if="showData && showData.sizes">
                                        <template x-for="size in showData.sizes" :key="size">
                                            <tr>
                                                <td class="border-x border-b border-black p-1.5 font-semibold"
                                                    x-text="size"></td>
                                                <template x-if="showData.sleeves">
                                                    <template x-for="sleeve in showData.sleeves"
                                                        :key="sleeve">
                                                        <td class="border-x border-b border-black p-1.5"
                                                            x-text="(() => {
                                                                const item = showData.order_items?.find(i => i.size_name === size && i.sleeve_name === sleeve);
                                                                return item && item.qty ? item.qty : '';
                                                            })()">
                                                        </td>
                                                    </template>
                                                </template>
                                                <td class="border-x border-b border-black p-1.5 font-bold bg-cyan-50"
                                                    x-text="(() => {
                                                        const total = showData.order_items?.filter(i => i.size_name === size)
                                                            .reduce((sum, item) => sum + (item.qty || 0), 0);
                                                        return total || '';
                                                    })()">
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                    <template
                                        x-if="!showData || !showData.order_items || showData.order_items.length === 0">
                                        <tr>
                                            <td colspan="6"
                                                class="border border-black p-2 text-center text-gray-500">
                                                Belum ada data order items
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-cyan-200 font-bold">
                                        <td class="border-x border-b border-black p-1.5 text-center"
                                            :colspan="(showData?.sleeves?.length || 0) + 1">
                                            Total:
                                        </td>
                                        <td class="border-x border-b border-black p-1.5 text-center bg-[#00B0F0] text-[#FFFF00]"
                                            x-text="showData?.order_items?.reduce((sum, item) => sum + (item.qty || 0), 0) || ''">
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Footer Warning --}}
                        <div class="bg-[#FF0000] border-2 border-black mt-auto">
                            <p class="text-center text-[#FFFF00] font-bold text-xs py-1.5 uppercase">
                                BACA DAN PAHAMI SEBELUM BEKERJA
                            </p>
                        </div>

                    </div>
                </div>

                {{-- PAGE 2: SABLON (PRINTING) - A4 Size --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black mt-6"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- Header: SCREEN, PROOFING, SABLON, & PRESS (Right Corner) --}}
                    <div class="flex justify-end mb-3">
                        <div class="bg-[#92D050] border-b-2 border-l-2 border-black px-6 py-1.5">
                            <span class="text-lg font-bold text-black">SCREEN - PROOFING - SABLON - PRESS</span>
                        </div>
                    </div>

                    {{-- Page Content --}}
                    <div class="p-4 h-full flex flex-col text-xs">

                        {{-- Print Ink & Finishing Info --}}
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="border-2 border-black">
                                <div class="bg-[#00B0F0] border-b-2 border-black p-1.5">
                                    <span class="font-bold text-xs">Print Ink</span>
                                </div>
                                <div class="p-2 bg-white">
                                    <span x-text="showData?.work_order?.printing?.print_ink_name || '-'"></span>
                                </div>
                            </div>
                            <div class="border-2 border-black">
                                <div class="bg-[#FFFF00] border-b-2 border-black p-1.5">
                                    <span class="font-bold text-xs">Finishing</span>
                                </div>
                                <div class="p-2 bg-white">
                                    <span x-text="showData?.work_order?.printing?.finishing_name || '-'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Detail & Ukuran Sablon Section --}}
                        <div class="flex-1">
                            <h2 class="bg-[#FFFF00] text-center text-sm font-bold text-black py-1.5">
                                DETAIL & UKURAN SABLON
                            </h2>
                            <div class="p-3 flex justify-center items-center h-full">
                                <img :src="showData?.work_order?.printing?.detail_img_url || '/images/work-order-null.png'"
                                    alt="Detail Sablon" class="max-h-220 object-contain">
                            </div>
                        </div>

                        {{-- Footer Warning --}}
                        <div class="bg-[#FF0000] border-2 border-black mt-auto">
                            <p class="text-center text-[#FFFF00] font-bold text-xs py-1.5 uppercase">
                                BACA DAN PAHAMI SEBELUM BEKERJA
                            </p>
                        </div>

                    </div>
                </div>

                {{-- PAGE 3: JARAK & POSISI SABLON - A4 Size --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black mt-6"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- Header: SCREEN, PROOFING, SABLON, & PRESS (Right Corner) --}}
                    <div class="flex justify-end mb-3">
                        <div class="bg-[#92D050] border-b-2 border-l-2 border-black px-6 py-1.5">
                            <span class="text-lg font-bold text-black">SCREEN - PROOFING - SABLON - PRESS</span>
                        </div>
                    </div>

                    {{-- Page Content --}}
                    <div class="p-4 h-full flex flex-col text-xs">

                        {{-- Detail Image Section --}}
                        <div class="mb-3 flex-1">
                            <h2 class="bg-[#FFFF00] text-center text-sm font-bold text-black py-1.5">
                                JARAK & POSISI SABLON
                            </h2>
                            <div class="p-3 flex justify-center items-center h-full">
                                <img :src="showData?.work_order?.printing_placement?.detail_img_url ||
                                    '/images/work-order-null.png'"
                                    alt="Posisi Sablon" class="max-h-220 object-contain">
                            </div>
                        </div>

                        {{-- Notes Section --}}
                        <div class="border-2 border-black mb-3">
                            <div class="bg-[#FFFF00] border-b-2 border-black p-1.5 text-center">
                                <span class="font-bold text-xs ">Notes:</span>
                            </div>
                            <div class="p-2 bg-white min-h-[60px]">
                                <p class="text-xs" x-text="showData?.work_order?.printing_placement?.notes || '-'">
                                </p>
                            </div>
                        </div>

                        {{-- Footer Warning --}}
                        <div class="bg-[#FF0000] border-2 border-black mt-auto">
                            <p class="text-center text-[#FFFF00] font-bold text-xs py-1.5 uppercase">
                                BACA DAN PAHAMI SEBELUM BEKERJA
                            </p>
                        </div>

                    </div>
                </div>

                {{-- PAGE 4: JAHIT & PACKING (Split A4) - A4 Size --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black mt-6"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- TOP HALF: JAHIT (Presisi 50%) --}}
                    <div class="flex flex-col border-b-2 border-black" style="height: 50%;">

                        {{-- Header: JAHIT (Nempel pojok kanan atas) --}}
                        <div class="flex justify-end flex-shrink-0">
                            <div class="bg-[#92D050] border-b-2 border-l-2 border-black px-6 py-1.5">
                                <span class="text-base font-bold text-black">JAHIT</span>
                            </div>
                        </div>

                        <div class="flex-1 p-3 flex flex-col">
                            {{-- Content Grid: Posisi Label + Info Table --}}
                            <div class="grid grid-cols-2 gap-2 mb-2" style="flex: 0 0 auto;">

                                {{-- LEFT: Posisi Jahit Label --}}
                                <div class="border-2 border-black">
                                    <div class="bg-[#92D050] border-b-2 border-black p-1 text-center">
                                        <span class="font-bold text-[10px]">Posisi Jahit Label</span>
                                    </div>
                                    <div class="p-2 bg-white flex justify-center items-center" style="height: 120px;">
                                        <img :src="showData?.work_order?.sewing?.detail_img_url || '/images/work-order-null.png'"
                                            alt="Posisi Label" class="max-h-full w-auto object-contain">
                                    </div>
                                </div>

                                {{-- RIGHT: Info Table --}}
                                <div class="">
                                    <div class="bg-[#92D050] border-2 border-black p-1">
                                        <h3 class="font-bold text-[10px] text-black text-center">Detail Jahit</h3>
                                    </div>
                                    <div class="bg-white">
                                        <table class="w-full text-[10px]">
                                            <tr>
                                                <td
                                                    class="bg-[#00B0F0] border-x-2 border-b-2 border-black p-1 font-semibold w-3/5">
                                                    Overdek leher
                                                </td>
                                                <td class="border-x-2 border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.neck_overdeck_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#FFFF00] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Overdek bawah & lengan
                                                </td>
                                                <td class="border-x-2 border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.underarm_overdeck_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#00B0F0] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Belah samping
                                                </td>
                                                <td class="border-x-2 border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.side_split_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#FFFF00] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Label Jahit
                                                </td>
                                                <td class="border-x-2 border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.sewing_label_name || '-'"></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Keterangan Tambahan (Flexible) --}}
                            <div class="border-2 border-black mb-2 flex-1">
                                <div class="bg-[#FFFF00] border-b-2 border-black p-1 text-center">
                                    <span class="font-bold text-[10px]">Keterangan Tambahan</span>
                                </div>
                                <div class="p-1 bg-gray-100 h-full">
                                    <p class="text-[10px]" x-text="showData?.work_order?.sewing?.notes || ''"></p>
                                </div>
                            </div>

                            {{-- Footer Warning --}}
                            <div class="bg-[#FF0000] border-2 border-black flex-shrink-0">
                                <p class="text-center text-[#FFFF00] font-bold text-[10px] py-1 uppercase">
                                    BACA DAN PAHAMI SEBELUM BEKERJA
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- BOTTOM HALF: PACKING (Presisi 50%) --}}
                    <div class="flex flex-col" style="height: 50%;">

                        {{-- Header: PACKING (Nempel) --}}
                        <div class="flex justify-end flex-shrink-0">
                            <div class="bg-[#92D050] border-b-2 border-l-2 border-black px-6 py-1.5">
                                <span class="text-base font-bold text-black">PACKING</span>
                            </div>
                        </div>

                        <div class="flex-1 p-3 flex flex-col">

                            {{-- Grid Layout: 2x2 Packing Details --}}
                            <div class="grid grid-cols-2 gap-2 mb-2" style="flex: 0 0 auto;">

                                {{-- TOP LEFT: Plastik Packing --}}
                                <div class="border-2 border-black">
                                    <div class="bg-[#00B0F0] border-b-2 border-black p-1 text-center">
                                        <span class="font-bold text-[10px]">Plastik Packing</span>
                                    </div>
                                    <div class="p-1 bg-white" style="height: 60px;">
                                        <p class="text-[10px]"
                                            x-text="showData?.work_order?.packing?.plastic_packing_name || '-'"></p>
                                    </div>
                                </div>

                                {{-- TOP RIGHT: Hangtag --}}
                                <div class="border-2 border-black">
                                    <div class="bg-[#00B0F0] border-b-2 border-black p-1 text-center">
                                        <span class="font-bold text-[10px]">Hangtag</span>
                                    </div>
                                    <div class="p-1 bg-white flex justify-center items-center" style="height: 60px;">
                                        <img :src="showData?.work_order?.packing?.hangtag_img_url || '/images/work-order-null.png'"
                                            alt="Hangtag" class="max-h-full w-auto object-contain">
                                    </div>
                                </div>

                                {{-- BOTTOM LEFT: Stiker --}}
                                <div class="border-2 border-black">
                                    <div class="bg-[#FFFF00] border-b-2 border-black p-1 text-center">
                                        <span class="font-bold text-[10px]">Stiker</span>
                                    </div>
                                    <div class="p-1 bg-white" style="height: 60px;">
                                        <p class="text-[10px]"
                                            x-text="showData?.work_order?.packing?.sticker_name || '-'"></p>
                                    </div>
                                </div>

                                {{-- BOTTOM RIGHT: Ekspedisi --}}
                                <div class="border-2 border-black">
                                    <div class="bg-[#FFFF00] border-b-2 border-black p-1 text-center">
                                        <span class="font-bold text-[10px]">Ekspedisi</span>
                                    </div>
                                    <div class="p-1 bg-white" style="height: 60px;">
                                        <p class="text-[10px]">Diambil</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Keterangan Tambahan (Flexible) --}}
                            <div class="border-2 border-black mb-2 flex-1">
                                <div class="bg-[#FFFF00] border-b-2 border-black p-1 text-center">
                                    <span class="font-bold text-[10px]">Keterangan Tambahan</span>
                                </div>
                                <div class="p-1 bg-gray-100 h-full">
                                    <p class="text-[10px]" x-text="showData?.work_order?.packing?.notes || ''"></p>
                                </div>
                            </div>

                            {{-- Footer Warning --}}
                            <div class="bg-[#FF0000] border-2 border-black flex-shrink-0">
                                <p class="text-center text-[#FFFF00] font-bold text-[10px] py-1 uppercase">
                                    BACA DAN PAHAMI SEBELUM BEKERJA
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end items-center gap-3">
                {{-- Download PDF Button --}}
                <button @click="alert('Download PDF feature coming soon!')" type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    {{-- Icon --}}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    {{-- Text (Hidden on mobile, shown on md+) --}}
                    <span class="hidden md:inline ml-2">Download PDF</span>
                </button>

                {{-- Close Button --}}
                <button @click="closeShowModal()" type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
