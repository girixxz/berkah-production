{{-- ================= WORK ORDER FORM BODY ================= --}}
<form id="workOrderForm" x-ref="workOrderForm" method="POST" action="{{ route('admin.work-orders.store') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                <input type="hidden" name="design_variant_id" x-model="formData.design_variant_id">

                {{-- Hidden inputs for deleted images --}}
                <input type="hidden" name="delete_mockup_img" x-model="deletedImages.mockup_img">
                <input type="hidden" name="delete_custom_size_chart_img" x-model="deletedImages.custom_size_chart_img">
                <input type="hidden" name="delete_printing_detail_img" x-model="deletedImages.printing_detail_img">
                <input type="hidden" name="delete_placement_detail_img" x-model="deletedImages.placement_detail_img">
                <input type="hidden" name="delete_sewing_detail_img" x-model="deletedImages.sewing_detail_img">
                <input type="hidden" name="delete_hangtag_img" x-model="deletedImages.hangtag_img">

                <div class="px-6 py-4 space-y-6">

                    {{-- Display Backend Validation Errors --}}
                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded-md p-3">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-red-800 mb-1">Terdapat kesalahan validasi:</h4>
                                    <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- SECTION 1: MOCKUP IMAGE --}}
                    <div class="bg-white border-2 border-gray-200 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-lg">📸</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">1. Mockup Design</h3>
                                <p class="text-xs text-gray-500">Upload product mockup design</p>
                            </div>
                        </div>

                        {{-- Upload Area with Drag & Drop & Preview Inside --}}
                        <div @drop.prevent="handleDrop($event)" @dragover.prevent="handleDragOver($event)"
                            @dragleave="handleDragLeave()" @click.stop="$refs.mockupInput.click()" 
                            :class="{
                                'border-primary bg-primary/5': isDragging && !mockupPreview,
                                'border-red-500 bg-red-50': errors.mockup_img && !mockupPreview,
                                'border-gray-300 bg-gray-50': !isDragging && !errors.mockup_img && !mockupPreview,
                                'border-gray-200 bg-white p-2': mockupPreview,
                                'p-6': !mockupPreview
                            }"
                            class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary"
                            :style="mockupPreview ? 'min-height: 200px;' : ''">
                            <input x-ref="mockupInput" type="file" name="mockup_img"
                                @change="handleMockupUpload($event)" accept="image/*" class="hidden">

                            {{-- Image Preview (Full Container) --}}
                            <div x-show="mockupPreview" x-cloak class="relative w-full h-full flex items-center justify-center">
                                <img :src="mockupPreview" alt="Mockup preview"
                                    class="w-full h-auto max-h-80 object-contain rounded-lg">
                                <button type="button"
                                    @click.stop="mockupPreview = null; formData.mockup_img_url = null; deletedImages.mockup_img = true; $refs.mockupInput.value = ''"
                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Upload Icon & Text (Only when no preview) --}}
                            <div x-show="!mockupPreview">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-gray-700">
                                        <span class="text-primary font-semibold">Click</span> or drag mockup image here
                                    </p>
                                    <p class="text-xs text-gray-500">PNG, JPG, JPEG (Max 5MB)</p>
                                </div>
                            </div>
                        </div>

                        <p x-show="errors.mockup_img" x-cloak x-text="errors.mockup_img"
                            class="mt-2 text-sm text-red-600"></p>
                    </div>

                    {{-- SECTION 2: CUTTING --}}
                    <div class="bg-white border-2 border-gray-200 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-lg">🔪</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">2. Cutting Page</h3>
                                <p class="text-xs text-gray-500">Pattern, cloth, and sizing details</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {{-- Cutting Pattern --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Pola Potong <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['cuttingPatterns']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('cutting_pattern_id') }}',
                                    
                                    init() {
                                        // Load from old() (validation error)
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        
                                        // Watch formData changes (for edit mode)
                                        this.$watch('formData.cutting_pattern_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.cutting_pattern_id = option.id;
                                        if (errors.cutting_pattern_id) { delete errors.cutting_pattern_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.cutting_pattern_id || {{ $errors->has('cutting_pattern_id') ? 'true' : 'false' }} ? 
                                            'border-red-500 focus:border-red-500 focus:ring-red-200' : 
                                            'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Pattern --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="cutting_pattern_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition
                                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)"
                                                    class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                    :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.cutting_pattern_id" x-cloak x-text="errors.cutting_pattern_id"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('cutting_pattern_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Chain Cloth --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Kain Rantai <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['chainCloths']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('chain_cloth_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.chain_cloth_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.chain_cloth_id = option.id;
                                        if (errors.chain_cloth_id) { delete errors.chain_cloth_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.chain_cloth_id || {{ $errors->has('chain_cloth_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Cloth --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="chain_cloth_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition
                                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)"
                                                    class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                    :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.chain_cloth_id" x-cloak x-text="errors.chain_cloth_id"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('chain_cloth_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Rib Size --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Ukuran Rib <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['ribSizes']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('rib_size_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.rib_size_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.rib_size_id = option.id;
                                        if (errors.rib_size_id) { delete errors.rib_size_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.rib_size_id || {{ $errors->has('rib_size_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Size --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="rib_size_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition
                                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)"
                                                    class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                                    :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.rib_size_id" x-cloak x-text="errors.rib_size_id"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('rib_size_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Cutting Notes --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea name="cutting_notes" x-model="formData.cutting_notes" rows="2"
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                    placeholder="Optional notes for cutting..."></textarea>
                            </div>

                            {{-- Cutting Image (Size Chart) --}}
                            <div class="md:col-span-2" x-data="{
                                preview: null,
                                dragging: false,
                                handleFile(event) {
                                    const file = event.target.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { this.preview = e.target.result; };
                                        reader.readAsDataURL(file);
                                        formData.custom_size_chart_img_url = null;
                                        deletedImages.custom_size_chart_img = false;
                                    }
                                },
                                handleDrop(event) {
                                    event.preventDefault();
                                    this.dragging = false;
                                    const file = event.dataTransfer.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        $refs.cuttingImageInput.files = dataTransfer.files;
                                        this.handleFile({ target: { files: [file] } });
                                    }
                                }
                            }" x-init="
                                preview = formData.custom_size_chart_img_url;
                                $watch('formData.custom_size_chart_img_url', (value) => {
                                    if (value && !preview) preview = value;
                                });
                            ">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Custom Size Chart Image <span class="text-gray-400">(Optional)</span>
                                </label>

                                {{-- Drag & Drop Area with Preview Inside --}}
                                <div @drop.prevent="handleDrop($event)" @dragover.prevent="dragging = true"
                                    @dragleave="dragging = false" @click="$refs.cuttingImageInput.click()" 
                                    :class="{
                                        'border-primary bg-primary/5': dragging && !preview,
                                        'border-gray-300 bg-gray-50': !dragging && !preview,
                                        'border-gray-200 bg-white p-2': preview,
                                        'p-4': !preview
                                    }"
                                    class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary"
                                    :style="preview ? 'min-height: 180px;' : ''">
                                    <input x-ref="cuttingImageInput" type="file" name="custom_size_chart_img"
                                        @change="handleFile($event)" accept="image/*" class="hidden">
                                    
                                    {{-- Image Preview (Full Container) --}}
                                    <div x-show="preview" x-cloak class="relative w-full h-full flex items-center justify-center">
                                        <img :src="preview" alt="Size chart preview"
                                            class="w-full h-auto max-h-64 object-contain rounded-lg">
                                        <button type="button"
                                            @click.stop="preview = null; formData.custom_size_chart_img_url = null; deletedImages.custom_size_chart_img = true; $refs.cuttingImageInput.value = ''"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    {{-- Upload Icon & Text (Only when no preview) --}}
                                    <div x-show="!preview">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-xs text-gray-600">
                                            <span class="text-primary font-semibold">Click</span> or drag size chart image
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 3: PRINTING #1 --}}
                    <div class="bg-white border-2 border-gray-200 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <span class="text-lg">🎨</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">3. Screen - Proofing - Sablon - Press #1</h3>
                                <p class="text-xs text-gray-500">Ink, finishing, and detail image</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {{-- Print Ink --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Print Ink <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['printInks']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('print_ink_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.print_ink_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.print_ink_id = option.id;
                                        if (errors.print_ink_id) { delete errors.print_ink_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.print_ink_id || {{ $errors->has('print_ink_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Ink --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="print_ink_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.print_ink_id" x-cloak x-text="errors.print_ink_id" class="mt-1 text-sm text-red-600"></p>
                                @error('print_ink_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Finishing --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Finishing <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['finishings']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('finishing_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.finishing_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.finishing_id = option.id;
                                        if (errors.finishing_id) { delete errors.finishing_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.finishing_id || {{ $errors->has('finishing_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Finishing --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="finishing_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.finishing_id" x-cloak x-text="errors.finishing_id" class="mt-1 text-sm text-red-600"></p>
                                @error('finishing_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Printing Detail Image --}}
                            <div class="md:col-span-2" x-data="{
                                preview: null,
                                dragging: false,
                                handleFile(event) {
                                    const file = event.target.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { this.preview = e.target.result; };
                                        reader.readAsDataURL(file);
                                        formData.printing_detail_img_url = null;
                                        deletedImages.printing_detail_img = false;
                                    }
                                },
                                handleDrop(event) {
                                    event.preventDefault();
                                    this.dragging = false;
                                    const file = event.dataTransfer.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        $refs.printingImageInput.files = dataTransfer.files;
                                        this.handleFile({ target: { files: [file] } });
                                    }
                                }
                            }" x-init="
                                preview = formData.printing_detail_img_url;
                                $watch('formData.printing_detail_img_url', (value) => {
                                    if (value && !preview) preview = value;
                                });
                            ">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Detail Image <span class="text-gray-400">(Optional)</span>
                                </label>
                                <div @drop.prevent="handleDrop($event)" @dragover.prevent="dragging = true"
                                    @dragleave="dragging = false" @click="$refs.printingImageInput.click()" :class="{
                                        'border-primary bg-primary/5': dragging && !preview,
                                        'border-gray-300 bg-gray-50': !dragging && !preview,
                                        'border-gray-200 bg-white p-2': preview,
                                        'p-4': !preview
                                    }" :style="preview ? 'min-height: 180px;' : ''"
                                    class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary hover:bg-primary/5">
                                    <input x-ref="printingImageInput" type="file" name="printing_detail_img"
                                        @change="handleFile($event)" accept="image/*" class="hidden">
                                    <div x-show="preview" class="relative w-full h-full flex items-center justify-center">
                                        <img :src="preview" alt="Printing detail"
                                            class="w-full h-auto max-h-64 object-contain rounded-lg">
                                        <button type="button"
                                            @click.stop="preview = null; formData.printing_detail_img_url = null; deletedImages.printing_detail_img = true; $refs.printingImageInput.value = ''"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="!preview">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-xs text-gray-600">
                                            <span class="text-primary font-semibold">Click</span> or drag printing detail image
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 4: PRINTING #2 (PLACEMENT) --}}
                    <div class="bg-white border-2 border-gray-200 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-lg">📍</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">4. Screen - Proofing - Sablon - Press #2</h3>
                                <p class="text-xs text-gray-500">Placement details and image</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-3">
                            {{-- Placement Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea name="placement_notes" x-model="formData.placement_notes" rows="2"
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                    placeholder="Optional notes for placement..."></textarea>
                            </div>

                            {{-- Placement Detail Image --}}
                            <div x-data="{
                                preview: null,
                                dragging: false,
                                handleFile(event) {
                                    const file = event.target.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { this.preview = e.target.result; };
                                        reader.readAsDataURL(file);
                                        formData.placement_detail_img_url = null;
                                        deletedImages.placement_detail_img = false;
                                    }
                                },
                                handleDrop(event) {
                                    event.preventDefault();
                                    this.dragging = false;
                                    const file = event.dataTransfer.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        $refs.placementImageInput.files = dataTransfer.files;
                                        this.handleFile({ target: { files: [file] } });
                                    }
                                }
                            }" x-init="
                                preview = formData.placement_detail_img_url;
                                $watch('formData.placement_detail_img_url', (value) => {
                                    if (value && !preview) preview = value;
                                });
                            ">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Placement Detail Image <span class="text-gray-400">(Optional)</span>
                                </label>
                                <div @drop.prevent="handleDrop($event)" @dragover.prevent="dragging = true"
                                    @dragleave="dragging = false" @click="$refs.placementImageInput.click()" :class="{
                                        'border-primary bg-primary/5': dragging && !preview,
                                        'border-gray-300 bg-gray-50': !dragging && !preview,
                                        'border-gray-200 bg-white p-2': preview,
                                        'p-4': !preview
                                    }" :style="preview ? 'min-height: 180px;' : ''"
                                    class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary hover:bg-primary/5">
                                    <input x-ref="placementImageInput" type="file" name="placement_detail_img"
                                        @change="handleFile($event)" accept="image/*" class="hidden">
                                    <div x-show="preview" class="relative w-full h-full flex items-center justify-center">
                                        <img :src="preview" alt="Placement detail"
                                            class="w-full h-auto max-h-64 object-contain rounded-lg">
                                        <button type="button"
                                            @click.stop="preview = null; formData.placement_detail_img_url = null; deletedImages.placement_detail_img = true; $refs.placementImageInput.value = ''"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="!preview">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-xs text-gray-600">
                                            <span class="text-primary font-semibold">Click</span> or drag placement detail image
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 5: SEWING --}}
                    <div class="bg-white border-2 border-gray-200 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                <span class="text-lg">🧵</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">5. Jahit Page</h3>
                                <p class="text-xs text-gray-500">Overdeck, labels, and detail image</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {{-- Neck Overdeck --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Overdek Leher <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['neckOverdecks']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('neck_overdeck_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.neck_overdeck_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.neck_overdeck_id = option.id;
                                        if (errors.neck_overdeck_id) { delete errors.neck_overdeck_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.neck_overdeck_id || {{ $errors->has('neck_overdeck_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Overdeck --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="neck_overdeck_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.neck_overdeck_id" x-cloak x-text="errors.neck_overdeck_id" class="mt-1 text-sm text-red-600"></p>
                                @error('neck_overdeck_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Underarm Overdeck --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Overdek Bawah & Lengan <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['underarmOverdecks']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('underarm_overdeck_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.underarm_overdeck_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.underarm_overdeck_id = option.id;
                                        if (errors.underarm_overdeck_id) { delete errors.underarm_overdeck_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.underarm_overdeck_id || {{ $errors->has('underarm_overdeck_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Overdeck --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="underarm_overdeck_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.underarm_overdeck_id" x-cloak x-text="errors.underarm_overdeck_id" class="mt-1 text-sm text-red-600"></p>
                                @error('underarm_overdeck_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Side Split --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Belah Samping <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['sideSplits']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('side_split_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.side_split_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.side_split_id = option.id;
                                        if (errors.side_split_id) { delete errors.side_split_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.side_split_id || {{ $errors->has('side_split_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Split --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="side_split_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.side_split_id" x-cloak x-text="errors.side_split_id" class="mt-1 text-sm text-red-600"></p>
                                @error('side_split_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Sewing Label --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Label Jahit <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['sewingLabels']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('sewing_label_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.sewing_label_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.sewing_label_id = option.id;
                                        if (errors.sewing_label_id) { delete errors.sewing_label_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.sewing_label_id || {{ $errors->has('sewing_label_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Label --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="sewing_label_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.sewing_label_id" x-cloak x-text="errors.sewing_label_id" class="mt-1 text-sm text-red-600"></p>
                                @error('sewing_label_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Sewing Notes --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea name="sewing_notes" x-model="formData.sewing_notes" rows="2"
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                    placeholder="Optional notes for sewing..."></textarea>
                            </div>

                            {{-- Sewing Detail Image --}}
                            <div class="md:col-span-2" x-data="{
                                preview: null,
                                dragging: false,
                                handleFile(event) {
                                    const file = event.target.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { this.preview = e.target.result; };
                                        reader.readAsDataURL(file);
                                        formData.sewing_detail_img_url = null;
                                        deletedImages.sewing_detail_img = false;
                                    }
                                },
                                handleDrop(event) {
                                    event.preventDefault();
                                    this.dragging = false;
                                    const file = event.dataTransfer.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        $refs.sewingImageInput.files = dataTransfer.files;
                                        this.handleFile({ target: { files: [file] } });
                                    }
                                }
                            }" x-init="
                                preview = formData.sewing_detail_img_url;
                                $watch('formData.sewing_detail_img_url', (value) => {
                                    if (value && !preview) preview = value;
                                });
                            ">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Detail Image <span class="text-gray-400">(Optional)</span>
                                </label>
                                <div @drop.prevent="handleDrop($event)" @dragover.prevent="dragging = true"
                                    @dragleave="dragging = false" @click="$refs.sewingImageInput.click()" :class="{
                                        'border-primary bg-primary/5': dragging && !preview,
                                        'border-gray-300 bg-gray-50': !dragging && !preview,
                                        'border-gray-200 bg-white p-2': preview,
                                        'p-4': !preview
                                    }" :style="preview ? 'min-height: 180px;' : ''"
                                    class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary hover:bg-primary/5">
                                    <input x-ref="sewingImageInput" type="file" name="sewing_detail_img"
                                        @change="handleFile($event)" accept="image/*" class="hidden">
                                    <div x-show="preview" class="relative w-full h-full flex items-center justify-center">
                                        <img :src="preview" alt="Sewing detail"
                                            class="w-full h-auto max-h-64 object-contain rounded-lg">
                                        <button type="button"
                                            @click.stop="preview = null; formData.sewing_detail_img_url = null; deletedImages.sewing_detail_img = true; $refs.sewingImageInput.value = ''"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="!preview">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-xs text-gray-600">
                                            <span class="text-primary font-semibold">Click</span> or drag sewing detail image
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 6: PACKING --}}
                    <div class="bg-white border-2 border-gray-200 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                                <span class="text-lg">📦</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">6. Packing Page</h3>
                                <p class="text-xs text-gray-500">Plastic, sticker, and hangtag image</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {{-- Plastic Packing --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Plastik Packing <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['plasticPackings']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('plastic_packing_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.plastic_packing_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.plastic_packing_id = option.id;
                                        if (errors.plastic_packing_id) { delete errors.plastic_packing_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.plastic_packing_id || {{ $errors->has('plastic_packing_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Packing --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="plastic_packing_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.plastic_packing_id" x-cloak x-text="errors.plastic_packing_id" class="mt-1 text-sm text-red-600"></p>
                                @error('plastic_packing_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Sticker --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Sticker <span class="text-red-600">*</span>
                                </label>
                                <div x-data="{
                                    open: false,
                                    options: @js($masterData['stickers']->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                                    selected: null,
                                    selectedValue: '{{ old('sticker_id') }}',
                                    
                                    init() {
                                        if (this.selectedValue) {
                                            this.selected = this.options.find(o => String(o.id) === String(this.selectedValue)) || null;
                                        }
                                        this.$watch('formData.sticker_id', (value) => {
                                            if (value) {
                                                this.selectedValue = value;
                                                this.selected = this.options.find(o => String(o.id) === String(value)) || null;
                                            }
                                        });
                                    },
                                    
                                    select(option) {
                                        this.selected = option;
                                        this.selectedValue = option.id;
                                        this.open = false;
                                        formData.sticker_id = option.id;
                                        if (errors.sticker_id) { delete errors.sticker_id; }
                                    }
                                }" class="relative w-full">
                                    <button type="button" @click="open = !open"
                                        :class="errors.sticker_id || {{ $errors->has('sticker_id') ? 'true' : 'false' }} ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                        class="w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 transition-colors">
                                        <span x-text="selected ? selected.name : '-- Select Sticker --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="sticker_id" x-model="selectedValue">
                                    <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="option in options" :key="option.id">
                                                <li @click="select(option)" class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors" :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.id === option.id }">
                                                    <span x-text="option.name"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <p x-show="errors.sticker_id" x-cloak x-text="errors.sticker_id" class="mt-1 text-sm text-red-600"></p>
                                @error('sticker_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Packing Notes --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea name="packing_notes" x-model="formData.packing_notes" rows="2"
                                    class="w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 text-gray-700"
                                    placeholder="Optional notes for packing..."></textarea>
                            </div>

                            {{-- Hangtag Image --}}
                            <div class="md:col-span-2" x-data="{
                                preview: null,
                                dragging: false,
                                handleFile(event) {
                                    const file = event.target.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { this.preview = e.target.result; };
                                        reader.readAsDataURL(file);
                                        formData.hangtag_img_url = null;
                                        deletedImages.hangtag_img = false;
                                    }
                                },
                                handleDrop(event) {
                                    event.preventDefault();
                                    this.dragging = false;
                                    const file = event.dataTransfer.files[0];
                                    if (file && file.type.startsWith('image/')) {
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        $refs.hangtagImageInput.files = dataTransfer.files;
                                        this.handleFile({ target: { files: [file] } });
                                    }
                                }
                            }" x-init="
                                preview = formData.hangtag_img_url;
                                $watch('formData.hangtag_img_url', (value) => {
                                    if (value && !preview) preview = value;
                                });
                            ">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Hangtag Image <span class="text-gray-400">(Optional)</span>
                                </label>
                                <div @drop.prevent="handleDrop($event)" @dragover.prevent="dragging = true"
                                    @dragleave="dragging = false" @click="$refs.hangtagImageInput.click()" :class="{
                                        'border-primary bg-primary/5': dragging && !preview,
                                        'border-gray-300 bg-gray-50': !dragging && !preview,
                                        'border-gray-200 bg-white p-2': preview,
                                        'p-4': !preview
                                    }" :style="preview ? 'min-height: 180px;' : ''"
                                    class="border-2 border-dashed rounded-lg text-center cursor-pointer transition-all hover:border-primary hover:bg-primary/5">
                                    <input x-ref="hangtagImageInput" type="file" name="hangtag_img"
                                        @change="handleFile($event)" accept="image/*" class="hidden">
                                    <div x-show="preview" class="relative w-full h-full flex items-center justify-center">
                                        <img :src="preview" alt="Hangtag"
                                            class="w-full h-auto max-h-64 object-contain rounded-lg">
                                        <button type="button"
                                            @click.stop="preview = null; formData.hangtag_img_url = null; deletedImages.hangtag_img = true; $refs.hangtagImageInput.value = ''"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="!preview">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-xs text-gray-600">
                                            <span class="text-primary font-semibold">Click</span> or drag hangtag image
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
