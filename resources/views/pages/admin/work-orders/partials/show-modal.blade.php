{{-- SHOW MODAL - Work Order Print Preview (A4 Format) --}}
<div x-show="showModal" x-cloak class="fixed inset-0 z-50"
    @keydown.escape.window="showModal && closeShowModal()" aria-labelledby="modal-title" role="dialog" aria-modal="true">

    {{-- Background Overlay --}}
    <div x-show="showModal" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity" @click="closeShowModal()">
    </div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 flex items-center justify-center px-4 py-8" style="font-family: 'Times New Roman', Times, serif;">
        <div x-show="showModal"
            class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl transform transition-all flex flex-col"
            style="height: min(calc(100vh - 4rem), 95vh); min-height: 0;" @click.away="closeShowModal()">

            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-md md:text-lg font-semibold text-gray-900">
                            Work Order Preview
                        </h3>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Zoom Controls - Responsive --}}
                        <div class="flex items-center gap-1 mr-1 md:mr-3 bg-white rounded-lg border border-gray-300 px-1 md:px-2 py-1">
                            {{-- Zoom Out Button --}}
                            <button @click="zoomOut()" type="button"
                                class="p-1 rounded hover:bg-gray-100 transition-colors"
                                :disabled="zoomLevel <= 50"
                                :class="{ 'opacity-50 cursor-not-allowed': zoomLevel <= 50 }">
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                            
                            {{-- Percentage Display - Hidden on Mobile --}}
                            <span class="hidden md:inline text-xs font-medium text-gray-700 min-w-[45px] text-center" x-text="zoomLevel + '%'"></span>
                            
                            {{-- Zoom In Button --}}
                            <button @click="zoomIn()" type="button"
                                class="p-1 rounded hover:bg-gray-100 transition-colors"
                                :disabled="zoomLevel >= 200"
                                :class="{ 'opacity-50 cursor-not-allowed': zoomLevel >= 200 }">
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            
                            {{-- Reset Button - Icon only on mobile, text on desktop --}}
                            <button @click="resetZoom()" type="button"
                                class="ml-1 p-1 md:px-2 md:py-1 text-gray-600 hover:bg-gray-100 rounded transition-colors">
                                <svg class="w-4 h-4 md:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span class="hidden md:inline text-xs font-medium">Reset</span>
                            </button>
                        </div>
                        <button @click="closeShowModal()" type="button"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-500">
                    <span>Variant </span><span x-text="showData?.variant_index || '1'"></span>
                    <span class="italic" x-show="showData?.design_name"> ( </span><span class="italic"
                        x-text="showData?.design_name || ''"></span><span class="italic" x-show="showData?.design_name">
                        )</span>
                </p>
            </div>

            {{-- Modal Content - A4 Print Preview --}}
            <div class="p-6 bg-gray-100 flex-1 overflow-auto">
                <div class="inline-block min-w-full" :style="`transform: scale(${zoomLevel / 100}); transform-origin: top center; transition: transform 0.2s ease;`">

                {{-- PAGE 1 - CUTTING --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- Header: POTONG (Right Corner) --}}
                    <div class="flex justify-end">
                        <div class="bg-[#92D050] border-b-2 border-l-2 border-black px-6 py-1.5">
                            <span class="text-lg font-bold text-black">POTONG</span>
                        </div>
                    </div>

                    {{-- Page Content with proper padding --}}
                    <div class="h-full flex flex-col overflow-y-auto">

                        {{-- Title Bar: MOCKUP DESAIN --}}
                        <div class="">
                            <h2 class="bg-[#FFFF00] text-center text-lg font-bold text-black py-1.5">
                                MOCKUP DESIGN
                            </h2>

                            {{-- Mockup Image --}}
                            <div class="flex justify-center px-2 py-4">
                                <img :src="(() => {
                                    const workOrder = showData?.work_order;
                                    if (!workOrder || !workOrder.id || !workOrder.mockup_img_url) return '/images/work-order-null.png?v=' + Date.now();
                                    return '{{ route('work-orders.serve-mockup-image', ['workOrder' => '__ID__']) }}'.replace('__ID__', workOrder.id);
                                })()"
                                    alt="Mockup" class="max-h-92 w-auto object-contain">
                            </div>
                        </div>

                        {{-- 2 Column Layout --}}
                        <div class="grid grid-cols-2 gap-3 mb-3">

                            {{-- LEFT: Size Chart Custom --}}
                            <div class="border-r-2 border-y-2 border-black">
                                <div class="bg-[#FFFF00] border-b-2 border-black p-1.5">
                                    <h3 class="font-bold text-sm text-black text-center">Size Chart Custom</h3>
                                </div>
                                <div class="p-2 bg-white flex justify-center items-center mt-2" style="height: 186px;">
                                    <img :src="(() => {
                                        const cutting = showData?.work_order?.cutting;
                                        if (!cutting || !cutting.id || !cutting.custom_size_chart_img_url) return '/images/work-order-null.png?v=' + Date.now();
                                        return '{{ route('work-orders.serve-cutting-image', ['cutting' => '__ID__']) }}'.replace('__ID__', cutting.id);
                                    })()"
                                        alt="Size Chart" class="max-h-[186px] w-auto object-contain">
                                </div>
                            </div>

                            {{-- RIGHT: Material Info --}}
                            <div class="">
                                <div class="bg-[#FFFF00] border-y-2 border-l-2 border-black p-1.5">
                                    <h3 class="font-bold text-sm text-black text-center">Detail Product</h3>
                                </div>
                                <div class="bg-white">
                                    <table class="w-full text-sm">
                                        <tr>
                                            <td class="bg-[#00B0F0] border-x-2 border-b-2 p-1.5 font-semibold w-2/7">
                                                Product
                                            </td>
                                            <td class="border-b-2 p-1.5">
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
                                            <td class="border-b-2 p-1.5">
                                                <span x-text="showData?.material_category || '-'"></span>
                                                <span x-show="showData?.material_texture"> - </span>
                                                <span x-text="showData?.material_texture || ''"></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#00B0F0] border-x-2 border-b-2 p-1.5 font-semibold">Warna Kain
                                            </td>
                                            <td class="border-b-2 p-1.5"
                                                x-text="showData?.color || '-'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#FFFF00]  p-1.5 font-semibold border-x-2 border-b-2">Pola
                                                Potong
                                            </td>
                                            <td class="border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.cutting_pattern_name || '-'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#00B0F0]  p-1.5 font-semibold border-x-2 border-b-2">Kain
                                                Rantai
                                            </td>
                                            <td class="border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.chain_cloth_name || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#FFFF00]  p-1.5 font-semibold border-x-2 border-b-2">Ukuran
                                                Rib
                                            </td>
                                            <td class="border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.rib_size_name || '-'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-[#00B0F0]  p-1.5 font-semibold border-x-2 border-b-2">Notes
                                            </td>
                                            <td class="border-b-2 p-1.5 text-xs"
                                                x-text="showData?.work_order?.cutting?.notes || '-'">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Order Items Table (Group by Size, show sleeve types) --}}
                        <div class="mb-3 flex-grow">
                            <div class="bg-[#FFFF00] border-y-2 border-black p-1.5">
                                <h3 class="font-bold text-sm text-black text-center">Detail Items
                                </h3>
                            </div>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-[#FFFF00]">
                                        <th class="border-r-2 border-b-2 border-black p-1.5 font-bold bg-[#00B0F0] ">
                                            Size
                                        </th>
                                        <template x-if="showData && showData.sleeves">
                                            <template x-for="sleeve in showData.sleeves" :key="sleeve">
                                                <th class="border-r-2 border-b-2 border-black p-1.5 font-bold bg-[#FF0000]"
                                                    x-text="sleeve"></th>
                                            </template>
                                        </template>
                                        <th class="border-b-2 border-black p-1.5 font-bold bg-[#00B0F0]">Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="text-center bg-white text-xs">
                                    <template x-if="showData && showData.sizes">
                                        <template x-for="size in showData.sizes" :key="size">
                                            <tr>
                                                <td class="border-r-2 border-b-2 border-black p-1.5 font-semibold"
                                                    x-text="size"></td>
                                                <template x-if="showData.sleeves">
                                                    <template x-for="sleeve in showData.sleeves"
                                                        :key="sleeve">
                                                        <td class="border-r-2 border-b-2 border-black p-1.5"
                                                            x-text="(() => {
                                                                const item = showData.order_items?.find(i => i.size_name === size && i.sleeve_name === sleeve);
                                                                return item && item.qty ? item.qty : '';
                                                            })()">
                                                        </td>
                                                    </template>
                                                </template>
                                                <td class="border-b-2 border-black p-1.5 font-bold bg-cyan-50"
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
                                        <td class="border-r-2 border-b-2 border-black p-1.5 text-center"
                                            :colspan="(showData?.sleeves?.length || 0) + 1">
                                            Total:
                                        </td>
                                        <td class="border-b-2 border-black p-1.5 text-center bg-[#00B0F0] text-[#FFFF00]"
                                            x-text="showData?.order_items?.reduce((sum, item) => sum + (item.qty || 0), 0) || ''">
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Footer Warning --}}
                        <div class="bg-[#FF0000] border-t-2 border-black mt-auto">
                            <p class="text-center text-[#FFFF00] font-bold text-sm py-1.5 uppercase">
                                BACA DAN PAHAMI SEBELUM BEKERJA
                            </p>
                        </div>

                    </div>
                </div>

                {{-- PAGE 2: SABLON (PRINTING) - A4 Size --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black mt-6"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- Header: SCREEN, PROOFING, SABLON, & PRESS (Right Corner) --}}
                    <div class="flex justify-end">
                        <div class="bg-[#92D050] border-l-2 border-black px-3 py-1.5">
                            <span class="text-lg font-bold text-black">SCREEN - PROOFING - SABLON - PRESS</span>
                        </div>
                    </div>

                    {{-- Page Content --}}
                    <div class="h-full flex flex-col">
                        {{-- Print Ink & Finishing Info --}}
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Print Ink --}}
                            <table class="w-full text-md">
                                <tr>
                                    <td class="bg-[#00B0F0] border-r-2 border-y-2 p-1.5 font-semibold w-2/5">
                                        Print Ink
                                    </td>
                                    <td class="border-y-2 border-r-2 p-1.5 text-md">
                                        <span x-text="showData?.work_order?.printing?.print_ink_name || '-'"></span>
                                    </td>
                                </tr>
                            </table>

                            {{-- Finishing --}}
                            <table class="w-full text-md">
                                <tr>
                                    <td class="bg-[#FFFF00] border-l-2 border-y-2 p-1.5 font-semibold w-2/5">
                                        Finishing
                                    </td>
                                    <td class="border-y-2 border-l-2 p-1.5 text-md">
                                        <span x-text="showData?.work_order?.printing?.finishing_name || '-'"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="">
                            <h2 class="bg-[#FFFF00] text-center text-lg font-bold text-black py-1.5">
                                DETAIL & UKURAN SABLON
                            </h2>

                            <div class="flex justify-center items-center px-2 py-4">
                                <img :src="(() => {
                                    const printing = showData?.work_order?.printing;
                                    if (!printing || !printing.id || !printing.detail_img_url) return '/images/work-order-null.png?v=' + Date.now();
                                    return '{{ route('work-orders.serve-printing-image', ['printing' => '__ID__']) }}'.replace('__ID__', printing.id);
                                })()"
                                    alt="Mockup" class="max-h-235 w-auto object-contain">
                            </div>
                        </div>

                        
                        {{-- Footer Warning --}}
                        <div class="bg-[#FF0000] border-t-2 border-black mt-auto">
                            <p class="text-center text-[#FFFF00] font-bold text-sm py-1.5 uppercase">
                                BACA DAN PAHAMI SEBELUM BEKERJA
                            </p>
                        </div>

                    </div>
                </div>

                {{-- PAGE 3: JARAK & POSISI SABLON - A4 Size --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black mt-6"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- Header: SCREEN, PROOFING, SABLON, & PRESS (Right Corner) --}}
                    <div class="flex justify-end">
                        <div class="bg-[#92D050] border-l-2 border-b-2 border-black px-3 py-1.5">
                            <span class="text-lg font-bold text-black">SCREEN - PROOFING - SABLON - PRESS</span>
                        </div>
                    </div>

                    {{-- Page Content --}}
                    <div class="ph-full flex flex-col text-xs">

                        {{-- Detail Image Section --}}
                        <div class="">
                            <h2 class="bg-[#FFFF00] text-center text-lg font-bold text-black py-1.5">
                                JARAK & POSISI SABLON
                            </h2>

                            <div class="flex justify-center items-center px-2 py-4">
                                <img :src="(() => {
                                    const placement = showData?.work_order?.printing_placement;
                                    if (!placement || !placement.id || !placement.detail_img_url) return '/images/work-order-null.png?v=' + Date.now();
                                    return '{{ route('work-orders.serve-placement-image', ['placement' => '__ID__']) }}'.replace('__ID__', placement.id);
                                })()"
                                    alt="Mockup" class="max-h-235 w-auto object-contain">
                            </div>
                        </div>

                        {{-- Notes Section --}}
                        <div class="px-4 mb-2">
                            <div class="bg-[#FFFF00] border-2 border-black p-1.5 text-center">
                                <span class="font-bold text-md ">Notes:</span>
                            </div>
                            <div class="p-2 bg-white h-[60px] border-x-2 border-b-2 border-black">
                                <p class="text-xs" x-text="showData?.work_order?.printing_placement?.notes || '-'">
                                </p>
                            </div>
                        </div>

                        
                        {{-- Footer Warning --}}
                        <div class="bg-[#FF0000] border-t-2 border-black mt-auto">
                            <p class="text-center text-[#FFFF00] font-bold text-md py-1.5 uppercase">
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
                        <div class="flex justify-end mb-4">
                            <div class="bg-[#92D050] border-l-2 border-b-2 border-black px-6 py-1.5">
                                <span class="text-lg font-bold text-black">JAHIT</span>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col">
                            {{-- Content Grid: Posisi Label + Info Table --}}
                            <div class="grid grid-cols-2 gap-2 mb-2" style="flex: 0 0 auto;">

                                {{-- LEFT: Posisi Jahit Label --}}
                                <div class="border-y-2 border-r-2 border-black">
                                    <div class="bg-[#92D050] border-b-2 border-black p-1 text-center">
                                        <span class="font-bold text-md">Posisi Jahit Label</span>
                                    </div>
                                    <div class="p-2 bg-white flex justify-center items-center h-54">
                                        <img :src="(() => {
                                            const sewing = showData?.work_order?.sewing;
                                            if (!sewing || !sewing.id || !sewing.detail_img_url) return '/images/work-order-null.png?v=' + Date.now();
                                            return '{{ route('work-orders.serve-sewing-image', ['sewing' => '__ID__']) }}'.replace('__ID__', sewing.id);
                                        })()"
                                            alt="Posisi Label" class="h-50 w-auto object-contain">
                                    </div>
                                </div>

                                {{-- RIGHT: Info Table --}}
                                <div class="">
                                    <div class="bg-[#92D050] border-y-2 border-l-2 border-black p-1">
                                        <h3 class="font-bold text-md text-black text-center">Detail Jahit</h3>
                                    </div>
                                    <div class="bg-white">
                                        <table class="w-full text-md">
                                            <tr>
                                                <td
                                                    class="bg-[#00B0F0] border-x-2 border-b-2 border-black p-1 font-semibold w-3/6">
                                                    Overdek leher
                                                </td>
                                                <td class="border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.neck_overdeck_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#FFFF00] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Overdek bawah & lengan
                                                </td>
                                                <td class="border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.underarm_overdeck_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#00B0F0] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Belah samping
                                                </td>
                                                <td class="border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.side_split_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#FFFF00] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Label Jahit
                                                </td>
                                                <td class="border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.sewing?.sewing_label_name || '-'"></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Notes Section --}}
                            <div class="px-4 mb-2">
                                <div class="bg-[#FFFF00] border-2 border-black p-1.5 text-center">
                                    <span class="font-bold text-md ">Notes:</span>
                                </div>
                                <div class="p-2 bg-white h-[60px] border-x-2 border-b-2 border-black">
                                    <p class="text-xs" x-text="showData?.work_order?.sewing?.notes || ''"></p>
                                    </p>
                                </div>
                            </div>

                            
                            {{-- Footer Warning --}}
                            <div class="bg-[#FF0000] border-t-2 border-black mt-auto">
                                <p class="text-center text-[#FFFF00] font-bold text-md py-1.5 uppercase">
                                    BACA DAN PAHAMI SEBELUM BEKERJA
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- BOTTOM HALF: PACKING (Presisi 50%) --}}
                    <div class="flex flex-col border-b-2 border-black" style="height: 50%;">

                        {{-- Header: JAHIT (Nempel pojok kanan atas) --}}
                        <div class="flex justify-end mb-4">
                            <div class="bg-[#92D050] border-l-2 border-b-2 border-black px-6 py-1.5">
                                <span class="text-lg font-bold text-black">PACKING</span>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col">
                            {{-- Content Grid: Posisi Label + Info Table --}}
                            <div class="grid grid-cols-2 gap-2 mb-2" style="flex: 0 0 auto;">

                                {{-- LEFT: Posisi Hangtag --}}
                                <div class="border-y-2 border-r-2 border-black">
                                    <div class="bg-[#92D050] border-b-2 border-black p-1 text-center">
                                        <span class="font-bold text-md">Hangtag</span>
                                    </div>
                                    <div class="p-2 bg-white flex justify-center items-center h-54">
                                        <img :src="(() => {
                                            const packing = showData?.work_order?.packing;
                                            if (!packing || !packing.id || !packing.hangtag_img_url) return '/images/work-order-null.png?v=' + Date.now();
                                            return '{{ route('work-orders.serve-packing-image', ['packing' => '__ID__']) }}'.replace('__ID__', packing.id);
                                        })()"
                                            alt="Posisi Hangtag" class="h-50 w-auto object-contain">
                                    </div>
                                </div>

                                {{-- RIGHT: Info Table --}}
                                <div class="">
                                    <div class="bg-[#92D050] border-y-2 border-l-2 border-black p-1">
                                        <h3 class="font-bold text-md text-black text-center">Detail Packing</h3>
                                    </div>
                                    <div class="bg-white">
                                        <table class="w-full text-md">
                                            <tr>
                                                <td
                                                    class="bg-[#00B0F0] border-x-2 border-b-2 border-black p-1 font-semibold w-3/6">
                                                   Plastik Packing
                                                </td>
                                                <td class="border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.packing?.plastic_packing_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#FFFF00] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Sticker
                                                </td>
                                                <td class="border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.work_order?.packing?.sticker_name || '-'"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    class="bg-[#00B0F0] border-x-2 border-b-2 border-black p-1 font-semibold">
                                                    Shipping Type
                                                </td>
                                                <td class="border-b-2 border-black p-1">
                                                    <span
                                                        x-text="showData?.shipping_type || '-'"></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Notes Section --}}
                            <div class="px-4 mb-2">
                                <div class="bg-[#FFFF00] border-2 border-black p-1.5 text-center">
                                    <span class="font-bold text-md ">Notes:</span>
                                </div>
                                <div class="p-2 bg-white h-[60px] border-x-2 border-b-2 border-black">
                                    <p class="text-xs" x-text="showData?.work_order?.packing?.notes || ''"></p>
                                    </p>
                                </div>
                            </div>

                            
                            {{-- Footer Warning --}}
                            <div class="bg-[#FF0000] border-t-2 border-black mt-auto">
                                <p class="text-center text-[#FFFF00] font-bold text-md py-1.5 uppercase">
                                    BACA DAN PAHAMI SEBELUM BEKERJA
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                </div>
            </div>
            
            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end items-center gap-3">
                {{-- Download PDF Button --}}
                <a :href="(() => {
                    const workOrder = showData?.work_order;
                    if (!workOrder || !workOrder.id) return '#';
                    return '{{ route('admin.work-orders.download-pdf', ['workOrder' => '__ID__']) }}'.replace('__ID__', workOrder.id);
                })()"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    {{-- Icon --}}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    {{-- Text (Hidden on mobile, shown on md+) --}}
                    <span class="hidden md:inline ml-2">Download PDF</span>
                </a>

                {{-- Close Button --}}
                <button @click="closeShowModal()" type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
