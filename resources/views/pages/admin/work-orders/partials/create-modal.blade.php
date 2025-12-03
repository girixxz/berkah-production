{{-- ================= CREATE WORK ORDER MODAL ================= --}}
<div x-show="openModal && !selectedDesign?.work_order?.id" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 overflow-y-auto">
    
    {{-- Background Overlay --}}
    <div x-show="openModal" class="fixed inset-0 bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity"></div>
    
    {{-- Modal Panel --}}
    <div class="flex items-center justify-center h-screen p-4">
        <div @click.away="closeModal()" class="relative bg-white rounded-xl shadow-lg w-full max-w-5xl">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Create Work Order - <span
                        x-text="selectedDesign ? 'Variant Design ' + (@json($order->designVariants->pluck('id')->toArray()).indexOf(selectedDesign.id) + 1) : ''"></span>
                    <span x-show="selectedDesign?.design_name" class="text-gray-600 font-normal">
                        (<span x-text="selectedDesign?.design_name"></span>)
                    </span>
                </h3>
                <button @click="closeModal()" type="button"
                    class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    ✕
                </button>
            </div>

            {{-- Include Form Body --}}
            @include('pages.admin.work-orders.partials.form-body')

        </div>
    </div>
</div>
