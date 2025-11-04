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
                        <h3 class="text-lg font-semibold text-gray-900">
                            Work Order Preview
                        </h3>
                        <p class="mt-1 text-sm text-gray-500" x-text="showData?.design_name || '-'"></p>
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

            {{-- Modal Content - A4 Print Preview --}}
            <div class="p-6 bg-gray-100 flex-1 overflow-y-auto">

                {{-- CUTTING PAGE - A4 Size with proper ratio (210mm x 297mm = 1:1.414) --}}
                <div class="bg-white mx-auto shadow-2xl border-4 border-black"
                    style="width: 210mm; aspect-ratio: 1 / 1.414;">

                    {{-- Page Content with proper padding --}}
                    <div class="p-4 h-full flex flex-col text-xs overflow-y-auto">

                        {{-- Header: POTONG (Right Corner) --}}
                        <div class="flex justify-end mb-3">
                            <div class="bg-green-400 border-2 border-black px-6 py-1.5">
                                <span class="text-lg font-bold text-black">POTONG</span>
                            </div>
                        </div>

                        {{-- Title Bar: MOCKUP DESAIN --}}
                        <div class="border-2 border-black mb-3">
                            <h2
                                class="bg-yellow-300 text-center text-sm font-bold text-black py-1.5 uppercase border-b-2 border-black">
                                Mockup Desain
                            </h2>

                            {{-- Mockup Image --}}
                            <div class="flex justify-center mb-3 bg-gray-50">
                                <img :src="showData?.work_order?.mockup_img_url || '/images/work-order-null.png'"
                                    alt="Mockup" class="max-h-[250px] w-auto object-contain">
                            </div>
                        </div>

                        {{-- 2 Column Layout --}}
                        <div class="grid grid-cols-2 gap-3 mb-3">

                            {{-- LEFT: Size Chart Custom --}}
                            <div class="border-2 border-black">
                                <div class="bg-yellow-300 border-b-2 border-black p-1.5">
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
                                <div class="bg-cyan-400 border-2 border-black p-1.5">
                                    <h3 class="font-bold text-xs text-black text-center">Detail Product</h3>
                                </div>
                                <div class="bg-white">
                                    <table class="w-full text-xs">
                                        <tr>
                                            <td class="bg-cyan-100 border-x-2 border-b-2 p-1.5 font-semibold w-2/5">
                                                Product</td>
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
                                            <td class="bg-cyan-100 border-x-2 border-b-2 p-1.5 font-semibold">Material
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5">
                                                <span x-text="showData?.material_category || '-'"></span>
                                                <span x-show="showData?.material_texture"> - </span>
                                                <span x-text="showData?.material_texture || ''"></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-cyan-100  p-1.5 font-semibold border-x-2 border-b-2">Pola
                                                Potong
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.cutting_pattern_name || '-'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-cyan-100  p-1.5 font-semibold border-x-2 border-b-2">Kain
                                                Rantai
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.chain_cloth_name || '-'"></td>
                                        </tr>
                                        <tr>
                                            <td class="bg-cyan-100  p-1.5 font-semibold border-x-2 border-b-2">Ukuran
                                                Rib
                                            </td>
                                            <td class="border-x-2 border-b-2 p-1.5"
                                                x-text="showData?.work_order?.cutting?.rib_size_name || '-'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-cyan-100  p-1.5 font-semibold border-x-2 border-b-2">Notes
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
                            <div class="bg-cyan-500 border-2 border-black p-1.5">
                                <h3 class="font-bold text-xs text-white text-center">Detail Items
                                </h3>
                            </div>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="border-x border-b border-black p-1.5 font-bold">Size</th>
                                        <template x-if="showData && showData.sleeves">
                                            <template x-for="sleeve in showData.sleeves" :key="sleeve">
                                                <th class="border-x border-b border-black p-1.5 font-bold"
                                                    x-text="sleeve"></th>
                                            </template>
                                        </template>
                                        <th class="border-x border-b border-black p-1.5 font-bold bg-cyan-100">Total
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
                                                    <template x-for="sleeve in showData.sleeves" :key="sleeve">
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
                                        <td class="border-x border-b border-black p-1.5 text-right">Total Qty:</td>
                                        <template x-if="showData && showData.sleeves">
                                            <template x-for="sleeve in showData.sleeves" :key="sleeve">
                                                <td class="border-x border-b border-black p-1.5 text-center"
                                                    x-text="(() => {
                                                        const total = showData.order_items?.filter(i => i.sleeve_name === sleeve)
                                                            .reduce((sum, item) => sum + (item.qty || 0), 0);
                                                        return total || '';
                                                    })()">
                                                </td>
                                            </template>
                                        </template>
                                        <td class="border-x border-b border-black p-1.5 text-center bg-cyan-100"
                                            x-text="showData?.order_items?.reduce((sum, item) => sum + (item.qty || 0), 0) || ''">
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Footer Warning --}}
                        <div class="bg-red-600 border-2 border-black mt-auto">
                            <p class="text-center text-white font-bold text-xs py-1.5 uppercase">
                                BACA DAN PAHAMI SEBELUM BEKERJA
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                <button @click="closeShowModal()" type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Close
                </button>
                {{-- Future: Export PDF button will be added here --}}
            </div>
        </div>
    </div>
</div>
