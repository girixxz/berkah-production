@extends('layouts.app')

@section('title', 'Edit Order')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Orders', 'Edit Order']" />

    <form x-data="orderForm()" @customer_id-selected.window="customer_id = $event.detail"
        @sales_id-selected.window="sales_id = $event.detail"
        @product_category_id-selected.window="product_category_id = $event.detail"
        @material_category_id-selected.window="material_category_id = $event.detail"
        @material_texture_id-selected.window="material_texture_id = $event.detail"
        @shipping_id-selected.window="shipping_id = $event.detail"
        @submit.prevent="validateAndSubmit"
        method="POST" action="{{ route('admin.orders.update', $order->id) }}" enctype="multipart/form-data"
        class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6 space-y-6 md:space-y-8">
        @csrf
        @method('PUT')

        {{-- ================= Header ================= --}}
        <div class="space-y-6 border-b border-gray-200 pb-6 md:pb-8">
            <h2 class="text-xl font-semibold text-gray-900">Edit Order</h2>
        </div>

        {{-- Priority, Order Date & Deadline --}}
        <div class="border-b border-gray-200 pb-8 md:pb-12">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                {{-- Priority --}}
                <div class="space-y-2">
                    <label for="priority" class="block text-sm font-medium text-gray-600">Priority</label>
                    <x-select-form name="priority" placeholder="Select Priority" :options="[
                        ['value' => 'normal', 'name' => 'Normal'],
                        ['value' => 'high', 'name' => 'High']
                    ]" value="value" display="name" :old="old('priority', $order->priority)" />
                </div>

                {{-- Order Date --}}
                <div class="space-y-2">
                    <label for="order_date" class="block text-sm font-medium text-gray-600">Order Date <span class="text-red-500">*</span></label>
                    <div class="relative cursor-pointer" @click="$refs.orderDateInput.showPicker()">
                        <input x-ref="orderDateInput" id="order_date" name="order_date" type="date" x-model="order_date"
                            value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}"
                            :class="errors.order_date ? 'border-red-500' : 'border-gray-300'"
                            class="w-full rounded-md px-3 py-2 text-sm border focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700 cursor-pointer" />
                        <p x-show="errors.order_date" x-text="errors.order_date" class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600"></p>
                        @error('order_date')
                            <p class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Deadline --}}
                <div class="space-y-2">
                    <label for="deadline" class="block text-sm font-medium text-gray-600">Deadline <span class="text-red-500">*</span></label>
                    <div class="relative cursor-pointer" @click="$refs.deadlineInput.showPicker()">
                        <input x-ref="deadlineInput" id="deadline" name="deadline" type="date" x-model="deadline"
                            value="{{ old('deadline', $order->deadline->format('Y-m-d')) }}"
                            :class="errors.deadline ? 'border-red-500' : 'border-gray-300'"
                            class="w-full rounded-md px-3 py-2 text-sm border focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700 cursor-pointer" />
                        <p x-show="errors.deadline" x-text="errors.deadline" class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600"></p>
                        @error('deadline')
                            <p class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= Customers & Sales ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8 md:pb-12">
            <h3 class="text-lg font-semibold text-gray-800">Data Customers & Sales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div class="relative flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24 md:mt-2">Customer <span class="text-red-500">*</span></label>

                    <div class="w-full">
                        <div class="relative">
                            <x-select-search name="customer_id" label="Customer" placeholder="Select Customer"
                                :options="$customers" display="customer_name" :old="old('customer_id', $order->customer_id)" />
                            <p x-show="errors.customer_id" x-text="errors.customer_id" class="absolute left-0 -bottom-5 text-xs text-red-600"></p>
                        </div>
                    </div>
                </div>

                <div class="relative flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24 md:mt-2">Sales <span class="text-red-500">*</span></label>

                    <div class="w-full">
                        <div class="relative">
                            <x-select-search name="sales_id" label="Sales" placeholder="Select Sales" :options="$sales"
                                display="sales_name" :old="old('sales_id', $order->sales_id)" />
                            <p x-show="errors.sales_id" x-text="errors.sales_id" class="absolute left-0 -bottom-5 text-xs text-red-600"></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= Detail Products ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8 md:pb-12">
            <h3 class="text-lg font-semibold text-gray-800">Detail Products</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div class="space-y-7">
                    {{-- Product --}}
                    <div class="relative flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                        <label class="text-sm text-gray-600 md:w-24">Product <span class="text-red-500">*</span></label>

                        <div class="relative w-full">
                            <x-select-search name="product_category_id" label="Product" placeholder="Select Product"
                                :options="$productCategories" display="product_name" :old="old('product_category_id', $order->product_category_id)" />
                            <p x-show="errors.product_category_id" x-text="errors.product_category_id" class="absolute left-0 -bottom-5 text-xs text-red-600"></p>
                        </div>
                    </div>

                    {{-- Color --}}
                    <div class="relative flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                        <label class="text-sm text-gray-600 md:w-24">Color <span class="text-red-500">*</span></label>
                        <div class="relative w-full">
                            <input type="text" name="product_color" x-model="product_color"
                                value="{{ old('product_color', $order->product_color) }}"
                                :class="errors.product_color ? 'border-red-500' : 'border-gray-300'"
                                class="w-full rounded-md border px-3 py-2 text-sm text-gray-700
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Enter color" />
                            <p x-show="errors.product_color" x-text="errors.product_color" class="absolute left-0 -bottom-5 text-xs text-red-600"></p>
                            @error('product_color')
                                <p class="absolute left-0 -bottom-5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Materials --}}
                    <div class="relative flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                        <label class="text-sm text-gray-600 md:w-24">Material <span class="text-red-500">*</span></label>
                        <div class="flex flex-col md:flex-row gap-2 gap-y-6 md:gap-3 w-full">
                            <div class="relative w-full">
                                <x-select-search name="material_category_id" label="Product" placeholder="Select Material"
                                    :options="$materialCategories" display="material_name" :old="old('material_category_id', $order->material_category_id)" />
                                <p x-show="errors.material_category_id" x-text="errors.material_category_id" class="absolute left-0 -bottom-5 text-xs text-red-600"></p>
                            </div>

                            <div class="relative w-full">
                                <x-select-search name="material_texture_id" label="Product" placeholder="Select Texture"
                                    :options="$materialTextures" display="texture_name" :old="old('material_texture_id', $order->material_texture_id)" />
                                <p x-show="errors.material_texture_id" x-text="errors.material_texture_id" class="absolute left-0 -bottom-5 text-xs text-red-600"></p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Notes --}}
                <div class="flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-14">Note</label>
                    <div class="relative w-full md:flex-1">
                        <textarea rows="3" name="notes" x-model="notes"
                            class="w-full min-h-[165px] rounded-md border border-gray-300 px-3 py-2 text-sm
                            focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Write notes here...">{{ old('notes', $order->notes) }}</textarea>
                        @error('notes')
                            <p class="absolute left-0 -bottom-5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= Detail Orders ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Detail Orders <span class="text-red-500">*</span></h3>
                <p x-show="errors.designs" x-text="errors.designs" class="text-sm text-red-600"></p>
                @error('designs')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Design Variants List --}}
            <template x-for="(design, dIndex) in designVariants" :key="dIndex">
                <div class="border border-gray-300 rounded-lg p-4 relative">
                    {{-- Delete Design Variant --}}
                    <button type="button"
                        @click="
                            let msg = design.name.trim() 
                                ? `Are you sure you want to delete ${design.name} design?` 
                                : 'Are you sure you want to delete this design variant?';
                            if (confirm(msg)) designVariants.splice(dIndex, 1)
                        "
                        class="absolute top-2 right-2 p-2 rounded-md text-gray-500 hover:text-red-600">
                        ✕
                    </button>


                    {{-- Design Name & Add Sleeve Variant --}}
                    <div class="flex flex-col md:flex-row gap-3 mt-8 md:mt-0">
                        <div class="relative w-full md:w-72">
                            <input type="text" placeholder="Design Name" x-model="design.name"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2" />
                            <span x-show="design.error" x-text="design.error"
                                class="absolute left-0 -bottom-5 text-xs text-red-600"></span>
                        </div>

                        <div class="flex flex-col md:flex-row gap-2 items-start md:items-center">
                            <button type="button" @click="if(design.name.trim() !== '') addSleeveVariant(dIndex)"
                                :class="design.name.trim() === '' ?
                                    'cursor-not-allowed bg-gray-300 text-white' :
                                    'bg-primary hover:bg-primary-dark text-white'"
                                class="px-3 py-2 rounded-md text-sm whitespace-nowrap w-full md:w-auto">
                                + Add Sleeve
                            </button>
                            <span class="italic text-xs text-gray-400 hidden md:inline">(Fill design name first)</span>
                        </div>
                    </div>


                    {{-- Sleeve Variants List --}}
                    <template x-for="(variant, vIndex) in design.sleeveVariants" :key="vIndex">
                        <div class="border border-gray-200 rounded-lg p-4 space-y-4 mt-4 relative">
                            {{-- Delete Sleeve Variant --}}
                            <button type="button"
                                @click="
                                    if (confirm('Are you sure you want to delete this sleeve?')) {
                                        design.sleeveVariants.splice(vIndex, 1)
                                    }
                                "
                                class="absolute top-2 right-2 p-2 rounded-md text-gray-500 hover:text-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="space-y-3">
                                <div class="flex flex-col md:flex-row md:justify-start md:items-start gap-3">
                                    {{-- Sleeve --}}
                                    <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-8 mt-4 md:mt-0">
                                        <label class="text-sm text-gray-600">Sleeve</label>
                                        <div class="relative w-full md:w-56" x-data="sleeveSelect(dIndex, vIndex, variant.sleeve)"
                                            @sleeve-selected.window="if($event.detail.dIndex === dIndex && $event.detail.vIndex === vIndex) variant.sleeve = $event.detail.value">

                                            {{-- Custom Select Button --}}
                                            <button type="button" @click="open = !open"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-left bg-white
                                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary flex justify-between items-center">
                                                <span x-text="selected ? selected.sleeve_name : '-- Select Sleeve --'"
                                                    :class="!selected ? 'text-gray-400' : 'text-gray-900'"></span>
                                                <svg class="w-4 h-4 text-gray-400" :class="open && 'rotate-180'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown --}}
                                            <div x-show="open" @click.away="open = false" x-cloak
                                                class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg">
                                                <div class="relative px-4 py-2">
                                                    <div
                                                        class="absolute inset-y-0 left-7 flex items-center pointer-events-none">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" x-model="search" placeholder="Search..."
                                                        class="block w-full h-10 pl-10 pr-3 text-gray-600 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary/50 focus:border-primary">
                                                </div>
                                                <ul class="max-h-60 overflow-y-auto">
                                                    <template x-for="sleeve in filteredOptions" :key="sleeve.id">
                                                        <li @click="selectSleeve(sleeve)"
                                                            class="px-5 py-2 text-sm hover:bg-primary/5 cursor-pointer"
                                                            :class="variant.sleeve == sleeve.id ? 'bg-primary/10 font-medium' :
                                                                ''"
                                                            x-text="sleeve.sleeve_name">
                                                        </li>
                                                    </template>
                                                    <li x-show="filteredOptions.length === 0"
                                                        class="px-5 py-2 text-sm text-gray-400 text-center">
                                                        No results found
                                                    </li>
                                                </ul>
                                            </div>

                                            <span x-show="variant.error" x-text="variant.error"
                                                class="absolute left-0 -bottom-5 text-xs text-red-600"></span>
                                        </div>
                                    </div>

                                    {{-- Base Price --}}
                                    <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                                        <label class="text-sm text-gray-600">Base Price</label>
                                        <div class="relative">
                                            <div class="relative">
                                                <span
                                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                                                    Rp
                                                </span>
                                                <input type="text" :value="variant.basePriceDisplay || ''"
                                                    @input="
                                                        let value = $event.target.value.replace(/[^0-9]/g, '');
                                                        variant.basePrice = value ? parseInt(value) : 0;
                                                        variant.basePriceDisplay = value ? parseInt(value).toLocaleString('id-ID') : '';
                                                        $event.target.value = variant.basePriceDisplay;
                                                        updateUnitPrices(dIndex, vIndex);
                                                    "
                                                    placeholder="0"
                                                    class="w-full md:w-40 rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                            </div>
                                            <span x-show="variant.basePriceError" x-text="variant.basePriceError"
                                                class="absolute left-0 -bottom-5 text-xs text-red-600"></span>
                                        </div>
                                    </div>

                                    {{-- Add Size --}}
                                    <div class="flex flex-col md:flex-row md:items-center gap-2">
                                        <button type="button"
                                            @click="if(variant.sleeve !== '' && variant.basePrice > 0) { 
                                                openModal = 'addSize'; 
                                                selectedDesign = dIndex; 
                                                selectedVariant = vIndex;
                                                selectedSizes = variant.rows.map(r => sizes.find(s => s.id === r.size_id)).filter(s => s);
                                            }"
                                            :class="(variant.sleeve === '' || variant.basePrice <= 0) ?
                                            'cursor-not-allowed bg-gray-300 text-white' :
                                            'bg-primary hover:bg-primary-dark text-white'"
                                            class="w-full md:w-auto px-3 py-2 rounded-md text-sm whitespace-nowrap">
                                            + Add Size
                                        </button>
                                        <span class="italic text-xs text-gray-400 hidden md:inline">(Select sleeve & set
                                            base price
                                            first)</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Table --}}
                            <div class="overflow-x-auto -mx-4 md:mx-0">
                                <table class="w-full text-sm min-w-[640px]" style="table-layout: fixed;">
                                    <thead class="bg-primary-light text-gray-600">
                                        <tr>
                                            <th class="py-2 px-4 text-left rounded-l-lg" style="width: 5%;">No</th>
                                            <th class="py-2 px-4 text-left" style="width: 12%;">Size</th>
                                            <th class="py-2 px-4 text-left" style="width: 22%;">Unit Price</th>
                                            <th class="py-2 px-4 text-left" style="width: 15%;">QTY</th>
                                            <th class="py-2 px-4 text-left" style="width: 20%;">Total Price</th>
                                            <th class="py-2 px-4 text-right rounded-r-lg" style="width: 8%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, rIndex) in variant.rows" :key="rIndex">
                                            <tr class="border-t border-gray-200">
                                                <td class="py-2 px-4" x-text="rIndex+1"></td>
                                                <td class="py-2 px-4">
                                                    <span x-text="row.size"></span>
                                                    <span class="text-xs text-gray-500" x-show="row.extraPrice > 0">
                                                        (+<span x-text="row.extraPrice.toLocaleString('id-ID')"></span>)
                                                    </span>
                                                </td>
                                                <td class="py-2 px-4">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-600 text-sm">Rp</span>
                                                        <input type="text" :value="row.unitPriceDisplay || ''"
                                                            @input="
                                                                let value = $event.target.value.replace(/[^0-9]/g, '');
                                                                row.unitPrice = value ? parseInt(value) : 0;
                                                                row.unitPriceDisplay = value ? parseInt(value).toLocaleString('id-ID') : '';
                                                                $event.target.value = row.unitPriceDisplay;
                                                            "
                                                            :class="row.unitPrice === 0 ? 'border-red-500' : 'border-gray-300'"
                                                            class="w-32 rounded-md border px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                                    </div>
                                                </td>
                                                <td class="py-2 px-4">
                                                    <div class="flex items-center gap-2">
                                                        <input type="number" x-model.number="row.qty" min="0"
                                                            @focus="if(row.qty == 0) row.qty = ''"
                                                            @blur="if(row.qty === '' || row.qty === null) row.qty = 0"
                                                            :class="row.qty === 0 ? 'border-red-500' : 'border-gray-300'"
                                                            class="w-20 rounded-md border px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                                        <span x-show="row.qty === 0"
                                                            class="text-xs text-red-600 whitespace-nowrap">QTY
                                                            required</span>
                                                    </div>
                                                </td>
                                                <td class="py-2 px-4 font-medium text-gray-900"
                                                    x-text="'Rp ' + (row.unitPrice * row.qty).toLocaleString('id-ID')">
                                                </td>
                                                <td class="py-2 px-4 text-right">
                                                    <button type="button" @click="showDeleteSizeConfirm = { dIndex, vIndex, rIndex }"
                                                        class="p-2 rounded-md bg-red-500 text-white hover:bg-red-600">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        
                                        {{-- Row TOTAL per Sleeve --}}
                                        <tr x-show="variant.rows.length > 0" class="bg-primary-light/50">
                                            <td class="py-3 px-4 font-bold text-gray-800" style="width: 5%;"></td>
                                            <td class="py-3 px-4 font-bold text-gray-800" style="width: 12%;"></td>
                                            <td class="py-3 px-4 font-bold text-gray-800 text-center" style="width: 22%;">
                                                TOTAL
                                            </td>
                                            <td class="py-3 px-4 font-bold text-gray-900 text-left" style="width: 15%;" 
                                                x-text="variant.rows.reduce((sum, row) => sum + (row.qty || 0), 0)">
                                            </td>
                                            <td class="py-3 px-4 font-bold text-gray-900 text-left" style="width: 20%;" 
                                                x-text="'Rp ' + variant.rows.reduce((sum, row) => sum + ((row.unitPrice || 0) * (row.qty || 0)), 0).toLocaleString('id-ID')">
                                            </td>
                                            <td class="py-3 px-4" style="width: 8%;"></td>
                                        </tr>
                                        
                                        <tr x-show="variant.rows.length === 0">
                                            <td colspan="6" class="py-3 px-4 text-center text-gray-400">
                                                No sizes added yet.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                    
                    {{-- TOTAL per Design (Gabungan semua sleeve) --}}
                    <div x-show="design.sleeveVariants.length > 0" class="mt-4 overflow-x-auto -mx-4 md:mx-0">
                        <table class="w-full text-sm min-w-[640px]" style="table-layout: fixed;">
                            <tbody>
                                <tr class="bg-gradient-to-br from-red-100 to-red-50 rounded-lg p-4 shadow-sm ">
                                    <td class="py-3 px-4 font-bold text-white rounded-l-md" style="width: 5%;"></td>
                                    <td class="py-3 px-4 font-bold text-white" style="width: 12%;"></td>
                                    <td class="py-3 px-4 font-bold text-red-600 text-left" style="width: 22%;">
                                        <span>TOTAL (</span><span x-text="design.name || 'Design ' + (dIndex + 1)"></span><span>)</span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-red-600 text-left" style="width: 15%;" 
                                        x-text="design.sleeveVariants.reduce((total, variant) => 
                                            total + variant.rows.reduce((sum, row) => sum + (row.qty || 0), 0), 0)">
                                    </td>
                                    <td class="py-3 px-4 font-bold text-red-600 text-left" style="width: 20%;" 
                                        x-text="'Rp ' + design.sleeveVariants.reduce((total, variant) => 
                                            total + variant.rows.reduce((sum, row) => 
                                                sum + ((row.unitPrice || 0) * (row.qty || 0)), 0), 0).toLocaleString('id-ID')">
                                    </td>
                                    <td class="py-3 px-4 rounded-r-md" style="width: 8%;"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            {{-- Button Add Design Variant --}}
            <button type="button" @click="addDesignVariant()"
                class="w-full md:w-auto px-3 py-2 rounded-md text-sm font-medium cursor-pointer bg-primary hover:bg-green-700 text-white">
                + Add Design Variant
            </button>

            {{-- GRAND TOTAL (Seluruh Design) --}}
            <div x-show="designVariants.length > 0" class="mt-4 overflow-x-auto -mx-4 md:mx-0">
                <table class="w-full text-sm min-w-[640px]" style="table-layout: fixed;">
                    <tbody>
                        <tr class="bg-primary-light">
                            <td class="py-4 px-4 font-bold text-gray-600 rounded-l-md" style="width: 5%;"></td>
                            <td class="py-4 px-4 font-bold text-gray-600" style="width: 12%;"></td>
                            <td class="py-4 px-4 font-bold text-gray-600 text-left" style="width: 22%;">
                                GRAND TOTAL
                            </td>
                            <td class="py-4 px-4 font-bold text-gray-600 text-left text-lg" style="width: 15%;" 
                                x-text="designVariants.reduce((grandTotal, design) => 
                                    grandTotal + design.sleeveVariants.reduce((total, variant) => 
                                        total + variant.rows.reduce((sum, row) => sum + (row.qty || 0), 0), 0), 0)">
                            </td>
                            <td class="py-4 px-4 font-bold text-gray-600 text-left text-lg" style="width: 20%;" 
                                x-text="'Rp ' + designVariants.reduce((grandTotal, design) => 
                                    grandTotal + design.sleeveVariants.reduce((total, variant) => 
                                        total + variant.rows.reduce((sum, row) => 
                                            sum + ((row.unitPrice || 0) * (row.qty || 0)), 0), 0), 0).toLocaleString('id-ID')">
                            </td>
                            <td class="py-4 px-4 rounded-r-md" style="width: 8%;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            {{-- ================= Modal Add Size ================= --}}
            <div x-show="openModal === 'addSize'" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
                <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6 space-y-5">
                    <div class="flex justify-between items-center pb-4">
                        <h3 class="text-xl font-semibold text-gray-900">Select Sizes</h3>
                        <button type="button" @click="openModal=null"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Size Cards --}}
                    <div
                        class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 max-h-96 overflow-y-auto py-2">
                        <template x-for="size in sizes" :key="size.id">
                            <div @click="toggleSize(size)"
                                :class="selectedSizes.find(s => s.id === size.id) ?
                                    'bg-primary text-white border-primary shadow-md scale-105' :
                                    'bg-white text-gray-700 border-gray-300 hover:border-primary/50 hover:shadow-sm'"
                                class="cursor-pointer rounded-lg border-2 px-4 py-3 text-center text-sm font-medium transition-all duration-200">
                                <span x-text="size.size_name" class="block"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" @click="openModal=null"
                            class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="button" @click="applySizes"
                            class="px-5 py-2.5 rounded-lg bg-primary text-white hover:bg-primary-dark font-medium transition-colors shadow-sm">
                            Add Size
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= Additionals & Final ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8 md:pb-12">
            <h3 class="text-lg font-semibold text-gray-800">Additionals</h3>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-x-8 gap-y-6">
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Additionals</label>

                    {{-- List Input Additionals --}}
                    <template x-for="(item, index) in additionals" :key="index">
                        <div class="flex flex-col gap-3 mb-4">
                            <div class="flex flex-col md:flex-row gap-3">
                                <div class="flex-1 relative" x-data="additionalServiceSelect(index, item.service_id)"
                                    @service-selected.window="if($event.detail.index === index) item.service_id = $event.detail.value">

                                    {{-- Custom Select Button --}}
                                    <button type="button" @click="open = !open"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-left bg-white
                                        focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary flex justify-between items-center">
                                        <span x-text="selected ? selected.service_name : '-- Select Service --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-900'"></span>
                                        <svg class="w-4 h-4 text-gray-400" :class="open && 'rotate-180'" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {{-- Dropdown --}}
                                    <div x-show="open" @click.away="open = false" x-cloak
                                        class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg">
                                        <div class="relative px-4 py-2">
                                            <div class="absolute inset-y-0 left-7 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                            <input type="text" x-model="search" placeholder="Search..."
                                                class="block w-full h-10 pl-10 pr-3 text-gray-600 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary/50 focus:border-primary">
                                        </div>
                                        <ul class="max-h-60 overflow-y-auto">
                                            <template x-for="service in filteredOptions" :key="service.id">
                                                <li @click="selectService(service)"
                                                    class="px-5 py-2 text-sm hover:bg-primary/5 cursor-pointer"
                                                    :class="item.service_id == service.id ? 'bg-primary/10 font-medium' : ''"
                                                    x-text="service.service_name">
                                                </li>
                                            </template>
                                            <li x-show="filteredOptions.length === 0"
                                                class="px-5 py-2 text-sm text-gray-400 text-center">
                                                No results found
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="relative w-full md:w-48">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                                        Rp
                                    </span>
                                    <input type="text"
                                        :value="item.price ? parseInt(item.price).toLocaleString('id-ID') : ''"
                                        @input="
                                            let value = $event.target.value.replace(/[^0-9]/g, '');
                                            item.price = value ? parseInt(value) : 0;
                                            $event.target.value = item.price ? item.price.toLocaleString('id-ID') : '';
                                        "
                                        placeholder="0"
                                        class="w-full rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                </div>

                                <button type="button" @click="removeAdditional(index)"
                                    class="w-full md:w-auto p-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors">
                                    <svg class="w-5 h-5 mx-auto md:mx-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Error Messages --}}
                            <div class="flex flex-col gap-1">
                                <template x-if="index === 0">
                                    <div>
                                        @error('additionals.0.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.0.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 1">
                                    <div>
                                        @error('additionals.1.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.1.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 2">
                                    <div>
                                        @error('additionals.2.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.2.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 3">
                                    <div>
                                        @error('additionals.3.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.3.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 4">
                                    <div>
                                        @error('additionals.4.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.4.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index >= 5">
                                    <div>
                                        <p class="text-xs text-red-600">Please check additional service #<span
                                                x-text="index + 1"></span></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Button Add --}}
                    <button type="button" @click="addAdditional"
                        class="w-full md:w-auto px-6 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm font-medium">
                        + Add Additional
                    </button>
                </div>

                {{-- Shipping Type --}}
                <div class="relative flex flex-col gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24">Shipping <span class="text-red-500">*</span></label>

                    <div class="relative w-full">
                        <x-select-form name="shipping_type" placeholder="Select Shipping" :options="[
                            ['value' => 'pickup', 'name' => 'Pickup'],
                            ['value' => 'delivery', 'name' => 'Delivery']
                        ]" value="value" display="name" :old="old('shipping_type', $order->shipping_type)" />
                        <p x-show="errors.shipping_type" x-text="errors.shipping_type" class="absolute left-0 -bottom-5 text-xs text-red-600"></p>
                        @error('shipping_type')
                            <p class="absolute left-0 -bottom-5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>
        </section>

        {{-- ================= Image Upload, Discount & Final Price ================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            {{-- Left: Order Image Upload --}}
            <div class="space-y-4" x-data="{
                orderImage: null,
                imagePreview: '{{ $order->img_url ? route('orders.serve-image', $order->id) : '' }}' || null,
                removeImageFlag: false,
                isDragging: false,
                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        this.orderImage = file;
                        this.imagePreview = URL.createObjectURL(file);
                        this.removeImageFlag = false;
                    }
                },
                handleDrop(event) {
                    event.preventDefault();
                    this.isDragging = false;
                    const file = event.dataTransfer.files[0];
                    if (file && file.type.startsWith('image/')) {
                        this.orderImage = file;
                        this.imagePreview = URL.createObjectURL(file);
                        this.removeImageFlag = false;
                        // Update file input
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        $refs.orderImageInput.files = dataTransfer.files;
                    }
                },
                removeImage() {
                    this.orderImage = null;
                    this.imagePreview = null;
                    this.removeImageFlag = true;
                    $refs.orderImageInput.value = '';
                }
            }">
                <label class="block text-sm font-medium text-gray-600">Order Image (Optional)</label>
                
                {{-- Drag & Drop Area --}}
                <div @drop="handleDrop($event)" 
                     @dragover.prevent="isDragging = true" 
                     @dragleave.prevent="isDragging = false"
                     @click="$refs.orderImageInput.click()"
                     :class="isDragging ? 'border-primary bg-primary/5' : 'border-gray-300'"
                     class="relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-all duration-200">
                    
                    {{-- Preview Image --}}
                    <div x-show="imagePreview" class="relative">
                        <img :src="imagePreview" alt="Order Preview" class="max-h-48 mx-auto rounded-lg shadow-sm">
                        <button type="button" @click.stop="removeImage()"
                            class="absolute top-2 right-2 p-1.5 rounded-full bg-red-500 text-white hover:bg-red-600 shadow-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    {{-- Upload Icon & Text --}}
                    <div x-show="!imagePreview" class="space-y-2">
                        <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium text-primary">Click to upload</span> or drag and drop
                        </p>
                        <p class="text-xs text-gray-500">PNG, JPG, JPEG up to 5MB</p>
                    </div>
                </div>
                
                <input type="file" x-ref="orderImageInput" name="order_image" accept="image/png,image/jpeg,image/jpg" 
                       @change="handleFileSelect($event)" class="hidden">
                
                {{-- Hidden input to flag image removal --}}
                <input type="hidden" name="remove_order_image" :value="removeImageFlag ? '1' : '0'">
                
                @error('order_image')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Right: Discount, Final Price & Submit --}}
            <div class="space-y-4">
                <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24">Discount</label>
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                            Rp
                        </span>
                        <input type="text" :value="discountDisplay || ''"
                            @input="
                                let value = $event.target.value.replace(/[^0-9]/g, '');
                                discount = value ? parseInt(value) : 0;
                                discountDisplay = value ? parseInt(value).toLocaleString('id-ID') : '';
                                $event.target.value = discountDisplay;
                            "
                            placeholder="0"
                            class="w-full rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-200 focus:border-green-400" />
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600">Final Price</p>
                    <p class="text-2xl md:text-lg font-bold text-gray-900"
                        x-text="'Rp ' + getFinalPrice().toLocaleString('id-ID')">
                    </p>
                </div>
                <button type="submit"
                    class="w-full px-4 py-3 md:py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-base md:text-sm font-medium">
                    Update Order
                </button>
            </div>
        </div>

        {{-- Hidden inputs for design variants and additionals --}}
        <template x-for="(design, dIndex) in designVariants" :key="'design-' + dIndex">
            <div>
                <input type="hidden" :name="'designs[' + dIndex + '][name]'" x-model="design.name">
                <template x-for="(variant, vIndex) in design.sleeveVariants" :key="'variant-' + dIndex + '-' + vIndex">
                    <div>
                        <template x-for="(row, rIndex) in variant.rows"
                            :key="'row-' + dIndex + '-' + vIndex + '-' + rIndex">
                            <div>
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][design_name]'"
                                    x-model="design.name">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][sleeve_id]'"
                                    x-model="variant.sleeve">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][size_id]'"
                                    x-model="row.size_id">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][qty]'"
                                    x-model="row.qty">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][unit_price]'"
                                    x-model="row.unitPrice">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>

        <template x-for="(item, index) in additionals" :key="'add-' + index">
            <div>
                <input type="hidden" :name="'additionals[' + index + '][service_id]'" x-model="item.service_id">
                <input type="hidden" :name="'additionals[' + index + '][price]'" x-model="item.price">
            </div>
        </template>

        {{-- Modal Image Preview --}}
        <div x-data="{ showImageModal: false, modalImageSrc: '' }"
             @open-image-modal.window="showImageModal = true; modalImageSrc = $event.detail"
             x-show="showImageModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
             @click="showImageModal = false">
            <div class="relative max-w-4xl max-h-[90vh]" @click.stop>
                <img :src="modalImageSrc" alt="Order Image" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
                <button type="button" @click="showImageModal = false"
                    class="absolute top-4 right-4 p-2 rounded-full bg-white/90 hover:bg-white shadow-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Hidden calculated totals --}}
        <input type="hidden" name="total_qty"
            :value="designVariants.reduce((sum, d) => sum + d.sleeveVariants.reduce((s, v) => s + v.rows.reduce((r, row) => r +
                parseInt(row.qty || 0), 0), 0), 0)">
        <input type="hidden" name="subtotal" :value="getSubTotal()">
        <input type="hidden" name="discount" :value="discount || 0">
        <input type="hidden" name="grand_total" :value="getFinalPrice()">

        {{-- ================= DELETE SIZE CONFIRMATION MODAL ================= --}}
        <div x-show="showDeleteSizeConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteSizeConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                {{-- Icon --}}
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                    Delete Size?
                </h3>

                {{-- Message --}}
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this size? This action cannot be undone.
                </p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteSizeConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="confirmDeleteSize()"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                        Yes, Delete
                    </button>
                </div>
            </div>
        </div>

    </form>

@endsection
@push('scripts')
    <script>
        function searchSelect(options, oldValue, fieldName) {
            return {
                open: false,
                search: '',
                options,
                selected: null,
                selectedId: oldValue || '',
                fieldName,

                init() {
                    if (this.selectedId) {
                        this.selected = this.options.find(o => String(o.id) === String(this.selectedId)) || null;
                    }
                    this.$dispatch(`${this.fieldName}-selected`, this.selectedId || '');
                },

                select(option) {
                    this.selected = option;
                    this.selectedId = option.id;
                    this.open = false;
                    this.$dispatch(`${this.fieldName}-selected`, this.selectedId);
                }
            }
        }

        function sleeveSelect(dIndex, vIndex, initialValue) {
            return {
                open: false,
                search: '',
                sleeves: @json($materialSleeves),
                selected: null,
                dIndex,
                vIndex,

                get filteredOptions() {
                    if (!this.search) return this.sleeves;
                    return this.sleeves.filter(s =>
                        s.sleeve_name.toLowerCase().includes(this.search.toLowerCase())
                    );
                },

                init() {
                    if (initialValue) {
                        this.selected = this.sleeves.find(s => String(s.id) === String(initialValue)) || null;
                    }
                },

                selectSleeve(sleeve) {
                    this.selected = sleeve;
                    this.open = false;
                    this.$dispatch('sleeve-selected', {
                        dIndex: this.dIndex,
                        vIndex: this.vIndex,
                        value: sleeve.id
                    });
                }
            }
        }

        function additionalServiceSelect(index, initialValue) {
            return {
                open: false,
                search: '',
                services: @json($services),
                selected: null,
                index,

                get filteredOptions() {
                    if (!this.search) return this.services;
                    return this.services.filter(s =>
                        s.service_name.toLowerCase().includes(this.search.toLowerCase())
                    );
                },

                init() {
                    if (initialValue) {
                        this.selected = this.services.find(s => String(s.id) === String(initialValue)) || null;
                    }
                },

                selectService(service) {
                    this.selected = service;
                    this.open = false;
                    this.$dispatch('service-selected', {
                        index: this.index,
                        value: service.id
                    });
                }
            }
        }

        function orderForm() {
            return {
                // ====== STATE UTAMA ======
                priority: @json(old('priority', $order->priority)),
                order_date: @json(old('order_date', $order->order_date->format('Y-m-d'))),
                deadline: @json(old('deadline', $order->deadline->format('Y-m-d'))),
                customer_id: @json(old('customer_id', $order->customer_id)),
                sales_id: @json(old('sales_id', $order->sales_id)),
                product_category_id: @json(old('product_category_id', $order->product_category_id)),
                product_color: @json(old('product_color', $order->product_color)),
                material_category_id: @json(old('material_category_id', $order->material_category_id)),
                material_texture_id: @json(old('material_texture_id', $order->material_texture_id)),
                notes: @json(old('notes', $order->notes)),
                discount: {{ old('discount', $order->discount ?? 0) }},
                discountDisplay: '{{ old('discount', $order->discount ?? 0) ? number_format($order->discount, 0, ',', '.') : '' }}',
                shipping_type: @json(old('shipping_type', $order->shipping_type)),

                // ====== DETAIL ======
                designVariants: [],
                additionals: [],

                // REF DATA
                sizes: @json($materialSizes),
                sleeves: @json($materialSleeves),

                // MODAL STATE
                openModal: null,
                selectedDesign: null,
                selectedVariant: null,
                selectedSizes: [],
                showDeleteSizeConfirm: null,

                // ERROR & SUBMIT STATE
                errors: {},
                isSubmitting: false,

                // INIT
                init() {
                    @if (old('designs'))
                        // If validation error, restore from old input
                        this.restoreFromOldInput();
                    @else
                        // Load existing order data
                        this.loadExistingData();
                    @endif

                    @if (old('additionals'))
                        @foreach (old('additionals', []) as $index => $add)
                            this.additionals.push({
                                service_id: '{{ $add['service_id'] ?? '' }}',
                                price: {{ $add['price'] ?? 0 }},
                                error: ''
                            });
                        @endforeach
                    @else
                        // Load existing additionals
                        @foreach ($order->extraServices as $extra)
                            this.additionals.push({
                                service_id: '{{ $extra->service_id }}',
                                price: {{ $extra->price }},
                                error: ''
                            });
                        @endforeach
                    @endif

                    // Auto-clear errors when user fills the fields
                    this.$watch('customer_id', () => { delete this.errors.customer_id; });
                    this.$watch('sales_id', () => { delete this.errors.sales_id; });
                    this.$watch('order_date', () => { delete this.errors.order_date; });
                    this.$watch('deadline', () => { delete this.errors.deadline; });
                    this.$watch('product_category_id', () => { delete this.errors.product_category_id; });
                    this.$watch('product_color', () => { delete this.errors.product_color; });
                    this.$watch('material_category_id', () => { delete this.errors.material_category_id; });
                    this.$watch('material_texture_id', () => { delete this.errors.material_texture_id; });
                    this.$watch('designVariants', () => { delete this.errors.designs; }, { deep: true });

                    // Watch for shipping_type changes (from hidden input)
                    const shippingObserver = new MutationObserver(() => {
                        delete this.errors.shipping_type;
                    });
                    const shippingInput = document.querySelector('input[name="shipping_type"]');
                    if (shippingInput) {
                        shippingObserver.observe(shippingInput, { attributes: true, attributeFilter: ['value'] });
                    }
                },

                // Load existing order data
                loadExistingData() {
                    const orderItems = @json($order->orderItems()->with(['designVariant', 'size', 'sleeve'])->get());
                    const designMap = {};

                    // Group items by design name
                    orderItems.forEach(item => {
                        const designName = item.design_variant.design_name;
                        if (!designMap[designName]) {
                            designMap[designName] = [];
                        }
                        designMap[designName].push(item);
                    });

                    // Rebuild design variants structure
                    Object.keys(designMap).forEach(designName => {
                        const items = designMap[designName];
                        const sleeveMap = {};

                        // Group by sleeve_id
                        items.forEach(item => {
                            if (!sleeveMap[item.sleeve_id]) {
                                sleeveMap[item.sleeve_id] = [];
                            }
                            sleeveMap[item.sleeve_id].push(item);
                        });

                        const sleeveVariants = [];
                        Object.keys(sleeveMap).forEach(sleeveId => {
                            const sleeveItems = sleeveMap[sleeveId];
                            const basePrice = sleeveItems[0].unit_price - (this.sizes.find(s => s.id ==
                                sleeveItems[0].size_id)?.extra_price || 0);

                            const rows = sleeveItems.map(item => {
                                const size = this.sizes.find(s => s.id == item.size_id);
                                const unitPrice = parseFloat(item.unit_price);
                                return {
                                    size_id: item.size_id,
                                    size: item.size?.size_name || '',
                                    extraPrice: parseFloat(item.size?.extra_price || 0),
                                    unitPrice: unitPrice,
                                    unitPriceDisplay: unitPrice ? unitPrice.toLocaleString(
                                        'id-ID') : '',
                                    qty: parseInt(item.qty || 0)
                                };
                            });

                            sleeveVariants.push({
                                sleeve: sleeveId,
                                basePrice: basePrice,
                                basePriceDisplay: basePrice ? basePrice.toLocaleString('id-ID') :
                                    '',
                                rows: rows,
                                error: '',
                                basePriceError: ''
                            });
                        });

                        this.designVariants.push({
                            name: designName,
                            sleeveVariants: sleeveVariants,
                            error: ''
                        });
                    });
                },

                // Restore design variants from old input (for validation errors)
                restoreFromOldInput() {
                    const oldDesigns = @json(old('designs', []));
                    const designMap = {};

                    // Group items by design name
                    Object.values(oldDesigns).forEach(design => {
                        if (design.items) {
                            Object.values(design.items).forEach(item => {
                                const designName = item.design_name;
                                if (!designMap[designName]) {
                                    designMap[designName] = [];
                                }
                                designMap[designName].push(item);
                            });
                        }
                    });

                    // Rebuild design variants structure
                    Object.keys(designMap).forEach(designName => {
                        const items = designMap[designName];
                        const sleeveMap = {};

                        // Group by sleeve_id
                        items.forEach(item => {
                            if (!sleeveMap[item.sleeve_id]) {
                                sleeveMap[item.sleeve_id] = [];
                            }
                            sleeveMap[item.sleeve_id].push(item);
                        });

                        const sleeveVariants = [];
                        Object.keys(sleeveMap).forEach(sleeveId => {
                            const sleeveItems = sleeveMap[sleeveId];
                            const basePrice = sleeveItems[0].unit_price - (this.sizes.find(s => s.id ==
                                sleeveItems[0].size_id)?.extra_price || 0);

                            const rows = sleeveItems.map(item => {
                                const size = this.sizes.find(s => s.id == item.size_id);
                                const unitPrice = parseFloat(item.unit_price);
                                return {
                                    size_id: item.size_id,
                                    size: size?.size_name || '',
                                    extraPrice: parseFloat(size?.extra_price || 0),
                                    unitPrice: unitPrice,
                                    unitPriceDisplay: unitPrice ? unitPrice.toLocaleString(
                                        'id-ID') : '',
                                    qty: parseInt(item.qty || 0)
                                };
                            });

                            sleeveVariants.push({
                                sleeve: sleeveId,
                                basePrice: basePrice,
                                basePriceDisplay: basePrice ? basePrice.toLocaleString('id-ID') :
                                    '',
                                rows: rows,
                                error: '',
                                basePriceError: ''
                            });
                        });

                        this.designVariants.push({
                            name: designName,
                            sleeveVariants: sleeveVariants,
                            error: ''
                        });
                    });
                },

                // ====== DESIGN VARIANT HANDLER ======
                addDesignVariant() {
                    this.designVariants.push({
                        name: '',
                        sleeveVariants: [],
                        error: ''
                    });
                },
                addSleeveVariant(dIndex) {
                    this.designVariants[dIndex].sleeveVariants.push({
                        sleeve: '',
                        basePrice: 0,
                        basePriceDisplay: '',
                        rows: [],
                        error: '',
                        basePriceError: ''
                    });
                },

                // Update unit prices when base price changes
                updateUnitPrices(dIndex, vIndex) {
                    const variant = this.designVariants[dIndex].sleeveVariants[vIndex];
                    const basePrice = parseFloat(variant.basePrice) || 0;

                    variant.rows.forEach(row => {
                        row.unitPrice = basePrice + row.extraPrice;
                        row.unitPriceDisplay = row.unitPrice ? row.unitPrice.toLocaleString('id-ID') : '';
                    });
                },

                // SIZE HANDLER
                toggleSize(size) {
                    let exists = this.selectedSizes.find(s => s.id === size.id);
                    this.selectedSizes = exists ?
                        this.selectedSizes.filter(s => s.id !== size.id) : [...this.selectedSizes, size];
                },
                applySizes() {
                    if (this.selectedDesign !== null && this.selectedVariant !== null) {
                        const variant = this.designVariants[this.selectedDesign].sleeveVariants[this.selectedVariant];
                        const basePrice = parseFloat(variant.basePrice) || 0;

                        this.selectedSizes.forEach(size => {
                            let exists = variant.rows.find(r => r.size_id === size.id);

                            if (!exists) {
                                const extraPrice = parseFloat(size.extra_price) || 0;
                                const unitPrice = basePrice + extraPrice;

                                variant.rows.push({
                                    size_id: size.id,
                                    size: size.size_name,
                                    extraPrice: extraPrice,
                                    unitPrice: unitPrice,
                                    unitPriceDisplay: unitPrice ? unitPrice.toLocaleString('id-ID') : '',
                                    qty: 0
                                });
                            }
                        });

                        this.selectedSizes = [];
                        this.openModal = null;
                    }
                },

                // ADDITIONALS
                addAdditional() {
                    this.additionals.push({
                        service_id: '',
                        price: 0,
                        error: ''
                    });
                },
                removeAdditional(index) {
                    this.additionals.splice(index, 1);
                },

                // CALCULATION
                getSubTotal() {
                    let total = 0;
                    this.designVariants.forEach(design => {
                        design.sleeveVariants.forEach(variant => {
                            variant.rows.forEach(row => {
                                total += (row.unitPrice || 0) * (row.qty || 0);
                            });
                        });
                    });
                    this.additionals.forEach(add => {
                        total += parseInt(add.price || 0);
                    });
                    return total;
                },
                getFinalPrice() {
                    return this.getSubTotal() - (this.discount || 0);
                },

                // ====== VALIDATION ======
                validateForm() {
                    this.errors = {};
                    let isValid = true;

                    // Validate Customer
                    if (!this.customer_id || this.customer_id === '') {
                        this.errors.customer_id = 'Customer is required';
                        isValid = false;
                    }

                    // Validate Sales
                    if (!this.sales_id || this.sales_id === '') {
                        this.errors.sales_id = 'Sales person is required';
                        isValid = false;
                    }

                    // Validate Order Date
                    if (!this.order_date || this.order_date === '') {
                        this.errors.order_date = 'Order date is required';
                        isValid = false;
                    }

                    // Validate Deadline
                    if (!this.deadline || this.deadline === '') {
                        this.errors.deadline = 'Deadline is required';
                        isValid = false;
                    } else if (this.order_date && this.deadline < this.order_date) {
                        this.errors.deadline = 'Deadline must be after or equal to order date';
                        isValid = false;
                    }

                    // Validate Product
                    if (!this.product_category_id || this.product_category_id === '') {
                        this.errors.product_category_id = 'Product is required';
                        isValid = false;
                    }

                    // Validate Color
                    if (!this.product_color || this.product_color.trim() === '') {
                        this.errors.product_color = 'Color is required';
                        isValid = false;
                    }

                    // Validate Material Category
                    if (!this.material_category_id || this.material_category_id === '') {
                        this.errors.material_category_id = 'Material is required';
                        isValid = false;
                    }

                    // Validate Material Texture
                    if (!this.material_texture_id || this.material_texture_id === '') {
                        this.errors.material_texture_id = 'Texture is required';
                        isValid = false;
                    }

                    // Validate Shipping Type
                    const shippingInput = document.querySelector('input[name="shipping_type"]');
                    if (!shippingInput || !shippingInput.value || shippingInput.value === '') {
                        this.errors.shipping_type = 'Shipping type is required';
                        isValid = false;
                    }

                    // Validate Design Variants
                    if (this.designVariants.length === 0) {
                        this.errors.designs = 'At least one design variant is required';
                        isValid = false;
                    } else {
                        // Validate each design variant
                        let hasItems = false;
                        this.designVariants.forEach((design, dIndex) => {
                            // Check design name
                            if (!design.name || design.name.trim() === '') {
                                design.error = 'Design name is required';
                                isValid = false;
                            } else {
                                design.error = '';
                            }

                            // Check if has sleeve variants with items
                            design.sleeveVariants.forEach((variant, vIndex) => {
                                if (variant.rows && variant.rows.length > 0) {
                                    hasItems = true;
                                    // Validate quantities
                                    variant.rows.forEach(row => {
                                        if (!row.qty || row.qty <= 0) {
                                            variant.error = 'All quantities must be greater than 0';
                                            isValid = false;
                                        }
                                    });
                                }
                            });
                        });

                        if (!hasItems) {
                            this.errors.designs = 'At least one item with quantity is required';
                            isValid = false;
                        }
                    }

                    // Validate Additionals (if any added)
                    if (this.additionals.length > 0) {
                        this.additionals.forEach((additional, index) => {
                            additional.error = '';
                            
                            // If additional exists, service must be selected
                            if (!additional.service_id || additional.service_id === '') {
                                additional.error = 'Please select a service or remove this additional';
                                isValid = false;
                            }
                            // Price can be 0, no validation needed for price
                        });
                    }

                    // Scroll to first error
                    if (!isValid) {
                        setTimeout(() => {
                            const firstError = document.querySelector('[x-show="errors.' + Object.keys(this.errors)[0] + '"]') ||
                                              document.querySelector('.text-red-600:not([style*="display: none"])');
                            if (firstError) {
                                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 100);
                    }

                    return isValid;
                },

                // ====== SUBMIT ======
                validateAndSubmit(e) {
                    e.preventDefault();
                    
                    if (this.isSubmitting) return;

                    if (this.validateForm()) {
                        this.isSubmitting = true;
                        // Submit form natively
                        e.target.submit();
                    }
                },

                // ====== DELETE SIZE ======
                confirmDeleteSize() {
                    if (this.showDeleteSizeConfirm) {
                        const { dIndex, vIndex, rIndex } = this.showDeleteSizeConfirm;
                        this.designVariants[dIndex].sleeveVariants[vIndex].rows.splice(rIndex, 1);
                        this.showDeleteSizeConfirm = null;
                    }
                }
            }
        }
    </script>
@endpush
